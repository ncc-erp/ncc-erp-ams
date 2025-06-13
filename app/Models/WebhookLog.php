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
        'payload',
        'status_code',
        'response',
        'asset_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}