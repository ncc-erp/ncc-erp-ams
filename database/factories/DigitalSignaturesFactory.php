<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class DigitalSignaturesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'seri' =>  $this->faker->uuid,
            'supplier_id' => Supplier::all()->random()->id,
            'user_id' => 1,
            'assigned_status' => 0,
            'assigned_to' => null,
            'purchase_date' => $this->faker->dateTimeBetween('-1 years', 'now', date_default_timezone_get()),
            'expiration_date' => $this->faker->dateTimeBetween('now','now', date_default_timezone_get()),
            'purchase_cost' => $this->faker->randomFloat(2, '299.99', '2999.99'),
            'note'   => 'Created by DB seeder',
            'status_id' => 5,
            'category_id' => 19,
            'qty' => $this->faker->numberBetween(5, 10),
            'location_id' => 1,
            'warranty_months' => $this->faker->numberBetween(5, 10)

        ];
    }
}
