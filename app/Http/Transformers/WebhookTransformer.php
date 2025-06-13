<?php

namespace App\Http\Transformers;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Gate;
use App\Helpers\Helper;


class WebhookTransformer
{
    public function transformWebhooks(Collection $webhooks, $total)
    {
        $array = [];
        foreach ($webhooks as $webhook) {
            $array[] = $this->transformWebhook($webhook);
        }
        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformWebhook(Webhook $webhook = null)
    {
        if ($webhook) {
            $array = [
                'id' => (int) $webhook->id,
                'name' => e($webhook->name),
                'url' => e($webhook->url),
                'created_at' => Helper::getFormattedDateObject($webhook->created_at, 'datetime'),
                'updated_at' => Helper::getFormattedDateObject($webhook->updated_at, 'datetime'),
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