<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Factories related exclusively to modelling assets.
|
*/

// These are just for unit tests, not to generate data

class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $purchaseDate = $this->faker->dateTimeBetween('-1 years', 'now', date_default_timezone_get());
        $maintenanceDate = $this->faker->dateTimeBetween($purchaseDate, 'now', date_default_timezone_get());

        return [
            'name' => $this->faker->word,
            'rtd_location_id' => Location::all()->random()->id,
            'serial' => $this->faker->uuid,
            'status_id' => 5,
            'user_id' => 1,
            'asset_tag' => $this->faker->unixTime('now'),
            'notes' => 'Created by DB seeder',
            'purchase_date' => $purchaseDate,
            'purchase_cost' => $this->faker->randomFloat(2, 299.99, 2999.99),
            'order_number' => $this->faker->numberBetween(1000000, 50000000),
            'supplier_id' => Supplier::all()->random()->id,
            'requestable' => $this->faker->boolean(),
            'assigned_to' => null,
            'assigned_type' => null,
            'next_audit_date' => null,
            'last_checkout' => null,
            'maintenance_date' => $maintenanceDate,
            'maintenance_cycle' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function laptopMbp()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
            ];
        });
    }

    public function laptopMbpPending()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
                'status_id' => 2,
            ];
        });
    }

    public function laptopMbpArchived()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
                'status_id' => 3,
            ];
        });
    }

    public function laptopAir()
    {
        return $this->state(function () {
            return [
                'model_id' => 2,
            ];
        });
    }

    public function laptopSurface()
    {
        return $this->state(function () {
            return [
                'model_id' => 3,
            ];
        });
    }

    public function laptopXps()
    {
        return $this->state(function () {
            return [
                'model_id' => 4,
            ];
        });
    }

    public function laptopSpectre()
    {
        return $this->state(function () {
            return [
                'model_id' => 5,
            ];
        });
    }

    public function laptopZenbook()
    {
        return $this->state(function () {
            return [
                'model_id' => 6,
            ];
        });
    }

    public function laptopYoga()
    {
        return $this->state(function () {
            return [
                'model_id' => 7,
            ];
        });
    }

    public function desktopMacpro()
    {
        return $this->state(function () {
            return [
                'model_id' => 8,
            ];
        });
    }

    public function desktopLenovoI5()
    {
        return $this->state(function () {
            return [
                'model_id' => 9,
            ];
        });
    }

    public function desktopOptiplex()
    {
        return $this->state(function () {
            return [
                'model_id' => 10,
            ];
        });
    }

    public function confPolycom()
    {
        return $this->state(function () {
            return [
                'model_id' => 11,
            ];
        });
    }

    public function confPolycomcx()
    {
        return $this->state(function () {
            return [
                'model_id' => 12,
            ];
        });
    }

    public function tabletIpad()
    {
        return $this->state(function () {
            return [
                'model_id' => 13,
            ];
        });
    }

    public function tabletTab3()
    {
        return $this->state(function () {
            return [
                'model_id' => 14,
            ];
        });
    }

    public function phoneIphone11()
    {
        return $this->state(function () {
            return [
                'model_id' => 15,
            ];
        });
    }

    public function phoneIphone12()
    {
        return $this->state(function () {
            return [
                'model_id' => 16,
            ];
        });
    }

    public function ultrafine()
    {
        return $this->state(function () {
            return [
                'model_id' => 17,
            ];
        });
    }

    public function ultrasharp()
    {
        return $this->state(function () {
            return [
                'model_id' => 18,
            ];
        });
    }

    public function assignedToUser()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
                'assigned_to' => \App\Models\User::factory()->create()->id,
                'assigned_type' => \App\Models\User::class,
            ];
        });
    }

    public function assignedToLocation()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
                'assigned_to' => \App\Models\Location::factory()->create()->id,
                'assigned_type' => \App\Models\Location::class,
            ];
        });
    }

    public function assignedToAsset()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
                'assigned_to' => \App\Models\Asset::factory()->create()->id,
                'assigned_type' => \App\Models\Asset::class,
            ];
        });
    }

    public function requiresAcceptance()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
            ];
        });
    }

    public function deleted()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
                'deleted_at' => $this->faker->dateTime(),
            ];
        });
    }
}
