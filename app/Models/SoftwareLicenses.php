<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Watson\Validating\ValidatingTrait;

class SoftwareLicenses extends Model
{
    use HasFactory, ValidatingTrait, SoftDeletes, Searchable;

    public $timestamps = true;
    protected $guarded = 'id';
    protected $table = 'software_licenses';

    protected $rules = [
        'software_id' => 'required|exists:softwares,id',
        'licenses' => 'required|unique:software_licenses|min:3',
        'seats' => 'required|min:1|integer',
        'purchase_date' => 'required',
        'purchase_cost' => 'required',
        'expiration_date' => 'required',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiration_date' => 'date',
        'seats'   => 'integer',
        'checkout_count'   => 'integer',
        'software_id'   => 'integer',
    ];
    protected $fillable = [
        'expiration_date',
        'purchase_cost',
        'purchase_date',
        'seats',
        'user_id',
        'checkout_count',
        'software_id'
    ];

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id');
    }

    public function scopeOrderSoftware($query, $order)
    {
        return $query->join('softwares', 'software_licenses.software_id', '=', 'softwares.id')->orderBy('softwares.name', $order);
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'software_licenses_users', 'assigned_to', 'software_licenses_id');
    }

    public function allocatedSeats()
    {
        return $this->hasMany(LicensesUsers::class)->whereNull('deleted_at');
    }

    public function scopeByFilter($query, $filter)
    {
        return $query->where(function ($query) use ($filter) {
            foreach ($filter as $key => $search_val) {
                $fieldname = $key;
                if($fieldname == 'purchase_cost'){
                    $query->where('software_licenses.' . $fieldname, $search_val);
                }else{
                    $query->where('software_licenses.' . $fieldname, 'LIKE', '%' . $search_val . '%');
                }
            }
        });
    }

    public function scopeOrderAllocatedSeats($query, $order){
        return $query->join('manufacturers', 'softwares.manufacturer_id', '=', 'manufacturers.id')->orderBy('manufacturers.name', $order);
    }


    public function advancedTextSearch(Builder $query, array $terms)
    {  
        foreach ($terms as $term) {
            $query = $query
                ->Where('software_licenses.seats', $term)
                ->orwhere('software_licenses.purchase_cost',  $term)
                ->orwhere('software_licenses.licenses', 'LIKE', '%' . $term . '%');
        }
        return $query;
    }

    public function availableForCheckout()
    {
        $allocatedSeats = $this->allocatedSeats()->count();
        if ($this->deleted != null || $allocatedSeats == $this->seats || $this->seats == 0) {
            return false;
        }
        return true;
    }

    public function getFirstLicenseAvailableForCheckout($softwareId){
        return $this->leftJoin('software_licenses_users', 'software_licenses.id', '=', 'software_licenses_users.software_licenses_id')
        ->select('software_licenses.id', 'software_licenses.checkout_count',
            'software_licenses.seats', 'software_licenses.licenses',
            DB::raw('count(software_licenses_users.id) as allocatedSeat'))
        ->where('software_id', '=', $softwareId)
        ->where('seats', '>', config('enum.seats.MIN'))
        ->groupBy('software_licenses_users.software_licenses_id')
        ->havingRaw('software_licenses.seats > allocatedSeat')
        ->orderBy('id')
        ->first();
    }
}
