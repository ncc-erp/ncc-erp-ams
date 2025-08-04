<?php

use App\Models\User;
use App\Models\WebhookLog;

class ApiWebhookLogsCest
{
    protected $user;

    public function _before(ApiTester $I)
    {
        $this->user = User::factory()->create();
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amBearerAuthenticated($I->getToken($this->user));
    }


    /** @test */
    public function indexWebhookLogs(ApiTester $I)
    {
        $I->wantTo('Get a list of Webhook logs');

        WebhookLog::factory()->count(3)->create();

        $I->sendGET('/webhook-logs?order_by=id&limit=10');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /** @test */
    public function deleteWebhookLogs(ApiTester $I)
    {
        $I->wantTo('Delete a Webhook log');

        $webhookLog = WebhookLog::factory()->create();
        $I->sendDelete("/webhook-logs/{$webhookLog->id}");
        $I->seeResponseCodeIs(200);
    }
}
