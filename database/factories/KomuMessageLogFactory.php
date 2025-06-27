<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class KomuMessageLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\KomuMessageLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'send_to' => $this->faker->email(),
            'message' => $this->faker->sentence(),
            'system_response' => $this->faker->text(),
            'status' => $this->faker->randomElement([0, 1]),
            'creator_id' => $this->faker->numberBetween(1, 100),
            'company_id' => $this->faker->numberBetween(1, 50),
        ];
    }
}