<?php
namespace Tests\Unit;

use App\Services\GoogleLinkService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GoogleLinkServiceTest extends TestCase {
    public function test_it_generates_a_google_short_url(): void {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'short_url' => 'https://share.google/CA4To95pCb6jyNEN4',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]));
        $handler->push(Middleware::history($history));

        $service = new GoogleLinkService(new Client([
            'handler' => $handler,
        ]), 'http://localhost:3501/api/v1/shortlink');

        $shortUrl = $service->generate('https://local.test/abc12');

        $this->assertSame('https://share.google/CA4To95pCb6jyNEN4', $shortUrl);
        $this->assertCount(1, $history);
        $this->assertSame('POST', $history[0]['request']->getMethod());
        $this->assertSame(
            ['long_url' => 'https://local.test/abc12'],
            json_decode((string) $history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function test_it_rejects_an_unsuccessful_response(): void {
        $service = new GoogleLinkService(new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(502),
            ])),
        ]), 'http://localhost:3501/api/v1/shortlink');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('returned HTTP 502');

        $service->generate('https://local.test/abc12');
    }

    public function test_it_rejects_a_response_without_a_short_url(): void {
        $service = new GoogleLinkService(new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, [], '{"data":{}}'),
            ])),
        ]), 'http://localhost:3501/api/v1/shortlink');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid response');

        $service->generate('https://local.test/abc12');
    }
}
