<?php

namespace App\Http\Transformers;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use App\Helpers\Helper;


class WebhookTransformer
{
    /**
     * @OA\Schema(
     *     schema="AvailableWebhookActions",
     *     type="object",
     *     @OA\Property(property="update", type="boolean", example=true),
     *     @OA\Property(property="restore", type="boolean", example=false),
     *     @OA\Property(property="delete", type="boolean", example=true)
     * )
     * 
     * @OA\Schema(
     *     schema="Webhook",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Asset Checkout Webhook"),
     *     @OA\Property(property="url", type="string", example="https://webhook.mezon.ai/webhooks/1957415919202340864/MTc1NTQ5MDYyODk0..."),
     *     @OA\Property(
     *         property="created_at", 
     *         ref="#/components/schemas/DateObject"
     *     ),
     *     @OA\Property(
     *         property="updated_at", 
     *         ref="#/components/schemas/DateObject"
     *     ),
     *     @OA\Property(
     *         property="type", 
     *         type="array", 
     *         @OA\Items(type="string", example="CHECKOUT_TOOL"),
     *         description="Array of webhook event types this webhook subscribes to"
     *     ),
     *     @OA\Property(
     *         property="available_actions", 
     *         ref="#/components/schemas/AvailableWebhookActions"
     *     )
     * )
     * 
     * @OA\Schema(
     *     schema="WebhooksResponse",
     *     type="object",
     *     description="Webhooks response format",
     *     @OA\Property(property="total", type="integer", example=10, description="Total number of webhooks"),
     *     @OA\Property(
     *         property="rows",
     *         type="array",
     *         description="Array of webhook items",
     *         @OA\Items(ref="#/components/schemas/Webhook")
     *     )
     * )
     *
     * /**
     * Transform a collection of webhooks for the API response
     *
     * @param  Collection $webhooks
     * @param  int $total
     * @return array
     */
    public function transformWebhooks(Collection $webhooks, $total)
    {
        $array = [];
        foreach ($webhooks as $webhook) {
            $array[] = $this->transformWebhook($webhook);
        }
        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    /**
     * Transform a single webhook for the API response
     *
     * @param  Webhook|null $webhook
     * @return array
     */
    public function transformWebhook(Webhook $webhook = null)
    {
        if ($webhook) {
            $array = [
                'id' => (int) $webhook->id,
                'name' => e($webhook->name),
                'url' => e($webhook->url),
                'created_at' => Helper::getFormattedDateObject($webhook->created_at, 'datetime'),
                'updated_at' => Helper::getFormattedDateObject($webhook->updated_at, 'datetime'),
                'type' => $webhook->type,
            ];

            $permissions_array['available_actions'] = [
                'update' => (($webhook->deleted_at == '') && (Gate::allows('update', Webhook::class))),
                'restore' => (($webhook->deleted_at != '') && (Gate::allows('create', Webhook::class))),
                'delete' => $webhook->isDeletable(),
            ];
        }
        $array += $permissions_array;

        return $array;
    }
}