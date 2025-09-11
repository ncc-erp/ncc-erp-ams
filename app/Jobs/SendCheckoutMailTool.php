<?php

namespace App\Jobs;

use App\Mail\CheckoutMailTool;
use App\Models\Setting;
use App\Services\KomuService;
use App\Services\MailService;
use App\Helpers\KomuMessages;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;


class SendCheckoutMailTool implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    protected $user_email;

    /**
     * Create a new job instance.
     *
     * @return void
     */

     
    public function __construct($data, $user_email)
    {
        $this->data = $data;
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
            Log::error("[SendCheckoutMailTool][Error] Job Failed: ", [
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
            
            Log::info("[SendCheckoutMailTool][Email] Starting email send", [
                'to' => $this->user_email,
                'cc' => $ccEmails
            ]);

            MailService::sendMail(
                new CheckoutMailTool($this->data), 
                $this->user_email, 
                $ccEmails,
                'checkout_tool',
                'Tool Checkout Notification'
            );
            
            Log::info("[SendCheckoutMailTool][Email] Email sent successfully", [
                'to' => $this->user_email
            ]);

        } catch (\Exception $e) {
            Log::error("[SendCheckoutMailTool][Email] Email send failed", [
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
            $message = KomuMessages::toolCheckout($this->data);

            Log::info("[SendCheckoutMailTool][Komu] Starting Komu message send", [
                'username' => $user_name
            ]);
            
            KomuService::sendMessage($user_name, $message);
            
            Log::info("[SendCheckoutMailTool][Komu] Komu message sent successfully", [
                'username' => $user_name
            ]);

        } catch (\Exception $e) {
            Log::error("[SendCheckoutMailTool][Komu] Komu message send failed", [
                'username' => $user_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
