<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\DigitalSignaturesTransformer;
use App\Models\DigitalSignatures;
use App\Models\User;
use App\Services\DigitalSignatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use App\Models\WebhookLog;

class DigitalSignaturesController extends Controller
{
    private $digitalSignatureService;
    public function __construct(
        DigitalSignatureService $digitalSignatureService
    ) {
        $this->digitalSignatureService = $digitalSignatureService;
    }
    public function index(request $request)
    {
        $this->authorize('view', DigitalSignatures::class);
        $data = $this->digitalSignatureService->index($request->all());
        return (new DigitalSignaturesTransformer())->transformSignatures($data['digital_signatures'], $data['total']);
    }

    public function getTotalDetail(request $request)
    {
        $this->authorize('view', DigitalSignatures::class);
        $data = $this->digitalSignatureService->getTotalDetail($request->all());
        return response()->json(Helper::formatStandardApiResponse($data['status'], $data['payload'], $data['message']));
    }

    public function store(Request $request)
    {
        $this->authorize('create', DigitalSignatures::class);
        try {
            $data = $this->digitalSignatureService->store($request->all());
            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $data,
                trans('admin/digital_signatures/message.create.success')
            ), Response::HTTP_OK);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function show(int $id)
    {
        $this->authorize('view', DigitalSignatures::class);
        $data = $this->digitalSignatureService->show($id);

        return (new DigitalSignaturesTransformer())->transformSignature($data);
    }

    public function update(Request $request, int $id)
    {
        $this->authorize('update', DigitalSignatures::class);
        $digital_signature = DigitalSignatures::find($id);
        $data = $this->digitalSignatureService->update($request->all(), $id);

        if ($request->assigned_status === config('enum.assigned_status.ACCEPT')) {
            if ($digital_signature->withdraw_from) {
                $checkinWebhooks = Webhook::whereJsonContains('type', 'CONFIRM_CHECKIN')
                    ->get();
                foreach ($checkinWebhooks as $checkinWebhook) {
                    $this->sendNotification("CONFIRM_CHECKIN",$digital_signature, $checkinWebhook, true, true);
                }
            } else {
                $checkoutWebhooks = Webhook::whereJsonContains('type', 'CONFIRM_CHECKOUT')
                    ->get();
                foreach ($checkoutWebhooks as $checkoutWebhook) {
                    $this->sendNotification("CONFIRM_CHECKOUT",$digital_signature, $checkoutWebhook, false, true);
                }
            }
        } else {
            if ($digital_signature->withdraw_from) {
                $checkinWebhooks = Webhook::whereJsonContains('type', 'REJECT_CHECKIN')
                    ->get();
                foreach ($checkinWebhooks as $checkinWebhook) {
                    $this->sendNotification("REJECT_CHECKIN",$digital_signature, $checkinWebhook, true, false, true);
                }
            } else {
                $checkoutWebhooks = Webhook::whereJsonContains('type', 'REJECT_CHECKOUT')
                    ->get();
                foreach ($checkoutWebhooks as $checkoutWebhook) {
                    $this->sendNotification("REJECT_CHECKOUT",$digital_signature, $checkoutWebhook, false, false, true);
                }
            }
        }
        return response()->json(Helper::formatStandardApiResponse(
            'success',
            $data,
            trans('admin/digital_signatures/message.update.success')
        ), Response::HTTP_OK);
    }

    public function destroy(int $id)
    {
        $this->authorize('delete', DigitalSignatures::class);
        try {
            $data = $this->digitalSignatureService->delete($id);
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    null,
                    trans('admin/digital_signatures/message.delete.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function checkout(Request $request, int $digital_signature_id)
    {
        $this->authorize('checkout', DigitalSignatures::class);
        try {
            $digital_signature = DigitalSignatures::find($digital_signature_id);
            $data = $this->digitalSignatureService->checkout($request->all(), $digital_signature_id);
            $checkoutWebhooks = Webhook::whereJsonContains('type', 'CHECKOUT_TAX_TOKEN')
                ->get();
            foreach ($checkoutWebhooks as $checkoutWebhook) {
                $this->sendNotification("CHECKOUT_TAX_TOKEN",$digital_signature, $checkoutWebhook);
            }
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    $data,
                    trans('admin/digital_signatures/message.checkout.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }
    private function sendNotification($type, $item, $webhook, $isCheckin = false, $isConfirmed = false, $isRejected = false)
    {

        $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is requested to check out.";
        if ($isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is requested to check in.";
        }
        if($isConfirmed && !$isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is confirmed to check out.";
        }
        if($isRejected && !$isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is rejected to check out.";
        }
        if($isConfirmed && $isCheckin) {
            $messageText = "[{$item->category->category_type}] {$item->name} - {$item->category->name} is confirmed to check in.";
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

    public function multiCheckout(Request $request)
    {
        $this->authorize('checkout', Asset::class);
        try {
            $digitalSignatures = request('signatures');
            foreach ($digitalSignatures as $digital_signature_id) {
                $data = $this->digitalSignatureService->checkout($request->all(), $digital_signature_id);
            }
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    null,
                    trans('admin/digital_signatures/message.checkout.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function checkIn(Request $request, $signature_id)
    {
        $this->authorize('checkin', DigitalSignatures::class);
        try {
            $digital_signature = DigitalSignatures::find($signature_id);
            $data = $this->digitalSignatureService->checkin($request->all(), $signature_id);

            $checkinWebhooks = Webhook::whereJsonContains('type', 'CHECKIN_TAX_TOKEN')
                ->get();
            foreach ($checkinWebhooks as $checkinWebhook) {
                $this->sendNotification("CHECKIN_TAX_TOKEN",$digital_signature, $checkinWebhook, true);
            }

            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    $data,
                    trans('admin/digital_signatures/message.checkin.success')
                ),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function multiCheckin(Request $request)
    {
        $this->authorize('checkin', DigitalSignatures::class);
        try {
            $digitalSignatures = request('signatures');
            foreach ($digitalSignatures as $digital_signature_id) {
                $data = $this->digitalSignatureService->checkin($request->all(), $digital_signature_id);
            }
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    $data,
                    trans('admin/digital_signatures/message.checkin.success')
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
        $data = $this->digitalSignatureService->assign($request->all());
        return (new DigitalSignaturesTransformer())->transformSignatures($data['digital_signatures'], $data['total']);
    }

    public function multiUpdate(Request $request)
    {
        $this->authorize('update', DigitalSignatures::class);

        try {
            $signatures_id = $request->input('tax_tokens');
            foreach ($signatures_id as $id) {
                $data = $this->digitalSignatureService->update($request->all(), $id);
            }
            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $data,
                trans('admin/digital_signatures/message.update.success')
            ), Response::HTTP_OK);
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
