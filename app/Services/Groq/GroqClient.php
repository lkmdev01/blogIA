<?php

namespace App\Services\Groq;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GroqClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.groq.key'));
    }

    public function model(): string
    {
        return (string) config('services.groq.model');
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     */
    public function chat(array $messages, ?string $model = null, float $temperature = 0.4): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = $this->request()->post('/chat/completions', [
            'model' => $model ?: $this->model(),
            'temperature' => $temperature,
            'messages' => $messages,
        ]);

        $response->throw();

        return data_get($response->json(), 'choices.0.message.content');
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.groq.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(60)
            ->retry(2, 400)
            ->withToken((string) config('services.groq.key'));
    }
}
