<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\SoftwareLicensesTransformer;
use App\Jobs\SendCheckoutMail;
use App\Jobs\SendCheckoutMailSoftware;
use App\Models\Company;
use App\Models\LicensesUsers;
use App\Models\Software;
use App\Models\SoftwareLicenses;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SoftwareLicensesController extends Controller
{
    /**
     * Display a listing of the resource by software.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $softwareId)
    {
        $this->authorize('view', SoftwareLicenses::class);
        $licenses = Company::scopeCompanyables(
            SoftwareLicenses::select('software_licenses.*')
                ->with('software')
                ->withCount('allocatedSeats as allocated_seats_count')
                ->where('software_id', '=', $softwareId)
        );
        $allowed_columns = [
            'id',
            'software_id',
            'licenses',
            'seats',
            'allocated_seats_count',
            'free_seats_count',
            'purchase_date',
            'expiration_date',
            'purchase_cost'
        ];

        $filter = [];

        if ($request->filled('filter')) {
            $filter = json_decode($request->input('filter'), true);
        }

        if ((!is_null($filter)) && (count($filter)) > 0) {
            $licenses->ByFilter($filter);
        } elseif ($request->filled('search')) {
            $licenses->TextSearch($request->input('search'));
        }

        $total = $licenses->count();
        $offset = (($licenses) && ($request->get('offset') > $licenses->count()))
            ? $licenses->count()
            : $request->get('offset', 0);

        ((config('app.max_results') >= $request->input('limit')) && ($request->filled('limit')))
            ? $limit = $request->input('limit')
            : $limit = config('app.max_results');

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';

        $field_sort = $request->input('sort');
        $default_sort = in_array($field_sort, $allowed_columns) ? $field_sort : 'software_licenses.created_at';
        if($field_sort  == 'free_seats_count'){
            // $licenses->OrderFreeSeats($order);
        }else{
            $licenses->orderBy($default_sort, $order);
        }


        $licenses = $licenses->skip($offset)->take($limit)->get();
        return (new SoftwareLicensesTransformer)->transformSoftwareLicenses($licenses, $total);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', SoftwareLicenses::class);
        $license = new SoftwareLicenses();

        $license->fill($request->all());
        $license->licenses = $request->get('licenses');
        $license->user_id = Auth::id();
        if ($license->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $license, trans('admin/licenses/message.create.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $license->getErrors()));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->authorize('view', SoftwareLicenses::class);
        $license = SoftwareLicenses::withCount('allocatedSeats as allocated_seats_count')
            ->with('assignedUsers')->findOrFail($id);
        return (new SoftwareLicensesTransformer)->transformSoftwareLicense($license);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->authorize('update', SoftwareLicenses::class);
        $license = SoftwareLicenses::find($id);
        if ($license) {
            $license->fill($request->all());
            if($request->get('licenses')){
                $license->licenses = $request->get('licenses');
            }
            if ($license->save()) {
                return response()->json(Helper::formatStandardApiResponse('success', $license, trans('admin/licenses/message.update.success')));
            }
            return response()->json(Helper::formatStandardApiResponse('error', null, $license->getErrors()), 200);
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/licenses/message.does_not_exist')), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $license = SoftwareLicenses::findOrFail($id);
        $this->authorize('delete', $license);
        if ($license->delete()) {
            return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/licenses/message.delete.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/licenses/message.does_not_exist')), 200);
    }

    public function multiCheckout(Request $request){
        $this->authorize('checkout', SoftwareLicenses::class);
        $softwares = $request->get('softwares');
        $licenses_active = array();

        foreach( $softwares as $software_id ){
            $software = Software::find($software_id);

            if ($software && !$software->availableForCheckout()) {
                return response()->json(Helper::formatStandardApiResponse('error', 
                [
                    'software'=> e($software->name),
                ], 
                trans('admin/licenses/message.checkout.not_available')));
            }

            $assigned_users = $request->get('assigned_users');
            foreach($assigned_users as $assigned_user){
                if(User::find($assigned_user)){
                    $license = new SoftwareLicenses;
                    $license = $license->getFirstLicenseAvailableForCheckout($software_id);
                    $license_user = new LicensesUsers();
                    $license_user->software_licenses_id = $license->id;
                    $license_user->assigned_to = $assigned_user;
                    $license_user->checkout_at = $request->input('checkout_at');
                    $license_user->created_at = Carbon::now();
                    $license_user->user_id = Auth::id();
                    if($license_user->save()){
                        $licenseUpdate = SoftwareLicenses::findOrFail($license->id);
                        $licenseUpdate->update(['checkout_count' => $licenseUpdate->checkout_count + 1 ]);
                        array_push($licenses_active, $license_user->license->licenses);
                        $this->sendMailCheckOut($assigned_user, $licenseUpdate);
                    }
                }
                
            }
            }
            
        return response()->json(Helper::formatStandardApiResponse('success', ['license' => $licenses_active], trans('admin/licenses/message.checkout.success')));
    }

    public function checkOut(Request $request, $license_id)
    {
        $this->authorize('checkout', SoftwareLicenses::class);
        $license = SoftwareLicenses::findOrFail($license_id);
        if(!$license->availableForCheckout()){
            return response()->json(Helper::formatStandardApiResponse('error',      
            [
                'license'=> e($license->licenses),
                'seats'=> e($license->seats),
                'allocated seats'=> e($license->allocatedSeats()->count()),
            ], 
            trans('admin/licenses/message.checkout.not_available')));
        }

        $this->authorize('checkout', $license);
        $assigned_users = $request->get('assigned_users');
        foreach($assigned_users as $assigned_user){
            if(User::find($assigned_user)){
                $license_user = new LicensesUsers();
                $license_user->software_licenses_id = $license_id;
                $license_user->assigned_to = $assigned_user;
                $license_user->created_at = Carbon::now();
                $license_user->user_id = Auth::id();
                $license_user->checkout_at = $request->input('checkout_at');
                if ($license_user->save()) {
                    $license->update(['checkout_count' => $license->checkout_count + 1]);
                    $this->sendMailCheckOut($assigned_user, $license);
                }
            }
        }
        
        return response()->json(Helper::formatStandardApiResponse('success', ['license' => e($license_user->license->licenses)], trans('admin/licenses/message.checkout.success')));
    }

    public function sendMailCheckOut($assigned_user, $license){
        $user = User::find($assigned_user);
        $user_email = $user->email;
        $user_name = $user->first_name . ' ' . $user->last_name;
        $current_time = Carbon::now();
        $data = [
            'user_name' => $user_name,
            'software_name' => $license->software->name,
            'license' => $license->licenses,
            'count' => 1,
            'location_address' => null,
            'time' => $current_time->format('d-m-Y'),
            'link' => config('client.my_assets.link'),
        ];
        SendCheckoutMailSoftware::dispatch($data, $user_email);
    }
}
