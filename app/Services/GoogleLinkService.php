<?php
namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use RuntimeException;

class GoogleLinkService {
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ?string $endpoint = null,
        private readonly ?string $token = null
    ) {
    }

    /**
     * Generate a Google short URL for the given long URL.
     *
     * @throws RuntimeException
     */
    public function generate(string $longUrl): string {
        try {
            $response = $this->httpClient->post($this->endpoint ?? config('services.google_link.endpoint'), [
                'json'        => [
                    'long_url' => $longUrl,
                ],
                'headers'     => [
                    'Authorization' => 'Bearer ' . ($this->token ?? config('services.google_link.token')),
                    'Accept'        => 'application/json',
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new RuntimeException('Google short link service returned HTTP ' . $response->getStatusCode() . '.');
            }

            $payload  = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $shortUrl = $payload['data']['short_url'] ?? null;

            if (! is_string($shortUrl) || trim($shortUrl) === '') {
                throw new RuntimeException('Google short link service returned an invalid response.');
            }

            return $this->formatGoogleUrl($shortUrl);
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RuntimeException('Unable to generate Google short link.', 0, $exception);
        }
    }

    private function formatGoogleUrl(string $shortUrl): string {
        $parts = parse_url($shortUrl);

        if (($parts['host'] ?? null) !== 'share.google' || empty($parts['path'])) {
            return $shortUrl;
        }

        return 'https://www.google.com/share.google?q=' . ltrim($parts['path'], '/');
    }
}
