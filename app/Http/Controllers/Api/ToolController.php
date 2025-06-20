<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\ToolsTransformer;
use App\Models\Tool;
use App\Services\ToolService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use App\Models\WebhookLog;

class ToolController extends Controller
{
    private $toolService;
    public function __construct(ToolService $toolService)
    {
        $this->toolService = $toolService;
    }

    public function index(Request $request)
    {
        $this->authorize('view', Tool::class);
        $data = $this->toolService->index($request->all());
        return (new ToolsTransformer)->transformTools($data['tools'], $data['total']);
    }

    public function getTotalDetail(Request $request)
    {
        $this->authorize('view', Tool::class);

        $data = $this->toolService->getTotalDetail($request->all());

        return response()->json(Helper::formatStandardApiResponse('success', $data, null));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Tool::class);
        try {
            $data = $this->toolService->store($request->all());

            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    $data,
                    trans('admin/tools/message.create.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Tool::class);
        try {
            $tool = Tool::find($id);
            $data = $this->toolService->update($request->all(), $id);
            if ($request->assigned_status === config('enum.assigned_status.ACCEPT')) {
                if ($tool->withdraw_from) {
                    $checkinWebhooks = Webhook::whereJsonContains('type', 'CONFIRM_CHECKIN')
                        ->get();
                    foreach ($checkinWebhooks as $checkinWebhook) {
                        $this->sendNotification($tool, $checkinWebhook, true, true);
                    }
                } else {
                    $checkoutWebhooks = Webhook::whereJsonContains('type', 'CONFIRM_CHECKOUT')
                        ->get();
                    foreach ($checkoutWebhooks as $checkoutWebhook) {
                        $this->sendNotification($tool, $checkoutWebhook, false, true);
                    }
                }
            } else {
                if ($tool->withdraw_from) {
                    $checkinWebhooks = Webhook::whereJsonContains('type', 'REJECT_CHECKIN')
                        ->get();
                    foreach ($checkinWebhooks as $checkinWebhook) {
                        $this->sendNotification($tool, $checkinWebhook, true, false, true);
                    }
                } else {
                    $checkoutWebhooks = Webhook::whereJsonContains('type', 'REJECT_CHECKOUT')
                        ->get();
                    foreach ($checkoutWebhooks as $checkoutWebhook) {
                        $this->sendNotification($tool, $checkoutWebhook, false, false, true);
                    }
                }
            }
            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $data,
                trans('admin/tools/message.update.success')
            ), Response::HTTP_OK);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function multiUpdate(Request $request)
    {
        $this->authorize('update', Tool::class);
        try {
            $tools = $request->get('tools');
            foreach ($tools as $id) {
                $data = $this->toolService->update($request->all(), $id);
            }
            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $data,
                trans('admin/tools/message.update.success')
            ), Response::HTTP_OK);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function destroy($id)
    {
        $tool = $this->toolService->getToolById($id);
        $this->authorize('delete', $tool);
        try {
            $data = $this->toolService->delete($id);
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    null,
                    trans('admin/tools/message.delete.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function checkout(Request $request, $tool_id)
    {
        $this->authorize('checkout', Tool::class);
        try {
            $tool = Tool::find($tool_id);
            $data = $this->toolService->checkout($request->all(), $tool_id);
            $checkoutWebhooks = Webhook::whereJsonContains('type', 'CHECKOUT_TOOL')
                ->get();
            foreach ($checkoutWebhooks as $checkoutWebhook) {
                $this->sendNotification($tool, $checkoutWebhook);
            }
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    $data,
                    trans('admin/tools/message.checkout.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }
    private function sendNotification($item, $webhook, $isCheckin = false, $isConfirmed = false, $isRejected = false)
    {

        $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is requested to check out.";
        if ($isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is requested to checked in.";
        }
        if($isConfirmed && !$isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is confirmed to check out.";
        }
        if($isConfirmed && $isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is confirmed to check in.";
        }
        if($isRejected && !$isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is rejected to check out.";
        }
        if($isRejected && $isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is rejected to check in.";
        }
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
        ])->post($webhook->url, $payload);

        WebhookLog::create([
            'webhook_id' => $webhook->id,
            'url' => $webhook->url,
            'payload' => $payload,
            'status_code' => $response->status(),
            'response' => $response->body(),
            'asset_id' => $item->id,
        ]);
    }

    public function multiCheckout(Request $request)
    {
        $this->authorize('checkout', Tool::class);
        try {
            $tools = request('tools');
            foreach ($tools as $tool_id) {
                $data = $this->toolService->checkout($request->all(), $tool_id);
            }
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    null,
                    trans('admin/tools/message.checkout.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function checkIn(Request $request, $tool_id)
    {
        $this->authorize('checkin', Tool::class);
        try {
            $tool = Tool::find($tool_id);
            $data = $this->toolService->checkin($request->all(), $tool_id);
            $checkinWebhooks = Webhook::whereJsonContains('type', 'CHECKIN_TOOL')
                ->get();
            foreach ($checkinWebhooks as $checkinWebhook) {
                $this->sendNotification($tool, $checkinWebhook, true);
            }
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    $data,
                    trans('admin/tools/message.checkin.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function multiCheckin(Request $request)
    {
        $this->authorize('checkin', Tool::class);
        try {
            $tools = request('tools');
            foreach ($tools as $tool_id) {
                $data = $this->toolService->checkin($request->all(), $tool_id);
            }
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    null,
                    trans('admin/tools/message.checkin.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function assign(Request $request)
    {
        $this->authorize('view', Tool::class);
        $data = $this->toolService->getToolAssignList($request->all());
        return (new ToolsTransformer)->transformTools($data['tools'], $data['total']);
    }
}
