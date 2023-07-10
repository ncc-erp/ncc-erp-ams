<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\DigitalSignatures;
use Illuminate\Database\Eloquent\Collection;

class DigitalSignaturesTransformer
{
    public function transformSignatures(Collection $digitalSignatures, $total)
    {
        $array = [];
        foreach ($digitalSignatures as $signature) {
            $array[] = self::transformSignature($signature);
        }

        return (new DatatablesTransformer())->transformDatatables($array, $total);
    }

    public function transformSignature(DigitalSignatures $digitalSignature)
    {
        $array = [
            'id' => (int) $digitalSignature->id,
            'name' => $digitalSignature->name,
            'seri' => e($digitalSignature->seri),
            'supplier' => ($digitalSignature->supplier) ? [
                'id' => (int) $digitalSignature->supplier->id,
                'name' => e($digitalSignature->supplier->name),
            ] : null,
            'location' => ($digitalSignature->location) ? [
                'id' => (int) $digitalSignature->location->id,
                'name' => e($digitalSignature->location->name),
            ] : null,
            'category' => ($digitalSignature->category) ? [
                'id' => (int) $digitalSignature->category->id,
                'name' => e($digitalSignature->category->name),
            ] : null,
            'qty' => (int) $digitalSignature->qty,
            'warranty_months' => (int) $digitalSignature->warranty_months,
            'assigned_to' => $this->transformAssignedTo($digitalSignature),
            'assigned_status' => $digitalSignature->assigned_status,
            'withdraw_from' =>  $digitalSignature->withdraw_from,
            'purchase_date' => Helper::getFormattedDateObject($digitalSignature->purchase_date, 'date'),
            'purchase_cost' => Helper::formatCurrencyOutput($digitalSignature->purchase_cost),
            'expiration_date' => Helper::getFormattedDateObject($digitalSignature->expiration_date, 'date'),
            'checkout_date' => Helper::getFormattedDateObject($digitalSignature->checkout_date, 'datetime'),
            'last_checkout' => Helper::getFormattedDateObject($digitalSignature->last_checkout, 'datetime'),
            'checkin_date' => Helper::getFormattedDateObject($digitalSignature->checkin_date, 'datetime'),
            'status_label' => ($digitalSignature->tokenStatus) ? [
                'id' => (int) $digitalSignature->tokenStatus->id,
                'name'=> e($digitalSignature->tokenStatus->name),
                'status_type'=> e($digitalSignature->tokenStatus->getStatuslabelType()),
                'status_meta' => e($digitalSignature->tokenStatus->getStatuslabelType()),
            ] : null,
            'user_can_checkout' => (bool) $digitalSignature->availableForCheckout(),
            'user_can_checkin' => (bool) $digitalSignature->availableForCheckin(),
            'checkout_counter' => (int) $digitalSignature->checkout_counter,
            'checkin_counter' => (int) $digitalSignature->checkin_counter,
            'note' => e($digitalSignature->note),
            'created_at' => Helper::getFormattedDateObject($digitalSignature->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($digitalSignature->updated_at, 'datetime'),
        ];

        return $array;
    }

    public function transformAssetsDatatable($digitalSignatures)
    {
        return (new DatatablesTransformer())->transformDatatables($digitalSignatures);
    }

    public function transformAssignedTo($digitalSignature)
    {
        return $digitalSignature->assignedUser ? [
                'id' => (int) $digitalSignature->assignedUser->id,
                'username' => e($digitalSignature->assignedUser->username),
                'name' => e($digitalSignature->assignedUser->getFullNameAttribute()),
                'first_name' => e($digitalSignature->assignedUser->first_name),
                'last_name' => ($digitalSignature->assignedUser->last_name) ? e($digitalSignature->assignedUser->last_name) : null,
            ] : null;
    }
}
