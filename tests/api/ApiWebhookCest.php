<?php

use App\Models\User;
use App\Models\Webhook;
use App\Http\Transformers\WebhookTransformer;

class ApiWebhookCest
{
    protected $user;

    public function _before(ApiTester $I)
    {
        $this->user = User::factory()->create();
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amBearerAuthenticated($I->getToken($this->user));
    }

    /** @test */
    public function indexWebhook(ApiTester $I)
    {
        $I->wantTo('Get a list of webhooks');

        Webhook::factory()->count(3)->create();

        $I->sendGET('/webhooks?order_by=id&limit=10');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse(), true);

        $I->assertArrayHasKey('rows', $response);
        $I->assertIsArray($response['rows']);
        $I->assertGreaterThanOrEqual(1, count($response['rows']));

        $webhook = Webhook::orderByDesc('created_at')->first();
        $expected = (new WebhookTransformer)->transformWebhook($webhook);
        $I->assertContains($expected, $response['rows']);
    }


    /** @test */
    public function createWebhook(ApiTester $I)
    {
        $I->wantTo('Create a new webhook');

        $data = [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'type' => [
                'event',
            ],
        ];

        $I->sendPOST('/webhooks', $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse(), true);
        $I->assertEquals('success', $response['status']);
        $I->assertEquals('Test Webhook', $response['payload']['name']);
        $I->assertEquals('https://example.com/webhook', $response['payload']['url']);
    }

    /** @test */
    public function updateWebhook(ApiTester $I)
    {
        $I->wantTo('Update a webhook');

        $webhook = Webhook::factory()-> create([
            'name' => 'Original Webhook',
            'url' => 'https://original.com/webhook',
            'type' => [
                'event',
            ],
        ]);

        $data = [
            'name' => 'Updated Webhook',
            'url' => 'https://updated.com/webhook',
            'type' => [
                'notification',
            ],
        ];

        $I->sendPATCH('/webhooks/' . $webhook->id, $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse(), true);
        $I->assertEquals('success', $response['status']);
        $I->assertEquals('Updated Webhook', $response['payload']['name']);
        $I->assertEquals('https://updated.com/webhook', $response['payload']['url']);
    }

    /** @test */
    public function deleteWebhook(ApiTester $I)
    {
        $I->wantTo('Delete a webhook');

        $webhook = Webhook::factory()->create([
            'name' => 'To be deleted',
            'url' => 'https://delete.com/webhook',
            'type' => [
                'event',
            ],
        ]);

        $I->sendDELETE('/webhooks/' . $webhook->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse(), true);
        $I->assertEquals('success', $response['status']);
    }
}
