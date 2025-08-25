<?php

use App\Models\User;
use App\Models\Asset;
use App\Models\KomuMessageLog;

class ApiKomuLogsCest
{
    protected $user;

    public function _before(ApiTester $I)
    {
        $this->user = User::factory()->create();
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amBearerAuthenticated($I->getToken($this->user));
    }


    /** @test */
    public function indexKomuLogs(ApiTester $I)
    {
        $I->wantTo('Get a list of Komu logs');

        KomuMessageLog::factory()->count(3)->create();

        $I->sendGET('/komu-logs?order_by=id&limit=10');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /** @test */
    public function deleteKomuLogs(ApiTester $I)
    {
        $I->wantTo('Delete a Komu log');

        $komuLog = KomuMessageLog::factory()->create();
        $I->sendDelete("/komu-logs/{$komuLog->id}");
        $I->seeResponseCodeIs(200);

        $I->assertNotNull(KomuMessageLog::withTrashed()->find($komuLog->id)->deleted_at);
    }
}
