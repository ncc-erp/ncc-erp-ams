<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Watson\Validating\ValidatingTrait;
use App\Models\Traits\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tool extends Model
{
    use HasFactory, Searchable, ValidatingTrait, SoftDeletes;

    public $timestamps = true;
    protected $guarded = 'id';
    protected $table = 'tools';
    protected $injectUniqueIdentifier = true;

    protected $rules = [
        'name' => 'required|unique|string|min:3|max:255',
        'supplier_id' => 'required|exists:suppliers,id',
        'user_id' => 'nullable|exists:users,id',
        'category_id' => 'required|integer|exists:categories,id',
        'location_id'     => 'exists:locations,id|nullable',
        'qty'               => 'required|integer|min:1',
        'assisgned_to' => 'nullable|exists:users,id',
        'purchase_date' => 'required|date',
        'purchase_cost' => 'required|numeric',
        'expiration_date' => 'required|date',
        'status_id' => 'nullable|numeric',
        'notes' => 'nullable|string',
    ];

    protected $fillable = [
        'name',
        'category_id',
        'supplier_id',
        'user_id',
        'purchase_cost',
        'purchase_date',
        'notes',
        'assisgned_to',
        'qty',
        'location_id',
        'status_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id')->where('category_type', '=', 'tool');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function tokenStatus()
    {
        return $this->belongsTo(Statuslabel::class, 'status_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tools_users', 'tool_id', 'assigned_to');
    }

    public function toolsUsers()
    {
        return $this->hasMany(ToolUser::class);
    }

    public function assignedTo()
    {
        return $this->morphTo('assigned', 'assigned_type', 'assigned_to')->withTrashed();
    }

    public function scopeOrderUser($query, $order)
    {
        return $query->join('users', 'users.id', '=', $this->table . '.user_id')
            ->orderBy('users.username', $order);
    }

    public function scopeOrderAssignToUser($query, $order)
    {
        return $query->leftJoin('users', 'users.id', '=', $this->table . '.assigned_to')
            ->orderBy('users.username', $order);
    }
    
    public function scopeOrderCategory($query, $order)
    {
        return $query->join('categories', 'tools.category_id', '=', 'categories.id')->orderBy('categories.name', $order);
    }

    public function scopeOrderSupplier($query, $order)
    {
        return $query->join('suppliers', 'tools.supplier_id', '=', 'suppliers.id')
            ->orderBy('suppliers.name', $order);
    }

    public function scopeOrderLocation($query, $order)
    {
        return $query->join('locations', 'locations.id', '=', $this->table . '.location_id')->select($this->table . '.*')
            ->orderBy('locations.name', $order);
    }

    public function scopeOrderCheckoutCount($query, $order)
    {
        return $query->with('licenses')->withSum('licenses', 'checkout_count')
            ->orderBy('licenses_sum_checkout_count', $order);
    }

    public function scopeBySupplier($query, $supplier_id)
    {
        return $query->join('suppliers', 'tools.supplier_id', '=', 'suppliers.id')
            ->where('tools.supplier_id', '=', $supplier_id);
    }

    public function scopeInCategory($query, $category_id)
    {
        return $query->join('categories', $this->table . '.category_id', '=', 'categories.id')->where($this->table . '.category_id', '=', $category_id);
    }

    public function scopeInSupplier($query, $supplier_id)
    {
        return $query->join('suppliers', $this->table . '.supplier_id', '=', 'suppliers.id')->where($this->table . '.supplier_id', '=', $supplier_id);
    }

    /**
     * Filter tools by supplier, category
     * 
     * @param  Builder $query
     * @param  array  $filter
     * 
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFilter($query, $filter)
    {
        return $query->where(function ($query) use ($filter) {
            foreach ($filter as $key => $search_val) {
                $fieldname = $key;
                if ($fieldname == 'supplier') {
                    $query->whereHas('supplier', function ($query) use ($search_val) {
                        $query->where('suppliers.name', 'LIKE', '%' . $search_val . '%');
                    });
                }

                if ($fieldname == 'category') {
                    $query->whereHas('category', function ($query) use ($search_val) {
                        $query->where(function ($query) use ($search_val) {
                            $query->where('categories.name', 'LIKE', '%' . $search_val . '%');
                        });
                    });
                }

                if ($fieldname != 'category' && $fieldname != 'manufacturer') {
                    $query->where('tools.' . $fieldname, 'LIKE', '%' . $search_val . '%');
                }
            }
        });
    }

    /**
     * Search tools by information of tools
     * 
     * @param  Builder $query
     * @param  array  $terms
     * 
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function advancedTextSearch(Builder $query, array $terms)
    {
        $query = $query->leftJoin('categories as tools_category', function ($leftJoin) {
            $leftJoin->on('tools_category.id', '=', 'tools.category_id');
        });

        $query = $query->leftJoin('suppliers', function ($leftJoin) {
            $leftJoin->on('suppliers.id', '=', 'tools.supplier_id');
        });

        foreach ($terms as $term) {
            $query = $query
                ->where('tools_category.name', 'LIKE', '%' . $term . '%')
                ->orwhere('suppliers.name', 'LIKE', '%' . $term . '%')
                ->orwhere('tools.name', 'LIKE', '%' . $term . '%')
                ->orwhere('tools.version', 'LIKE', '%' . $term . '%')
                ->orwhere('tools.id', '=', $term);
        }
        return $query;
    }

    public function checkIsAdmin() {
        $user = Auth::user();
        return $user->isAdmin();
    }

    public function checkOut($target, $checkout_date, $tool_name, $status)
    {
        if (!$target) {
            return false;
        }

        $this->assignedTo()->associate($target);
        $this->last_checkout = $checkout_date;

        if ($tool_name != null) {
            $this->name = $tool_name;
        }

        if ($status !== null) {
            $this->assigned_status = $status;
        }

        if ($this->save()) {
            return true;
        }
        
        return false;
    }

    public function checkIn($target, $checkout_date, $tool_name, $status)
    {
        if (!$target) {
            return false;
        }
        $this->withdraw_from = $this->assigned_to;

        if ($tool_name != null) {
            $this->name = $tool_name;
        }

        if ($status !== null) {
            $this->assigned_status = $status;
        }

        if ($this->save()) {
            return true;
        }

        return false;
    }

    /**
     * Check tool available for checkout
     * 
     * @return  boolean
     */
    public function availableForCheckout()
    {
        return $this->checkIsAdmin() &&
            !$this->deleted_at &&
            !$this->assigned_to &&
            !$this->withdraw_from &&
            $this->assigned_status === config('enum.assigned_status.DEFAULT') &&
            $this->status_id === config('enum.status_id.READY_TO_DEPLOY');
    }

    /**
     * Check tool available for checkin 
     * 
     * @param  int $assigned_user
     * @return  boolean
     */
    public function availableForCheckin()
    {
        return $this->checkIsAdmin() &&
            !$this->deleted_at &&
            $this->assigned_to &&
            in_array($this->assigned_status, [config('enum.assigned_status.ACCEPT'), config('enum.assigned_status.REJECT')]) &&
            $this->status_id === config('enum.status_id.ASSIGN');
    }
}