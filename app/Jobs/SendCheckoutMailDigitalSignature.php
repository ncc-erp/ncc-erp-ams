<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Mail\CheckoutMailDigitalSignature;
use App\Models\Setting;
use App\Services\KomuService;
use App\Services\MailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCheckoutMailDigitalSignature implements ShouldQueue
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
        $context = ['job' => 'SendCheckoutMailDigitalSignature', 'email' => $this->user_email];
        
        // Send email
        $ccEmails = [Setting::first()->admin_cc_email];
        $mailSuccess = MailService::sendMail(
            new CheckoutMailDigitalSignature($this->data),
            $this->user_email,
            $ccEmails,
            'checkout_digital_signature',
            'Digital Signature Checkout Notification'
        );

        // Send Komu message
        $username = explode('@', $this->user_email)[0];
        $message = KomuMessages::toolCheckoutDigitalSignature($this->data);
        $komuSuccess = KomuService::sendMessage($username, $message);
        
        // Final result log
        if ($mailSuccess && $komuSuccess) {
            Log::info("[Job] Completed successfully", $context);
        } elseif (!$mailSuccess && !$komuSuccess) {
            Log::error("[Job] Both email and komu failed", $context);
        } else {
            Log::warning("[Job] Partial success", array_merge($context, [
                'mail_ok' => $mailSuccess,
                'komu_ok' => $komuSuccess
            ]));
        }
    }
}
