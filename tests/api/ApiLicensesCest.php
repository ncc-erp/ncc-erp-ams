<?php

use App\Http\Transformers\LicensesTransformer;
use App\Models\Category;
use App\Models\Company;
use App\Models\Depreciation;
use App\Models\License;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Supplier;
use App\Models\User;

class ApiLicensesCest
{
    protected $license;
    protected $timeFormat;
    protected $user;

    public function _before(ApiTester $I)
    {
        $this->user = User::factory()->create();
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amBearerAuthenticated($I->getToken($this->user));
    }

    /** @test */
    public function indexLicenses(ApiTester $I)
    {
        $I->wantTo('Get a list of licenses');

        // call
        $filter = '?limit=10&sort=created_at'
            . '&supplier_id=' . Supplier::all()->random(1)->first()->id
            . '&location_id=' . Location::all()->random(1)->first()->id
            . '&manufacturer_id=' . Manufacturer::all()->random(1)->first()->id
            . '&company_id=' . Company::all()->random(1)->first()->id
            . '&name=' . 'Acrobat'
            . '&order_number=' . rand(1000000, 50000000)
            . '&purchase_order=' . rand(1000000, 50000000)
            . '&license_name=' . 'Acrobat'
            . '&category_id=' . Category::where('category_type','=','license')->first()->id
            . '&search=' . 'Acrobat'
            . '&product_key=' . rand(1000000, 50000000);
        $I->sendGET('/licenses' . $filter);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }

    /** @test */
    public function indexSelecteLicense(ApiTester $I)
    {
        $I->wantTo('Get a list of selected licenses');

        // call
        $I->sendGET('/licenses/selectlist',[
            'search' => 'Acrobat'
        ]);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }

    /** @test */
    public function createLicense(ApiTester $I, $scenario)
    {
        $I->wantTo('Create a new license');
        $category = Category::factory()->create(['category_type' => 'accessory']);
        $location = Location::factory()->create();
        $depreciation = Depreciation::factory()->create();
        $company = Company::factory()->create();
        $temp_license = License::factory()->acrobat()->make([
            'name' => 'Test License Name',
            'depreciation_id' => $depreciation->id,
            'company_id' => $company->id,
            'location_id' => $location->id,
            'category_id' => $category->id
        ]);

        // setup
        $data = [
            'company_id' => $temp_license->company_id,
            'depreciation_id' => $temp_license->depreciation_id,
            'expiration_date' => $temp_license->expiration_date,
            'license_email' => $temp_license->license_email,
            'license_name' => $temp_license->license_name,
            'maintained' => $temp_license->maintained,
            'manufacturer_id' => $temp_license->manufacturer_id,
            'name' => $temp_license->name,
            'notes' => $temp_license->notes,
            'order_number' => $temp_license->order_number,
            'purchase_cost' => $temp_license->purchase_cost,
            'purchase_date' => $temp_license->purchase_date,
            'purchase_order' => $temp_license->purchase_order,
            'reassignable' => $temp_license->reassignable,
            'seats' => $temp_license->seats,
            'serial' => $temp_license->serial,
            'supplier_id' => $temp_license->supplier_id,
            'termination_date' => $temp_license->termination_date,
            'category_id' => $temp_license->category_id
        ];

        // create
        $I->sendPOST('/licenses', $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }

    /** @test */
    public function updateLicenseWithPatch(ApiTester $I, $scenario)
    {
        $I->wantTo('Update a license with PATCH');

        // create
        $depreciation = Depreciation::factory()->create();
        $company = Company::factory()->create();
        $license = License::factory()->acrobat()->create([
            'name' => 'Original License Name',
            'depreciation_id' => $depreciation->id,
            'company_id' => $company->id
        ]);
        $I->assertInstanceOf(License::class, $license);

        $category = Category::factory()->create(['category_type' => 'accessory']);
        $location = Location::factory()->create();
        $depreciation = Depreciation::factory()->create();
        $company = Company::factory()->create();
        $temp_license = License::factory()->acrobat()->make([
            'name' => 'Test License Name',
            'depreciation_id' => $depreciation->id,
            'company_id' => $company->id,
            'location_id' => $location->id,
            'category_id' => $category->id
        ]);

        $data = [
            'company_id' => $temp_license->company_id,
            'depreciation_id' => $temp_license->depreciation_id,
            'expiration_date' => $temp_license->expiration_date,
            'license_email' => $temp_license->license_email,
            'license_name' => $temp_license->license_name,
            'maintained' => $temp_license->maintained,
            'manufacturer_id' => $temp_license->manufacturer_id,
            'name' => $temp_license->name,
            'notes' => $temp_license->notes,
            'order_number' => $temp_license->order_number,
            'purchase_cost' => $temp_license->purchase_cost,
            'purchase_date' => $temp_license->purchase_date,
            'purchase_order' => $temp_license->purchase_order,
            'reassignable' => $temp_license->reassignable,
            'seats' => $temp_license->seats,
            'serial' => $temp_license->serial,
            'supplier_id' => $temp_license->supplier_id,
            'category_id' => $temp_license->category_id,
            'termination_date' => $temp_license->termination_date,
        ];
        $temp_license->free_seats_count = $temp_license->seats;
        $I->assertNotEquals($license->name, $data['name']);

        // update
        $I->sendPATCH('/licenses/'.$license->id, $data);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse());
        $I->assertEquals('success', $response->status);
        $I->assertEquals(trans('admin/licenses/message.update.success'), $response->messages);
        $I->assertEquals($license->id, $response->payload->id); // license id does not change
        $I->assertEquals($temp_license->name, $response->payload->name); // license name
        $temp_license->created_at = $response->payload->created_at;
        $temp_license->updated_at = $response->payload->updated_at;
        $temp_license->id = $license->id;
        // verify
        $I->sendGET('/licenses/'.$license->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson((new LicensesTransformer)->transformLicense($temp_license));
    }

    /** @test */
    public function deleteLicenseWithUsersTest(ApiTester $I, $scenario)
    {
        $I->wantTo('Ensure a license with seats checked out cannot be deleted');

        // create
        $license = License::factory()->acrobat()->create([
            'name' => 'Soon to be deleted',
        ]);
        $licenseSeat = $license->freeSeat();
        $licenseSeat->assigned_to = $this->user->id;
        $licenseSeat->save();
        $I->assertInstanceOf(License::class, $license);

        // delete
        $I->sendDELETE('/licenses/'.$license->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse());
        $I->assertEquals('error', $response->status);
        $I->assertEquals(trans('admin/licenses/message.assoc_users'), $response->messages);
    }

    /** @test */
    public function deleteLicenseTest(ApiTester $I, $scenario)
    {
        $I->wantTo('Delete an license');

        // create
        $license = License::factory()->acrobat()->create([
            'name' => 'Soon to be deleted',
        ]);
        $I->assertInstanceOf(License::class, $license);

        // delete
        $I->sendDELETE('/licenses/'.$license->id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse());
        $I->assertEquals('success', $response->status);
        $I->assertEquals(trans('admin/licenses/message.delete.success'), $response->messages);
    }
}
