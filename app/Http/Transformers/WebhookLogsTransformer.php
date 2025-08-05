<?php

namespace App\Http\Transformers;

use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Collection;
use Gate;
use App\Helpers\Helper;


class WebhookLogsTransformer
{
    public function transformWebhookLogs(Collection $webhookLogs, $total)
    {
        $array = [];
        foreach ($webhookLogs as $webhookLog) {
            $array[] = $this->transformWebhookLog($webhookLog);
        }
        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformWebhookLog(WebhookLog $webhooklog = null)
    {
        if ($webhooklog) {
            $array = [
                'id' => (int) $webhooklog->id,
                'webhook' => ($webhooklog->webhook) ? [
                    'id' => (int) $webhooklog->webhook->id,
                    'name' => e($webhooklog->webhook->name),
                ] : null,
                'url' => e($webhooklog->url),
                'message' => e($webhooklog->message),
                'status_code' => (int) $webhooklog->status_code,
                'response' => e($webhooklog->response),
                'asset' => e($webhooklog->asset),
                'created_at' => Helper::getFormattedDateObject($webhooklog->created_at, 'datetime'),
                'updated_at' => Helper::getFormattedDateObject($webhooklog->updated_at, 'datetime'),
                'type' => e($webhooklog->type),
            ];

            $permissions_array['available_actions'] = [
                'update' => (($webhooklog->deleted_at == '') && (Gate::allows('update', WebhookLog::class))),
                'restore' => (($webhooklog->deleted_at != '') && (Gate::allows('create', WebhookLog::class))),
                'delete' => $webhooklog->isDeletable(),
            ];
        }
        $array += $permissions_array;

        return $array;
    }
}