<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;
use App\Http\Transformers\WebhookTransformer;
use App\Helpers\Helper;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', Webhook::class);
        $webhooks = Webhook::select(
            ['id', 'name', 'url', 'created_at', 'updated_at', 'type']
        );
        $allowed_columns = [
            'id',
            'name',
            'url',
            'created_at',
            'updated_at',
            'type',
        ];
        if ($request->input('deleted') == 'true') {
            $webhooks->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $webhooks = $webhooks->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('webhook_id')) {
            $webhooks = $webhooks->where('id', '=', $request->input('webhook_id'));
        }

        $offset = (($webhooks) && ($request->get('offset') > $webhooks->count())) ? $webhooks->count() : $request->get('offset', 0);

        ((config('app.max_results') >= $request->input('limit')) && ($request->filled('limit'))) ? $limit = $request->input('limit') : $limit = config('app.max_results');

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';
        $webhooks->orderBy($sort, $order);

        $total = $webhooks->count();
        $webhooks = $webhooks->skip($offset)->take($limit)->get();

        return (new WebhookTransformer)->transformWebhooks($webhooks, $total);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Webhook::class);
        $webhook = new Webhook;
        $webhook->fill($request->all());
        if ($webhook->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $webhook, trans('admin/webhook/message.create.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $webhook->getErrors()), Response::HTTP_BAD_REQUEST);
    }

    public function show($id)
    {
        $this->authorize('view', Webhook::class);
        $webhook = Webhook::findOrFail($id);
        return (new WebhookTransformer)->transformWebhook($webhook);

    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Webhook::class);
        $webhook = Webhook::findOrFail($id);

        $webhook->fill($request->all());
        if ($webhook->save()) {
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    (new WebhookTransformer)->transformWebhook($webhook),
                    trans('admin/webhook/message.update.success')
                )
            );
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $webhook->getErrors()), Response::HTTP_BAD_REQUEST);
    }

    public function destroy($id)
    {
        $this->authorize('delete', Webhook::class);

        $webhook = Webhook::findOrFail($id);
        $webhook->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/webhook/message.delete.success')));
    }
}