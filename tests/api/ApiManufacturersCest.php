<?php

use App\Http\Transformers\ManufacturersTransformer;
use App\Models\Manufacturer;
use App\Models\User;

class ApiManufacturersCest
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
    public function indexManufacturers(ApiTester $I)
    {
        $I->wantTo('Get a list of manufacturers');

        // call
        $I->sendGET('/manufacturers?order_by=id&limit=10');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse(), true);
        // sample verify
        $manufacturer = Manufacturer::withCount('assets as assets_count', 'accessories as accessories_count', 'consumables as consumables_count', 'licenses as licenses_count')
            ->orderByDesc('created_at')->take(10)->get()->shuffle()->first();

        $I->seeResponseContainsJson($I->removeTimestamps((new ManufacturersTransformer)->transformManufacturer($manufacturer)));
    }

    /** @test */
    public function createManufacturer(ApiTester $I, $scenario)
    {
        $I->wantTo('Create a new manufacturer');

        $temp_manufacturer = Manufacturer::factory()->apple()->make([
            'name' => 'Test Manufacturer Tag',
        ]);

        // setup
        $data = [
            'name' => $temp_manufacturer->name,
            'support_email' => $temp_manufacturer->support_email,
            'support_phone' => $temp_manufacturer->support_phone,
            'support_url' => $temp_manufacturer->support_url,
            'url' => $temp_manufacturer->url,
        ];

        // create
        $I->sendPOST('/manufacturers', $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }

    /** @test */
    public function updateManufacturerWithPatch(ApiTester $I, $scenario)
    {
        $I->wantTo('Update an manufacturer with PATCH');

        // create
        $manufacturer = Manufacturer::factory()->apple()
            ->create([
                'name' => 'Original Manufacturer Name',
        ]);
        $I->assertInstanceOf(Manufacturer::class, $manufacturer);

        $temp_manufacturer = Manufacturer::factory()->apple()->make([
            'name' => 'updated manufacturer name',
        ]);

        $data = [
            'name' => $temp_manufacturer->name,
            'support_email' => $temp_manufacturer->support_email,
            'support_phone' => $temp_manufacturer->support_phone,
            'support_url' => $temp_manufacturer->support_url,
            'url' => $temp_manufacturer->url,
        ];

        $I->assertNotEquals($manufacturer->name, $data['name']);

        // update
        $I->sendPATCH('/manufacturers/'.$manufacturer->id, $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse());

        $I->assertEquals('success', $response->status);
        $I->assertEquals(trans('admin/manufacturers/message.update.success'), $response->messages);
        $I->assertEquals($manufacturer->id, $response->payload->id); // manufacturer id does not change
        $I->assertEquals($temp_manufacturer->name, $response->payload->name); // manufacturer name updated
        // Some manual copying to compare against
        $temp_manufacturer->created_at = Carbon::parse($response->payload->created_at);
        $temp_manufacturer->updated_at = Carbon::parse($response->payload->updated_at);
        $temp_manufacturer->id = $manufacturer->id;

        // verify
        $I->sendGET('/manufacturers/'.$manufacturer->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson((new ManufacturersTransformer)->transformManufacturer($temp_manufacturer));
    }

    /** @test */
    public function deleteManufacturerTest(ApiTester $I, $scenario)
    {
        $I->wantTo('Delete an manufacturer');

        // create
        $manufacturer = Manufacturer::factory()->apple()->create([
            'name' => 'Soon to be deleted',
        ]);
        $I->assertInstanceOf(Manufacturer::class, $manufacturer);

        // delete
        $I->sendDELETE('/manufacturers/'.$manufacturer->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse());
        $I->assertEquals('success', $response->status);
        $I->assertEquals(trans('admin/manufacturers/message.delete.success'), $response->messages);

        // verify, expect a 200
        $I->sendGET('/manufacturers/'.$manufacturer->id);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
