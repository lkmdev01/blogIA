<?php

namespace App\Services\Seo;

use App\Models\Project;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleTrendsService
{
    public function isConfigured(): bool
    {
        return filled(config('services.google_trends_bigquery.project_id'))
            && filled(config('services.google_trends_bigquery.client_email'))
            && (
                filled(config('services.google_trends_bigquery.private_key_base64'))
                || filled(config('services.google_trends_bigquery.private_key'))
            );
    }

    public function isReadyForProject(Project $project): bool
    {
        return $this->isConfigured() && filled($project->google_trends_country);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchSignals(Project $project): array
    {
        if (! $this->isReadyForProject($project)) {
            return [
                'configured' => false,
                'country' => $project->google_trends_country,
                'region' => $project->google_trends_region,
                'seed_tokens' => [],
                'top_terms' => [],
                'rising_terms' => [],
                'error_message' => null,
            ];
        }

        try {
            $country = Str::upper((string) $project->google_trends_country);
            $region = filled($project->google_trends_region)
                ? trim((string) $project->google_trends_region)
                : null;
            $seedTokens = $this->seedTokensFor($project);
            $endDate = now()->subDay()->toDateString();
            $startDate = now()->subDays(13)->toDateString();

            if ($seedTokens === []) {
                return [
                    'configured' => true,
                    'country' => $country,
                    'region' => $region,
                    'seed_tokens' => [],
                    'top_terms' => [],
                    'rising_terms' => [],
                    'error_message' => null,
                    'window' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                ];
            }

            $topTerms = $this->queryTerms(
                tableCandidates: [$this->topTermsTable()],
                country: $country,
                region: $region,
                startDate: $startDate,
                endDate: $endDate,
                seedTokens: $seedTokens,
            );

            $risingTerms = $this->queryTerms(
                tableCandidates: $this->risingTermsTables(),
                country: $country,
                region: $region,
                startDate: $startDate,
                endDate: $endDate,
                seedTokens: $seedTokens,
            );

            $project->forceFill([
                'last_google_trends_synced_at' => now(),
            ])->save();

            return [
                'configured' => true,
                'country' => $country,
                'region' => $region,
                'seed_tokens' => $seedTokens,
                'top_terms' => $topTerms,
                'rising_terms' => $risingTerms,
                'error_message' => null,
                'window' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ];
        } catch (Throwable $exception) {
            return [
                'configured' => false,
                'country' => $project->google_trends_country,
                'region' => $project->google_trends_region,
                'seed_tokens' => [],
                'top_terms' => [],
                'rising_terms' => [],
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<int, string>
     */
    protected function seedTokensFor(Project $project): array
    {
        return collect([
            $project->niche,
            $project->target_location,
            ...($project->primary_keywords ?? []),
        ])->filter()
            ->flatMap(function (string $value): array {
                return preg_split('/[^a-z0-9]+/i', Str::of($value)->ascii()->lower()->toString()) ?: [];
            })
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => mb_strlen($token) >= 4 && ! in_array($token, $this->stopWords(), true))
            ->unique()
            ->values()
            ->take(12)
            ->all();
    }

    /**
     * @param  array<int, string>  $tableCandidates
     * @param  array<int, string>  $seedTokens
     * @return array<int, array{term: string, peak_score: int, best_rank: int, active_days: int, latest_refresh_date: string}>
     */
    protected function queryTerms(
        array $tableCandidates,
        string $country,
        ?string $region,
        string $startDate,
        string $endDate,
        array $seedTokens,
    ): array {
        $lastException = null;

        foreach ($tableCandidates as $table) {
            try {
                $rows = $this->queryRows(
                    query: $this->termsQueryFor($table),
                    parameters: [
                        $this->dateParameter('start_date', $startDate),
                        $this->dateParameter('end_date', $endDate),
                        $this->stringParameter('country_code', $country),
                        $this->boolParameter('has_region', filled($region)),
                        $this->stringParameter('region_name', $region ?? ''),
                        $this->stringParameter('seed_pattern', $this->seedPatternFor($seedTokens)),
                    ],
                    columns: ['term', 'peak_score', 'best_rank', 'active_days', 'latest_refresh_date'],
                );

                return collect($rows)
                    ->map(fn (array $row): array => [
                        'term' => (string) data_get($row, 'term', ''),
                        'peak_score' => (int) data_get($row, 'peak_score', 0),
                        'best_rank' => (int) data_get($row, 'best_rank', 0),
                        'active_days' => (int) data_get($row, 'active_days', 0),
                        'latest_refresh_date' => (string) data_get($row, 'latest_refresh_date', ''),
                    ])
                    ->filter(fn (array $row): bool => filled($row['term']))
                    ->values()
                    ->all();
            } catch (RuntimeException $exception) {
                $lastException = $exception;

                if (! $this->isMissingTableException($exception)) {
                    throw $exception;
                }
            }
        }

        if ($lastException instanceof RuntimeException) {
            throw $lastException;
        }

        return [];
    }

    protected function termsQueryFor(string $table): string
    {
        return <<<SQL
SELECT
  term,
  CAST(MAX(score) AS INT64) AS peak_score,
  CAST(MIN(rank) AS INT64) AS best_rank,
  CAST(COUNT(DISTINCT refresh_date) AS INT64) AS active_days,
  CAST(MAX(refresh_date) AS STRING) AS latest_refresh_date
FROM `{$this->dataset()}.{$table}`
WHERE refresh_date BETWEEN @start_date AND @end_date
  AND country_code = @country_code
  AND (@has_region = FALSE OR region_name = @region_name)
  AND REGEXP_CONTAINS(LOWER(term), @seed_pattern)
GROUP BY term
ORDER BY active_days DESC, peak_score DESC, best_rank ASC, term ASC
LIMIT 12
SQL;
    }

    /**
     * @param  array<int, array<string, mixed>>  $parameters
     * @param  array<int, string>  $columns
     * @return array<int, array<string, mixed>>
     */
    protected function queryRows(string $query, array $parameters, array $columns): array
    {
        $response = $this->request()->post(
            'projects/'.$this->projectId().'/queries',
            [
                'query' => $query,
                'useLegacySql' => false,
                'parameterMode' => 'NAMED',
                'location' => (string) config('services.google_trends_bigquery.location', 'US'),
                'queryParameters' => $parameters,
            ],
        );

        $payload = $response->json();

        if (! $response->successful() || filled(data_get($payload, 'error'))) {
            $message = (string) data_get($payload, 'error.message', $response->body());

            throw new RuntimeException($message !== '' ? $message : 'Nao foi possivel consultar o Google Trends no BigQuery.');
        }

        return collect(data_get($payload, 'rows', []))
            ->map(fn (array $row): array => $this->mapRow($row, $columns))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    protected function mapRow(array $row, array $columns): array
    {
        $values = data_get($row, 'f', []);

        return collect($columns)
            ->mapWithKeys(function (string $column, int $index) use ($values): array {
                return [$column => data_get($values, $index.'.v')];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function stringParameter(string $name, string $value): array
    {
        return [
            'name' => $name,
            'parameterType' => ['type' => 'STRING'],
            'parameterValue' => ['value' => $value],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dateParameter(string $name, string $value): array
    {
        return [
            'name' => $name,
            'parameterType' => ['type' => 'DATE'],
            'parameterValue' => ['value' => $value],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function boolParameter(string $name, bool $value): array
    {
        return [
            'name' => $name,
            'parameterType' => ['type' => 'BOOL'],
            'parameterValue' => ['value' => $value ? 'true' : 'false'],
        ];
    }

    /**
     * @param  array<int, string>  $tokens
     */
    protected function seedPatternFor(array $tokens): string
    {
        return implode('|', array_map(fn (string $token): string => preg_quote($token, '/'), $tokens));
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.google_trends_bigquery.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(1, 400)
            ->withToken($this->accessToken());
    }

    protected function accessToken(): string
    {
        return Cache::remember('google-trends-bigquery-access-token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->timeout(20)
                ->post((string) config('services.google_trends_bigquery.token_url'), [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->jwtAssertion(),
                ]);

            $response->throw();

            $token = (string) data_get($response->json(), 'access_token');

            if (blank($token)) {
                throw new RuntimeException('Nao foi possivel obter o access token do BigQuery.');
            }

            return $token;
        });
    }

    protected function jwtAssertion(): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES) ?: '{}');

        $issuedAt = now()->timestamp;
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => (string) config('services.google_trends_bigquery.client_email'),
            'scope' => 'https://www.googleapis.com/auth/bigquery',
            'aud' => (string) config('services.google_trends_bigquery.token_url'),
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ], JSON_UNESCAPED_SLASHES) ?: '{}');

        $unsignedToken = $header.'.'.$claims;
        $privateKey = openssl_pkey_get_private($this->privateKey());

        if ($privateKey === false) {
            throw new RuntimeException('A chave privada do BigQuery esta invalida.');
        }

        $signature = '';
        $signed = openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (is_resource($privateKey) || $privateKey instanceof \OpenSSLAsymmetricKey) {
            openssl_free_key($privateKey);
        }

        if (! $signed) {
            throw new RuntimeException('Nao foi possivel assinar o token do BigQuery.');
        }

        return $unsignedToken.'.'.$this->base64UrlEncode($signature);
    }

    protected function privateKey(): string
    {
        $base64Key = trim((string) config('services.google_trends_bigquery.private_key_base64'));

        if ($base64Key !== '') {
            $decodedKey = base64_decode($base64Key, true);

            if ($decodedKey !== false && $decodedKey !== '') {
                return $this->normalizePrivateKey($decodedKey);
            }
        }

        return $this->normalizePrivateKey((string) config('services.google_trends_bigquery.private_key'));
    }

    protected function normalizePrivateKey(string $privateKey): string
    {
        $normalizedKey = trim($privateKey, " \t\n\r\0\x0B\"");

        return str_replace(["\r\n", '\r\n', '\n', '\r'], "\n", $normalizedKey);
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function dataset(): string
    {
        return (string) config('services.google_trends_bigquery.dataset', 'bigquery-public-data.google_trends');
    }

    protected function projectId(): string
    {
        return (string) config('services.google_trends_bigquery.project_id');
    }

    protected function topTermsTable(): string
    {
        return (string) config('services.google_trends_bigquery.top_terms_table', 'international_top_terms');
    }

    /**
     * @return array<int, string>
     */
    protected function risingTermsTables(): array
    {
        return array_values(array_unique(array_filter([
            (string) config('services.google_trends_bigquery.rising_terms_table', 'international_top_rising_terms'),
            'international_rising_terms',
        ])));
    }

    protected function isMissingTableException(RuntimeException $exception): bool
    {
        return Str::contains(Str::lower($exception->getMessage()), ['not found', 'table']);
    }

    /**
     * @return array<int, string>
     */
    protected function stopWords(): array
    {
        return [
            'para',
            'com',
            'sem',
            'sobre',
            'entre',
            'como',
            'mais',
            'menos',
            'blog',
            'site',
            'area',
            'cidade',
            'mercado',
        ];
    }
}
