<?php

namespace App\Services\Seo;

use Carbon\CarbonInterface;
use App\Models\Project;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SearchConsoleService
{
    public function isConfigured(): bool
    {
        return filled(config('services.google_search_console.client_id'))
            && filled(config('services.google_search_console.client_secret'))
            && filled(config('services.google_search_console.refresh_token'));
    }

    public function isReadyForProject(Project $project): bool
    {
        return $this->isConfigured() && filled($project->search_console_property);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchSignals(Project $project): array
    {
        if (! $this->isReadyForProject($project)) {
            return [
                'configured' => false,
                'property' => $project->search_console_property,
                'country' => $project->target_country,
                'top_queries' => [],
                'rising_queries' => [],
                'low_ctr_queries' => [],
                'error_message' => null,
            ];
        }

        try {
            $property = (string) $project->search_console_property;
            $country = filled($project->target_country) ? Str::upper((string) $project->target_country) : null;
            $windowEnd = now()->subDays(3)->startOfDay();
            $last7Start = $windowEnd->copy()->subDays(6);
            $previous7End = $last7Start->copy()->subDay();
            $previous7Start = $previous7End->copy()->subDays(6);
            $last28Start = $windowEnd->copy()->subDays(27);

            $topQueries = $this->queryTopQueries($property, $last28Start, $windowEnd, $country);
            $last7Queries = $this->queryTopQueries($property, $last7Start, $windowEnd, $country, 50);
            $previous7Queries = $this->queryTopQueries($property, $previous7Start, $previous7End, $country, 50);
            $risingQueries = $this->buildRisingQueries($last7Queries, $previous7Queries);
            $lowCtrQueries = collect($topQueries)
                ->filter(fn (array $row): bool => $row['impressions'] >= 25 && $row['ctr'] <= 0.03)
                ->take(8)
                ->values()
                ->all();

            $project->forceFill([
                'last_search_console_synced_at' => now(),
            ])->save();

            return [
                'configured' => true,
                'property' => $property,
                'country' => $country,
                'top_queries' => $topQueries,
                'rising_queries' => $risingQueries,
                'low_ctr_queries' => $lowCtrQueries,
                'error_message' => null,
                'window' => [
                    'start_date' => $last28Start->toDateString(),
                    'end_date' => $windowEnd->toDateString(),
                ],
            ];
        } catch (Throwable $exception) {
            return [
                'configured' => false,
                'property' => $project->search_console_property,
                'country' => $project->target_country,
                'top_queries' => [],
                'rising_queries' => [],
                'low_ctr_queries' => [],
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<int, string>
     */
    public function accessibleProperties(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->request()->get('sites');

            $response->throw();

            return collect(data_get($response->json(), 'siteEntry', []))
                ->pluck('siteUrl')
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{query: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    protected function queryTopQueries(
        string $property,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        ?string $country,
        int $rowLimit = 25,
    ): array {
        $payload = [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'dimensions' => ['query'],
            'type' => 'web',
            'rowLimit' => $rowLimit,
        ];

        if (filled($country)) {
            $payload['dimensionFilterGroups'] = [[
                'groupType' => 'and',
                'filters' => [[
                    'dimension' => 'country',
                    'operator' => 'equals',
                    'expression' => $country,
                ]],
            ]];
        }

        $response = $this->request()->post(
            'sites/'.rawurlencode($property).'/searchAnalytics/query',
            $payload,
        );

        $response->throw();

        return collect(data_get($response->json(), 'rows', []))
            ->map(fn (array $row): array => [
                'query' => (string) data_get($row, 'keys.0', ''),
                'clicks' => (float) data_get($row, 'clicks', 0),
                'impressions' => (float) data_get($row, 'impressions', 0),
                'ctr' => (float) data_get($row, 'ctr', 0),
                'position' => (float) data_get($row, 'position', 0),
            ])
            ->filter(fn (array $row): bool => filled($row['query']))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{query: string, clicks: float, impressions: float, ctr: float, position: float}>  $current
     * @param  array<int, array{query: string, clicks: float, impressions: float, ctr: float, position: float}>  $previous
     * @return array<int, array<string, mixed>>
     */
    protected function buildRisingQueries(array $current, array $previous): array
    {
        $previousByQuery = collect($previous)->keyBy('query');

        return collect($current)
            ->map(function (array $row) use ($previousByQuery): array {
                $previousRow = $previousByQuery->get($row['query']);
                $previousImpressions = (float) data_get($previousRow, 'impressions', 0);
                $impressionDelta = $row['impressions'] - $previousImpressions;
                $growthRatio = $previousImpressions > 0
                    ? round(($impressionDelta / $previousImpressions) * 100, 2)
                    : ($row['impressions'] > 0 ? 999 : 0);

                return [
                    ...$row,
                    'previous_impressions' => $previousImpressions,
                    'impression_delta' => $impressionDelta,
                    'growth_ratio' => $growthRatio,
                ];
            })
            ->filter(fn (array $row): bool => $row['impressions'] >= 10 && $row['growth_ratio'] > 15)
            ->sortByDesc('growth_ratio')
            ->take(8)
            ->values()
            ->all();
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.google_search_console.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(1, 400)
            ->withToken($this->accessToken());
    }

    protected function accessToken(): string
    {
        return Cache::remember('google-search-console-access-token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->timeout(20)
                ->post((string) config('services.google_search_console.token_url'), [
                    'client_id' => (string) config('services.google_search_console.client_id'),
                    'client_secret' => (string) config('services.google_search_console.client_secret'),
                    'refresh_token' => (string) config('services.google_search_console.refresh_token'),
                    'grant_type' => 'refresh_token',
                ]);

            $response->throw();

            $token = (string) data_get($response->json(), 'access_token');

            if (blank($token)) {
                throw new \RuntimeException('Nao foi possivel obter o access token do Search Console.');
            }

            return $token;
        });
    }
}
