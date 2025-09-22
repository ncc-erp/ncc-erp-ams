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
    /**
     * @OA\Get(
     *   tags={"Webhook"},
     *   path="/api/v1/webhooks",
     *   summary="Get list of webhooks",
     *   description="Returns list of registered webhooks",
     *   operationId="getWebhooks",
     *   security={{"bearerAuth":{}}},
     *   
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Search term to filter results by name or URL",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   
     *   @OA\Parameter(
     *     name="webhook_id",
     *     in="query",
     *     description="Limit results to a specific webhook ID",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="offset",
     *     in="query",
     *     description="Offset to start results from",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Number of results to return",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Column to sort results by",
     *     required=false,
     *     @OA\Schema(
     *       type="string",
     *       enum={"id", "name", "url", "created_at", "updated_at", "type"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="order",
     *     in="query",
     *     description="Sort order",
     *     required=false,
     *     @OA\Schema(
     *       type="string",
     *       enum={"asc", "desc"}
     *     )
     *   ),
     *   
     *   @OA\Parameter(
     *     name="deleted",
     *     in="query",
     *     description="Include deleted webhooks",
     *     required=false,
     *     @OA\Schema(type="string", enum={"true", "false"})
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(ref="#/components/schemas/WebhooksResponse")
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Server error"
     *   )
     * )
     */
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

    /**
     * @OA\Post(
     *   tags={"Webhook"},
     *   path="/api/v1/webhooks",
     *   summary="Create a new webhook",
     *   description="Creates a new webhook and returns the newly created webhook",
     *   operationId="createWebhook",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name", "url", "type"},
     *       @OA\Property(property="name", type="string", example="Asset Created Hook"),
     *       @OA\Property(property="url", type="string", example="https://example.com/webhook/asset-created"),
     *       @OA\Property(
     *         property="type",
     *         type="array",
     *         @OA\Items(type="string", example="CHECKOUT_ASSET"),
     *         description="Array of webhook event types this webhook subscribes to"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Webhook created successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(
     *         property="messages", 
     *         type="string", 
     *         example="Webhook was created successfully"
     *       ),
     *       @OA\Property(
     *         property="payload", 
     *         ref="#/components/schemas/Webhook"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad request"
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Unprocessable Entity"
     *   )
     * )
     */
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

    /**
     * @OA\Get(
     *   tags={"Webhook"},
     *   path="/api/v1/webhooks/{webhook}",
     *   summary="Get a single webhook",
     *   description="Returns a single webhook",
     *   operationId="getWebhookById",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="webhook",
     *     in="path",
     *     description="ID of webhook to return",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(ref="#/components/schemas/Webhook")
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Webhook not found"
     *   )
     * )
     */
    public function show($id)
    {
        $this->authorize('view', Webhook::class);
        $webhook = Webhook::findOrFail($id);
        return (new WebhookTransformer)->transformWebhook($webhook);

    }

    /**
     * @OA\Put(
     *   tags={"Webhook"},
     *   path="/api/v1/webhooks/{webhook}",
     *   summary="Update a webhook",
     *   description="Updates an existing webhook",
     *   operationId="updateWebhook",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="webhook",
     *     in="path",
     *     description="ID of webhook to update",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="Asset Updated Hook"),
     *       @OA\Property(property="url", type="string", example="https://example.com/webhook/asset-updated"),
     *       @OA\Property(
     *         property="type",
     *         type="array",
     *         @OA\Items(type="string", example="CHECKOUT_ASSET"),
     *         description="Array of webhook event types this webhook subscribes to"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Webhook updated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(
     *         property="messages", 
     *         type="string", 
     *         example="Webhook was updated successfully"
     *       ),
     *       @OA\Property(
     *         property="payload", 
     *         ref="#/components/schemas/Webhook"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad request"
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Webhook not found"
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Unprocessable Entity"
     *   )
     * )
     */
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

    /**
     * @OA\Delete(
     *   tags={"Webhook"},
     *   path="/api/v1/webhooks/{webhook}",
     *   summary="Delete a webhook",
     *   description="Deletes an existing webhook",
     *   operationId="deleteWebhook",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="webhook",
     *     in="path",
     *     description="ID of webhook to delete",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Webhook deleted successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(
     *         property="messages", 
     *         type="string", 
     *         example="Webhook was deleted successfully"
     *       ),
     *       @OA\Property(
     *         property="payload", 
     *         type="null"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Webhook not found"
     *   )
     * )
     */
    public function destroy($id)
    {
        $this->authorize('delete', Webhook::class);

        $webhook = Webhook::findOrFail($id);
        $webhook->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/webhook/message.delete.success')));
    }
}