<?php

namespace Database\Seeders;

use App\Models\Webhook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WebhookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Webhook::truncate();
        Webhook::factory()->count(3)->create();
    }
}
