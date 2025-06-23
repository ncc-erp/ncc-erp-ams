<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Searchable;

class MailLog extends SnipeModel
{
    use SoftDeletes, Searchable;

    /**
     * The table associated with the model.
     */
    protected $table = 'mail_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'send_to',
        'subject',
        'message_type',
        'message_content',
        'system_response',
        'status',
        'creator_id',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'integer',
        'creator_id' => 'integer',
        'company_id' => 'integer',
    ];

    /**
     * The attributes that should be searchable.
     */
    protected $searchableAttributes = [
        'send_to',
        'subject',
        'message_type',
        'system_response',
    ];

    /**
     * The attributes that should be sortable.
     */
    protected $sortableAttributes = [
        'send_to',
        'subject',
        'message_type',
        'status',
        'creator_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user who created this log.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the company that owns this log.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by success status.
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to filter by failed status.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Check if the mail was sent successfully.
     */
    public function isSuccess()
    {
        return $this->status === 1;
    }

    /**
     * Check if the mail failed to send.
     */
    public function isFailed()
    {
        return $this->status === 0;
    }
} 