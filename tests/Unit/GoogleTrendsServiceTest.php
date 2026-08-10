<?php

use App\Services\Seo\GoogleTrendsService;
use Tests\TestCase;

uses(TestCase::class);

it('prefers the base64 private key when configured', function () {
    $privateKey = <<<'KEY'
-----BEGIN PRIVATE KEY-----
line-1
line-2
-----END PRIVATE KEY-----
KEY;

    config()->set('services.google_trends_bigquery.private_key', 'invalid-key');
    config()->set('services.google_trends_bigquery.private_key_base64', base64_encode($privateKey));

    $service = new class extends GoogleTrendsService
    {
        public function exposedPrivateKey(): string
        {
            return $this->privateKey();
        }
    };

    expect($service->isConfigured())->toBeTrue();
    expect($service->exposedPrivateKey())->toBe($privateKey);
});

it('normalizes quoted escaped pem keys as a fallback', function () {
    $privateKey = <<<'KEY'
-----BEGIN PRIVATE KEY-----
line-1
line-2
-----END PRIVATE KEY-----
KEY;

    config()->set('services.google_trends_bigquery.private_key_base64', null);
    config()->set(
        'services.google_trends_bigquery.private_key',
        '"'.str_replace("\n", '\n', $privateKey).'"',
    );

    $service = new class extends GoogleTrendsService
    {
        public function exposedPrivateKey(): string
        {
            return $this->privateKey();
        }
    };

    expect($service->exposedPrivateKey())->toBe($privateKey);
});
