<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Location;
use App\Models\Supplier;
use Tests\Unit\BaseTest;
use App\Models\AssetModel;
use App\Models\Asset;
use App\Models\Statuslabel;

class CategoryTest extends BaseTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testFailsEmptyValidation()
    {
        // An Asset requires a name, a qty, and a category_id.
        $a = Category::create();
        $this->assertFalse($a->isValid());

        $fields = [
            'name' => 'name',
            'category_type' => 'category type',
        ];
        $errors = $a->getErrors();
        foreach ($fields as $field => $fieldTitle) {
            $this->assertEquals($errors->get($field)[0], "The ${fieldTitle} field is required.");
        }
    }

    public function testACategoryCanHaveAssets()
    {
        $category = Category::factory()->create(
            [
                'name' => 'test for category',
                'category_type' => 'asset',
            ]
        );
        $status_label = Statuslabel::factory()->pending()->create();
        $supplier = Supplier::factory()->create();
        $location = Location::factory()->create();
        // Generate 5 models via factory
        $models =  AssetModel::factory()
            ->mbp13Model()
            ->count(5)
            ->create(
                [
                    'category_id' => $category->id
                ]
            );



        // Loop through the models and create 2 assets in each model
        $models->each(function ($model) use ($status_label, $supplier, $location) {
            $asset = Asset::factory()
                ->count(2)
                ->create(
                    [
                        'model_id' => $model->id,
                        'status_id' => $status_label->id,
                        'supplier_id' => $supplier->id,
                        'rtd_location_id' => $location->id,
                        'assigned_status' => 1
                    ]
                );

        });

        $this->assertCount(5, $category->models);
        $this->assertCount(5, $category->models);
        $this->assertEquals(10, $category->itemCount());
    }


    // public function testACategoryCanHaveAccessories()
    // {
    //     $category = Category::factory()->assetDesktopCategory()->create();
    //     Accessory::factory()->count(5)->appleBtKeyboard()->create(
    //         [
    //             'category_id' => $category->id
    //         ]
    //     );

    //     $this->assertCount(5, $category->accessories);
    //     $this->assertEquals(5, $category->itemCount());
    // }

    // public function testACategoryCanHaveConsumables()
    // {
    //     $category = $this->createValidCategory('consumable-paper-category');
    //     \App\Models\Consumable::factory()->count(5)->cardstock()->create(['category_id' => $category->id]);
    //     $this->assertCount(5, $category->consumables);
    //     $this->assertEquals(5, $category->itemCount());
    // }

    // public function testACategoryCanHaveComponents()
    // {
    //     $category = $this->createValidCategory('component-ram-category');
    //     \App\Models\Component::factory()->count(5)->ramCrucial4()->create(['category_id' => $category->id]);
    //     $this->assertCount(5, $category->components);
    //     $this->assertEquals(5, $category->itemCount());
    // }
}
