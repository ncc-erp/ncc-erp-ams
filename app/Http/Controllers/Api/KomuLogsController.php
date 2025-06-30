<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KomuMessageLog;
use Illuminate\Http\Request;
use App\Http\Transformers\KomuLogsTransformer;
use App\Helpers\Helper;

class KomuLogsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', KomuMessageLog::class);
        $komu = KomuMessageLog::select(
            ['id', 'send_to', 'message', 'created_at', 'updated_at', 'system_response', 'status', 'creator_id', 'company_id']
        );
        $allowed_columns = [
            'id',
            'send_to',
            'message',
            'created_at',
            'updated_at',
            'system_response',
            'status',
            'creator_id',
            'company_id',
        ];
        if ($request->input('deleted') == 'true') {
            $komu->onlyTrashed();
        }
        $komu_logs = $komu;

        if ($request->filled('search')) {
            $search = $request->input('search');
            $komu_logs = $komu->where(function ($query) use ($search) {
                $query->where('send_to', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }
        if($request->filled('date_from') && $request->filled('date_to')) {
            $date_from = $request->input('date_from') . ' 00:00:00';
            $date_to = $request->input('date_to') . ' 23:59:59';
            $komu_logs = $komu_logs->whereBetween('created_at', [$date_from, $date_to]);
        }

        if($request->filled('status')) {
            $status = $request->input('status');
            if (is_array($status)) {
                $komu_logs = $komu_logs->whereIn('status', $status);
            } else {
                $komu_logs = $komu_logs->where('status', $status);
            }
        }

        $offset = (($komu_logs) && ($request->get('offset') > $komu_logs->count())) ? $komu_logs->count() : $request->get('offset', 0);

        ((config('app.max_results') >= $request->input('limit')) && ($request->filled('limit'))) ? $limit = $request->input('limit') : $limit = config('app.max_results');

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';
        $komu_logs->orderBy($sort, $order);

        $total = $komu_logs->count();
        $komu_logs = $komu_logs->skip($offset)->take($limit)->get();

        return (new KomuLogsTransformer)->transformKomuLogs($komu_logs, $total);
    }

    public function getTotalDetail(Request $request)
    {
        $this->authorize('view', KomuMessageLog::class);

        $query = KomuMessageLog::query();

        if ($request->input('deleted') == 'true') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('send_to', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $date_from = $request->input('date_from') . ' 00:00:00';
            $date_to = $request->input('date_to') . ' 23:59:59';
            $query->whereBetween('created_at', [$date_from, $date_to]);
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        $total = $query->count();


        $data = [
            'status' => 'success',
            'payload' => [
                [
                    'name' => "Komu Message Logs",
                    'total' => $total,
                ]
            ],
            'message' => null,
        ];
        return response()->json(Helper::formatStandardApiResponse($data['status'], $data['payload'], $data['message']));
    }

    public function destroy($id)
    {
        $this->authorize('delete', KomuMessageLog::class);

        $webhook = KomuMessageLog::findOrFail($id);
        $webhook->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/komu_logs/message.delete.success')));
    }
}