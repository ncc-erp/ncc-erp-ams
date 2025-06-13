<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Webhook::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word(),
            'url' => $this->faker->url(),
            'type' => $this->faker->randomElement(['event', 'notification', 'alert']),
        ];
    }
}