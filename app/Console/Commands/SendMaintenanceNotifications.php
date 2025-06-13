<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SendMaintenanceNotifications extends Command
{
    protected $signature = 'maintenance:notify';
    protected $description = 'Send notification to webhook when asset maintenance is due';

    public function handle()
    {
        $today = Carbon::today();

        $assets = Asset::whereDate('maintenance_date', $today)
            ->whereNotNull('webhook_id')
            ->with('webhook', 'model.category')
            ->get();

        foreach ($assets as $asset) {
            if ($asset->webhook && $asset->webhook->url) {
                $messageText = "Asset {$asset->name} - {$asset->model->category->name} is due for maintenance today.";
                $payload = [
                    'type' => 'hook',
                    'message' => [
                        't' => $messageText,
                        'mk' => [
                            [
                                'type' => 'pre',
                                's' => 0,
                                'e' => strlen($messageText),
                            ]
                        ],
                    ],
                ];
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($asset->webhook->url, $payload);

                $this->info("Notification sent for asset: {$asset->name}");
            }
        }
        return 0;
    }
}
