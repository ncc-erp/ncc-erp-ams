<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Webhook;
class WebhookLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\WebhookLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'webhook_id' => 1,
            'url' => $this->faker->url(),
            'message' => $this->faker->sentence(),
            'status_code' => $this->faker->numberBetween(200, 500),
            'response' => $this->faker->text(),
            'asset' => $this->faker->word(),
            'type' => $this->faker->randomElement(['ASSET_MAINTENANCE', 'CONSUMABLE_MAINTENANCE']),
            'created_at' => $this->faker->dateTimeThisYear(),
            'updated_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}