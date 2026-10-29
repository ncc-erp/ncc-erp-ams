<?php

namespace Tests\Feature\UpgradeExtra;

use App\Models\Accessory;
use App\Models\Category;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Manufacturer;
use App\Models\User;
use Tests\Feature\Upgrade\UpgradeTestCase;

class FormPagesRenderTest extends UpgradeTestCase
{
    public function testAdminFormPagesRenderWithTheCollectiveHtmlReplacement(): void
    {
        $admin = User::query()->first();
        $this->actingAs($admin);

        $category = Category::factory()->create(['category_type' => 'asset']);
        $licenseCategory = Category::factory()->create(['category_type' => 'license']);
        $accessoryCategory = Category::factory()->create(['category_type' => 'accessory']);
        $consumableCategory = Category::factory()->create(['category_type' => 'consumable']);
        $manufacturer = Manufacturer::factory()->create();
        $supplier = $this->makeSupplier();
        $model = $this->makeAssetModel();
        $location = $this->makeLocation();
        $license = License::factory()->create([
            'category_id' => $licenseCategory->id,
            'supplier_id' => $supplier->id,
        ]);
        $accessory = Accessory::factory()->create([
            'name' => 'Upgrade Gate Accessory',
            'qty' => 2,
            'min_amt' => 1,
            'category_id' => $accessoryCategory->id,
            'manufacturer_id' => $manufacturer->id,
            'supplier_id' => $supplier->id,
            'location_id' => $location->id,
        ]);
        $consumable = Consumable::factory()->create([
            'name' => 'Upgrade Gate Consumable',
            'qty' => 2,
            'min_amt' => 1,
            'category_id' => $consumableCategory->id,
            'manufacturer_id' => $manufacturer->id,
            'supplier_id' => $supplier->id,
        ]);
        $component = Component::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'company_id' => $admin->company_id,
        ]);

        $pages = [
            ['hardware.create', []],
            ['users.create', []],
            ['locations.create', []],
            ['models.create', []],
            ['categories.create', []],
            ['suppliers.create', []],
            ['licenses.create', []],
            ['accessories.create', []],
            ['consumables.create', []],
            ['components.create', []],
            ['settings.general.index', []],
            ['/hardware/'.$this->makeAsset()->id.'/edit', []],
            ['/users/'.$admin->id.'/edit', []],
            ['/locations/'.$location->id.'/edit', []],
            ['/models/'.$model->id.'/edit', []],
            ['/categories/'.$category->id.'/edit', []],
            ['/suppliers/'.$supplier->id.'/edit', []],
            ['/licenses/'.$license->id.'/edit', []],
            ['/accessories/'.$accessory->id.'/edit', []],
            ['/consumables/'.$consumable->id.'/edit', []],
            ['/components/'.$component->id.'/edit', []],
        ];

        foreach ($pages as [$routeName, $parameters]) {
            $url = str_starts_with($routeName, '/') ? $routeName : route($routeName, $parameters);
            $response = $this->get($url);

            $this->assertSame(200, $response->status(), $url);
            $this->assertStringContainsString('<form', $response->getContent(), $routeName);
        }
    }
}
