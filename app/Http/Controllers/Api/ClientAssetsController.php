<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AssetException;
use App\Http\Transformers\AssetsTransformer;
use App\Services\ClientAssetService;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetCheckoutRequest;
use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\ImageUploadRequest;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use App\Models\WebhookLog;
class ClientAssetsController extends Controller
{
    private $clientAssetService;

    public function __construct(ClientAssetService $clientAssetService)
    {
        $this->clientAssetService = $clientAssetService;
    }

    public function index(Request $request)
    {
        $this->authorize('index', Asset::class);
        $result = $this->clientAssetService->getListAssets($request->all());
        return (new AssetsTransformer)->transformAssets($result['assets'], $result['total']);
    }

    public function getTotalDetail(Request $request)
    {
        $this->authorize('index', Asset::class);
        $response = $this->clientAssetService->getTotalDetail($request->all());

        if ($request->has('IS_EXPIRE_PAGE') && $request->get('IS_EXPIRE_PAGE')) {
            $expire_asset = $this->assetExpiration($request);
        }

        if (isset($expire_asset)) {
            $response = $this->clientAssetService->getTotalDetailExpire($expire_asset);
        }

        return response()->json(
            Helper::formatStandardApiResponse(
                'success',
                $response,
                null
            )
        );
    }

    public function assetExpiration(Request $request)
    {
        $this->authorize('index', Asset::class);
        $result = $this->clientAssetService->getListAssets($request->all());

        $expiration = Carbon::now()->addDays(30)->startOfDay()->toDateTimeString();

        $data = [];
        $data['total'] = 0;
        $assets =  (new AssetsTransformer)->transformAssets($result['assets'], $result['total']);

        foreach ($assets['rows'] as $asset) {
            if (!$asset['warranty_expires']) continue;
            if ((new Carbon($asset['warranty_expires']['date']))->lte($expiration)) {
                $data['rows'][] = $asset;
                $data['total'] += 1;
            }
        }
        return $data;
    }

    public function store(ImageUploadRequest $request)
    {
        $this->authorize('create', Asset::class);

        try {
            $asset = $this->clientAssetService->store($request->all());

            if (!$asset) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $asset,
                __('admin/hardware/message.create.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(ImageUploadRequest $request, $id)
    {
        $this->authorize('update', Asset::class);

        try {
            $assets = Asset::find($id);
            $asset = $this->clientAssetService->update($request->all(), $id);

            if (!$asset) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }
            if ($request->assigned_status === config('enum.assigned_status.ACCEPT')) {
                if ($assets->withdraw_from) {
                    $checkinWebhooks = Webhook::whereJsonContains('type', 'CONFIRM_CHECKIN')
                        ->get();
                    foreach ($checkinWebhooks as $checkinWebhook) {
                        $this->sendNotification("CONFIRM_CHECKIN",$assets, $checkinWebhook, true, true);
                    }
                } else {
                    $checkoutWebhooks = Webhook::whereJsonContains('type', 'CONFIRM_CHECKOUT')
                        ->get();
                    foreach ($checkoutWebhooks as $checkoutWebhook) {
                        $this->sendNotification("CONFIRM_CHECKOUT",$assets, $checkoutWebhook, false, true);
                    }
                }
            } elseif ($request->assigned_status === config('enum.assigned_status.REJECT')) {
                if ($assets->withdraw_from) {
                    $checkinWebhooks = Webhook::whereJsonContains('type', 'REJECT_CHECKIN')
                        ->get();
                    foreach ($checkinWebhooks as $checkinWebhook) {
                        $this->sendNotification("REJECT_CHECKIN",$assets, $checkinWebhook, true, false, true);
                    }
                } else {
                    $checkoutWebhooks = Webhook::whereJsonContains('type', 'REJECT_CHECKOUT')
                        ->get();
                    foreach ($checkoutWebhooks as $checkoutWebhook) {
                        $this->sendNotification("REJECT_CHECKOUT",$assets, $checkoutWebhook, false, false, true);
                    }
                }
            }


            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $asset,
                __('admin/hardware/message.update.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function multiUpdate(ImageUploadRequest $request)
    {
        $this->authorize('update', Asset::class);

        try {
            $assets = $this->clientAssetService->update($request->all());

            if (!$assets) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $assets,
                __('admin/hardware/message.update.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy($id)
    {
        $this->authorize('delete', Asset::class);

        try {
            $asset = $this->clientAssetService->destroy($id);

            if (!$asset) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                null,
                __('admin/hardware/message.delete.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function multiCheckout(AssetCheckoutRequest $request)
    {
        $this->authorize('checkout', Asset::class);

        try {
            $assets = $this->clientAssetService->checkout($request->all());

            if (!$assets) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $assets['payload'],
                __('admin/hardware/message.checkout.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function multiCheckin(Request $request)
    {
        $this->authorize('checkin', Asset::class);

        try {
            $assets = $this->clientAssetService->checkin($request->all());

            if (!$assets) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $assets['payload'],
                __('admin/hardware/message.checkin.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function checkin(Request $request, $asset_id)
    {
        $this->authorize('checkin', Asset::class);

        try {
            $assets = Asset::find($asset_id);
            $asset = $this->clientAssetService->checkin($request->all(), $asset_id);

            if (!$asset) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }
            $checkinWebhooks = Webhook::whereJsonContains('type', 'CHECKIN_CLIENT_ASSET')
                ->get();
            foreach ($checkinWebhooks as $checkinWebhook) {
                $this->sendNotification("CHECKIN_CLIENT_ASSET",$assets, $checkinWebhook, true);
            }
            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $asset['payload'],
                __('admin/hardware/message.checkin.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function checkout(AssetCheckoutRequest $request, $asset_id)
    {
        $this->authorize('checkout', Asset::class);

        try {
            $assets = Asset::find($asset_id);
            $asset = $this->clientAssetService->checkout($request->all(), $asset_id);

            if (!$asset) {
                throw new AssetException(__('general.server_error'), "error", 500);
            }
            $checkoutWebhooks = Webhook::whereJsonContains('type', 'CHECKOUT_CLIENT_ASSET')
                ->get();
            foreach ($checkoutWebhooks as $checkoutWebhook) {
                $this->sendNotification("CHECKOUT_CLIENT_ASSET",$assets, $checkoutWebhook);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $asset['payload'],
                __('admin/hardware/message.checkout.success')
            ));
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    private function sendNotification($type, $item, $webhook, $isCheckin = false, $isConfirmed = false, $isRejected = false)
    {


        $messageText = "[Client Asset] {$item->name} is requested to check out.";
        if ($isCheckin) {
            $messageText = "[Client Asset] {$item->name} is requested to check in.";
        }
        if ($isConfirmed && $isCheckin) {
            $messageText = "[Client Asset] {$item->name} is confirmed to check in.";
        }
        if ($isConfirmed && !$isCheckin) {
            $messageText = "[Client Asset] {$item->name} is confirmed to check out.";
        }
        if($isRejected && $isCheckin) {
            $messageText = "[Client Asset] {$item->name} is rejected to check in.";
        }
        if($isRejected && !$isCheckin) {
            $messageText = "[Client Asset] {$item->name} is rejected to check out.";
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

        $success = $response->successful();
        $status_message = $success ? 'Webhook sent successfully' : 'Webhook failed to send';

        WebhookLog::create([
            'webhook_id' => $webhook->id,
            'url' => $webhook->url,
            'message' => $messageText,
            'status_code' => $response->status(),
            'response' => $status_message,
            'asset' => $item->name,
            'type' => $type,
        ]);
    }
}
