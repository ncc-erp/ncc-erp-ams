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
    protected $data;
    protected $user_email;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $user_email)
    {
        $this->data       = $data;
        $this->user_email = $user_email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Send mail with logging
            $this->sendCheckoutEmail();

            // Send Komu message
            $this->sendCheckoutKomuMessage();

        } catch (\Exception $e) {
            Log::error("[SendCheckoutMailDigitalSignature][Error] Job Failed: ", [
              'user_email' => $this->user_email,
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine()
            ]);
        }
    }

    private function sendCheckoutEmail(): void
    {
        try {
            $ccEmails = [Setting::first()->admin_cc_email];
            
            Log::info("[SendCheckoutMailDigitalSignature][Email] Starting email send", [
                'to' => $this->user_email,
                'cc' => $ccEmails
            ]);

            MailService::sendMail(
                new CheckoutMailDigitalSignature($this->data), 
                $this->user_email, 
                $ccEmails,
                'checkout_digital_signature',
                'Digital Signature Checkout Notification'
            );
            
            Log::info("[SendCheckoutMailDigitalSignature][Email] Email sent successfully", [
                'to' => $this->user_email
            ]);

        } catch (\Exception $e) {
            Log::error("[SendCheckoutMailDigitalSignature][Email] Email send failed", [
                'to' => $this->user_email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function sendCheckoutKomuMessage(): void
    {
        $user_name = null;
        try {
            $user_name = explode('@', $this->user_email)[0];
            $message   = KomuMessages::toolCheckoutDigitalSignature($this->data);

            Log::info("[SendCheckoutMailDigitalSignature][Komu] Starting Komu message send", [
                'username' => $user_name
            ]);
            
            KomuService::sendMessage($user_name, $message);
            
            Log::info("[SendCheckoutMailDigitalSignature][Komu] Komu message sent successfully", [
                'username' => $user_name
            ]);

        } catch (\Exception $e) {
            Log::error("[SendCheckoutMailDigitalSignature][Komu] Komu message send failed", [
                'username' => $user_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
