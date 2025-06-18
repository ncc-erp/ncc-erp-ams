<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'type',
    ];
    protected $rules = [
        'name' => 'required|string|max:255|min:1|unique:webhooks,name',
        'url' => 'required|string|max:512|min:1',
        'type' => 'required|array',
    ];
    protected $searchableAttributes = [
        'name',
        'url',
    ];
    protected $casts = [
        'type' => 'array',
    ];
    public function hasAccess($section)
    {
        if ($this->isSuperUser() || $this->isBranchAdmin()) {
            return true;
        }

        return $this->checkPermissionSection($section);
    }
    public function isSuperUser()
    {
        return $this->checkPermissionSection('superuser');
    }
    public function isAdmin()
    {
        return $this->checkPermissionSection('admin');
    }
    public function isBranchAdmin()
    {
        return $this->checkPermissionSection('branchadmin');
    }
    public function isDeletable()
    {
        return true;
    }
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
    public function consumables()
    {
        return $this->hasMany(Consumable::class);
    }
    use ValidatingTrait;
}
