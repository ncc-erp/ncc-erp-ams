<?php

namespace App\Http\Transformers;

use App\Models\KomuMessageLog;
use Illuminate\Database\Eloquent\Collection;
use Gate;
use App\Helpers\Helper;


class KomuLogsTransformer
{
    public function transformKomuLogs(Collection $komu_logs, $total)
    {
        $array = [];
        foreach ($komu_logs as $komu_log) {
            $array[] = $this->transformKomuLog($komu_log);
        }
        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformKomuLog(KomuMessageLog $komu_log = null)
    {
        if ($komu_log) {
            $array = [
                'id' => (int) $komu_log->id,
                'send_to' => e($komu_log->send_to),
                'message' => e($komu_log->message),
                'system_response' => e($komu_log->system_response),
                'status' => (int) $komu_log->status,
                'creator' => ($komu_log->creator) ? [
                    'id' => (int) $komu_log->creator->id,
                    'name' => e($komu_log->creator->username),
                ] : null,
                'company' => ($komu_log->company) ? [
                    'id' => (int) $komu_log->company->id,
                    'name' => e($komu_log->company->name),
                ] : null,
                'created_at' => Helper::getFormattedDateObject($komu_log->created_at, 'datetime'),
                'updated_at' => Helper::getFormattedDateObject($komu_log->updated_at, 'datetime'),
            ];

            $permissions_array['available_actions'] = [
                'update' => (($komu_log->deleted_at == '') && (Gate::allows('update', KomuMessageLog::class))),
                'restore' => (($komu_log->deleted_at != '') && (Gate::allows('create', KomuMessageLog::class))),
                'delete' => $komu_log->isDeletable(),
            ];
        }
        $array += $permissions_array;

        return $array;
    }
}