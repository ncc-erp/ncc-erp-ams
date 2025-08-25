<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'url',
        'message',
        'status_code',
        'response',
        'asset',
        'type',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
    public function isDeletable()
    {
        return true;
    }
}