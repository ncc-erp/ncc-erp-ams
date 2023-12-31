<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class SnipeModel extends Model
{
    // Setters that are appropriate across multiple models.
    public function setPurchaseDateAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['purchase_date'] = $value;
    }

    /**
     * @param $value
     */
    public function setPurchaseCostAttribute($value)
    {
        $value = Helper::ParseFloat($value);

        if ($value == '0.0') {
            $value = null;
        }
        $this->attributes['purchase_cost'] = $value;
    }

    public function setLocationIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['location_id'] = $value;
    }

    public function setCategoryIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['category_id'] = $value;
        // dd($this->attributes);
    }

    public function setSupplierIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['supplier_id'] = $value;
    }

    public function setDepreciationIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['depreciation_id'] = $value;
    }

    public function setManufacturerIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['manufacturer_id'] = $value;
    }

    public function setMinAmtAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['min_amt'] = $value;
    }

    public function setParentIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['parent_id'] = $value;
    }

    public function setFieldSetIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['fieldset_id'] = $value;
    }

    public function setCompanyIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['company_id'] = $value;
    }

    public function setWarrantyMonthsAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['warranty_months'] = $value;
    }

    public function setRtdLocationIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['rtd_location_id'] = $value;
    }

    public function setDepartmentIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['department_id'] = $value;
    }

    public function setManagerIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['manager_id'] = $value;
    }

    public function setModelIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['model_id'] = $value;
    }

    public function setStatusIdAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['status_id'] = $value;
    }

    //
    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    /**
     * Query builder scope to filter assets by role
     *
     */
    public function scopeFilterAssetByRole($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->isBranchAdmin()) {
            $manager_location = json_decode($user->manager_location, true);
            return $query->whereIn('assets.rtd_location_id', $manager_location);
        }
        return $query->where('assets.user_id', '=', $user->id);
    }

    /**
     * Query builder scope to filter accessories by role
     *
     */
    public function scopeFilterAccessoriesByRole($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        $manager_location = json_decode($user->manager_location, true);
        return $query->whereIn('accessories.location_id', $manager_location);
    }

    /**
     * Query builder scope to filter consumables by role
     *
     */
    public function scopeFilterConsumablesByRole($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        $manager_location = json_decode($user->manager_location, true);
        return $query->when($manager_location, function ($query) use ($manager_location) {
            return $query->whereIn('consumables.location_id', $manager_location);
        });
    }

    /**
     * Query builder scope to filter report by role
     *
     */
    public function scopeFilterReportByRole($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        $manager_location = json_decode($user->manager_location, true);
        return $query->join('assets as assets_report', 'action_logs.item_id', '=', 'assets_report.id')
            ->when($manager_location, function ($query) use ($manager_location) {
                return $query->whereIn('assets_report.rtd_location_id', $manager_location);
            });
    }

}