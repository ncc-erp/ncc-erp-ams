<?php

use App\Http\Transformers\StatuslabelsTransformer;
use App\Models\Statuslabel;
use App\Models\User;

class ApiStatusLabelsCest
{
    protected $user;
    protected $timeFormat;

    public function _before(ApiTester $I)
    {
        $this->user = User::factory()->create();
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amBearerAuthenticated($I->getToken($this->user));
    }

    /** @test */
    public function indexStatuslabels(ApiTester $I)
    {
        $I->wantTo('Get a list of statuslabels');

        // call
        $I->sendGET('/statuslabels?limit=10');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        // sample verify
        $statuslabel = Statuslabel::orderByDesc('created_at')
            ->withCount('assets as assets_count')
            ->take(10)->get()->shuffle()->first();
        $I->seeResponseContainsJson($I->removeTimestamps((new StatuslabelsTransformer)->transformStatuslabel($statuslabel)));
    }

    /** @test */
    public function createStatuslabel(ApiTester $I, $scenario)
    {
        $I->wantTo('Create a new statuslabel');

        $temp_statuslabel = Statuslabel::factory()->make([
            'name' => 'Test Statuslabel Tag',
        ]);

        // setup
        $data = [
            'name' => $temp_statuslabel->name,
            'archived' => $temp_statuslabel->archived,
            'deployable' => $temp_statuslabel->deployable,
            'notes' => $temp_statuslabel->notes,
            'pending' => $temp_statuslabel->pending,
            'type' => 'deployable',
        ];

        // create
        $I->sendPOST('/statuslabels', $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }

    /** @test */
    public function updateStatuslabelWithPatch(ApiTester $I, $scenario)
    {
        $I->wantTo('Update an statuslabel with PATCH');

        // create
        $statuslabel = Statuslabel::factory()->readyToDeploy()->create([
            'name' => 'Original Statuslabel Name',
            'id' => 98
        ]);
        $I->assertInstanceOf(Statuslabel::class, $statuslabel);
        $temp_statuslabel = Statuslabel::factory()->pending()->make([
            'name' => 'updated statuslabel name',
            'type' => 'pending',
            'id' => 99
        ]);
        $data = [
            'name' => $temp_statuslabel->name,
            'archived' => $temp_statuslabel->archived,
            'deployable' => $temp_statuslabel->deployable,
            'notes' => $temp_statuslabel->notes,
            'pending' => $temp_statuslabel->pending,
            'type' => $temp_statuslabel->type,
            'default_label' => $temp_statuslabel->default_label
        ];
        $I->assertNotEquals($statuslabel->name, $data['name']);
        // update
        $I->sendPATCH('/statuslabels/' . $statuslabel->id, $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse());
        
        $I->assertEquals('success', $response->status);
        $I->assertEquals(trans('admin/statuslabels/message.update.success'), $response->messages);
        $I->assertEquals($statuslabel->id, $response->payload->id); // statuslabel id does not change
        $I->assertEquals($temp_statuslabel->name, $response->payload->name); // statuslabel name updated
        // Some manual copying to compare against
        $temp_statuslabel->created_at = $response->payload->created_at;
        $temp_statuslabel->updated_at = $response->payload->updated_at;
        $temp_statuslabel->id = $statuslabel->id;

        // verify
        $I->sendGET('/statuslabels/' . $statuslabel->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson((new StatuslabelsTransformer)->transformStatuslabel($temp_statuslabel));
    }

    /** @test */
    public function deleteStatuslabelTest(ApiTester $I, $scenario)
    {
        $I->wantTo('Delete an statuslabel');

        // create
        $statuslabel = Statuslabel::factory()->create([
            'name' => 'Soon to be deleted',
        ]);
        $I->assertInstanceOf(Statuslabel::class, $statuslabel);

        // delete
        $I->sendDELETE('/statuslabels/' . $statuslabel->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse());
        $I->assertEquals('success', $response->status);
        $I->assertEquals(trans('admin/statuslabels/message.delete.success'), $response->messages);

        // verify, expect a 200
        $I->sendGET('/statuslabels/' . $statuslabel->id);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    public function getAssetCountByStatuslabelTest(ApiTester $I)
    {
        $I->wantTo("Test get assest count by status label");

        $statuslabels = Statuslabel::withCount('assets')->get();
        $labels = [];
        foreach ($statuslabels as $statuslabel) {
            if ($statuslabel->assets_count > 0) {
                $labels[] = $statuslabel->name.' ('.number_format($statuslabel->assets_count).')';
            }
        }
        // call
        $I->sendGET('/statuslabels/assets');
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "labels" => $labels
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function getAssetListByStatuslabelTest(ApiTester $I)
    {
        $I->wantTo("Test get assest list by status label");
        $status_label = Statuslabel::all()->random(1)->first();
        // call
        $I->sendGET('/statuslabels/' . $status_label->id . '/assetlist?limit=20&sort=id&order=desc');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }
}
