<?php

namespace App\Console\Commands;

use App\Models\Consumable;
use Illuminate\Console\Command;
use App\Models\Asset;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\WebhookLog;

class SendMaintenanceNotifications extends Command
{
    protected $signature = 'maintenance:notify';
    protected $description = 'Send notification to webhook when asset or consumable maintenance is due';

    public function handle()
    {
        $today = Carbon::today();

        $assets = Asset::whereDate('maintenance_date', $today)
            ->whereNotNull('webhook_id')
            ->with('webhook', 'model.category')
            ->get();

        foreach ($assets as $asset) {
            $this->sendNotification($asset, 'ASSET_MAINTENANCE');
        }

        $consumables = Consumable::whereDate('maintenance_date', $today)
            ->whereNotNull('webhook_id')
            ->with('webhook', 'category')
            ->get();
        foreach ($consumables as $consumable) {
            $this->sendNotification($consumable, 'CONSUMABLE_MAINTENANCE');
        }
        return 0;
    }
    private function sendNotification($item, $webhookType)
    {
        if (
            $item->webhook &&
            $item->webhook->url &&
            is_array($item->webhook->type) &&
            in_array($webhookType, $item->webhook->type)
        ) {
            $categoryName = $this->getCategoryName($item);

            $messageText = "{$item->type} {$item->name} - {$categoryName} is due for maintenance today.";            $messageText = "{$item->type} {$item->name} - {$categoryName} is due for maintenance today.";

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

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($item->webhook->url, $payload);

            $success = $response->successful();
            $status_message = $success ? 'Webhook sent successfully' : 'Webhook failed to send';

            WebhookLog::create([
                'webhook_id' => $item->webhook->id,
                'url' => $item->webhook->url,
                'message' => $messageText,
                'status_code' => $response->status(),
                'response' => $status_message,
                'asset' => $item->name,
                'type' => $webhookType
            ]);

            $this->info("Notification sent for {$item->type}: {$item->name}");
        }
    }
    private function getCategoryName($item): string
    {
        if ($item instanceof Asset) {
            return optional($item->model->category)->name ?? 'N/A';
        }

        if ($item instanceof Consumable) {
            return optional($item->category)->name ?? 'N/A';
        }

        return 'N/A';
    }

}
