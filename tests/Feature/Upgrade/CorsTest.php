<?php

namespace Tests\Feature\Upgrade;

/**
 * The API has to answer CORS preflight requests with the
 * Access-Control-Allow-Origin header.
 */
class CorsTest extends UpgradeTestCase
{
    /**
     * @var string
     */
    protected $origin = 'https://gate.example.test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cors.allowed_origins' => [$this->origin]]);
    }

    public function testAPreflightRequestReturnsTheCorsHeaders(): void
    {
        $response = $this->call('OPTIONS', '/api/v1/hardware', [], [], [], [
            'HTTP_ORIGIN' => $this->origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
        ]);

        $this->assertSame(
            $this->origin,
            $response->headers->get('Access-Control-Allow-Origin')
        );
        $this->assertNotEmpty($response->headers->get('Access-Control-Allow-Methods'));
    }

    public function testASimpleRequestReturnsTheCorsHeaders(): void
    {
        $response = $this->call('GET', '/api/v1/hardware', [], [], [], [
            'HTTP_ORIGIN' => $this->origin,
        ]);

        $this->assertSame(
            $this->origin,
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }
}
