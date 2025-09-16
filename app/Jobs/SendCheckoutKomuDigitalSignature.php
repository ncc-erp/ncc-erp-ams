<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Services\KomuService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCheckoutKomuDigitalSignature implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $data;
    protected $user_email;

    public function __construct($data, $user_email)
    {
        $this->data = $data;
        $this->user_email = $user_email;
    }

    public function handle()
    {
        $context = ['job' => 'SendCheckoutKomuDigitalSignature', 'email' => $this->user_email];
        
        $username = explode('@', $this->user_email)[0];
        $message = KomuMessages::toolCheckoutDigitalSignature($this->data);
        $komuSuccess = KomuService::sendMessage($username, $message);
        
        if ($komuSuccess) {
            Log::info("[Job] Komu sent successfully", $context);
        } else {
            Log::error("[Job] Komu failed", $context);
        }
    }
}
