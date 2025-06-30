<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use App\Http\Transformers\WebhookLogsTransformer;
use App\Helpers\Helper;

class WebhookLogsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', WebhookLog::class);
        $webhook_log = WebhookLog::select(
            ['id','webhook_id', 'url', 'message', 'status_code', 'response', 'asset', 'created_at', 'updated_at', 'type']
        );
        $allowed_columns = [
            'id',
            'webhook_id',
            'url',
            'status_code',
            'response',
            'message',
            'created_at',
            'updated_at',
            'asset',
            'type'
        ];
        if ($request->input('deleted') == 'true') {
            $webhook_log->onlyTrashed();
        }
        $webhook_logs = $webhook_log;
        if ($request->filled('search')) {
            $search = $request->input('search');
            $webhook_logs = $webhook_logs->where(function ($query) use ($search) {
                $query->where('url', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('webhook', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $date_from = $request->input('date_from') . ' 00:00:00';
            $date_to = $request->input('date_to') . ' 23:59:59';
            $webhook_logs = $webhook_logs->whereBetween('created_at', [$date_from, $date_to]);
        }

        if ($request->filled('status_code')) {
            $status = $request->input('status_code');
            if (is_array($status)) {
                $webhook_logs = $webhook_logs->whereIn('status_code', $status);
            } else {
                $webhook_logs = $webhook_logs->where('status_code', $status);
            }
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            if (is_array($type)) {
                $webhook_logs = $webhook_logs->whereIn('type', $type);
            } else {
                $webhook_logs = $webhook_logs->where('type', $type);
            }
        }

        $offset = (($webhook_logs) && ($request->get('offset') > $webhook_logs->count())) ? $webhook_logs->count() : $request->get('offset', 0);

        ((config('app.max_results') >= $request->input('limit')) && ($request->filled('limit'))) ? $limit = $request->input('limit') : $limit = config('app.max_results');

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';
        $webhook_logs->orderBy($sort, $order);

        $total = $webhook_logs->count();
        $webhook_logs = $webhook_logs->skip($offset)->take($limit)->get();

        return (new WebhookLogsTransformer)->transformWebhookLogs($webhook_logs, $total);
    }

    public function getTotalDetail(Request $request)
    {
        $this->authorize('view', WebhookLog::class);

        $query = WebhookLog::query();

        if ($request->input('deleted') == 'true') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('webhook', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $date_from = $request->input('date_from') . ' 00:00:00';
            $date_to = $request->input('date_to') . ' 23:59:59';
            $query->whereBetween('created_at', [$date_from, $date_to]);
        }

        if ($request->filled('status_code')) {
            $status = $request->input('status_code');
            if (is_array($status)) {
                $query->whereIn('status_code', $status);
            } else {
                $query->where('status_code', $status);
            }
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            if (is_array($type)) {
                $query->whereIn('type', $type);
            } else {
                $query->where('type', $type);
            }
        }

        $total = $query->count();


        $data = [
            'status' => 'success',
            'payload' => [
                [
                    'name' => "Webhook Logs",
                    'total' => $total,
                ]
            ],
            'message' => null,
        ];
        return response()->json(Helper::formatStandardApiResponse($data['status'], $data['payload'], $data['message']));
    }

    public function destroy($id)
    {
        $this->authorize('delete', WebhookLog::class);

        $webhook = WebhookLog::findOrFail($id);
        $webhook->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/webhook_logs/message.delete.success')));
    }
}