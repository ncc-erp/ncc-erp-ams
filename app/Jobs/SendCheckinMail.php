<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use App\Mail\CheckinMail;
use App\Services\KomuService;
use App\Services\MailService;
use App\Helpers\KomuMessages;
use Illuminate\Support\Facades\Log;

class SendCheckinMail implements ShouldQueue
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
            $this->sendCheckinEmail();

            // Send Komu message
            $this->sendCheckinKomuMessage();

        } catch (\Exception $e) {
            Log::error("[SendCheckinMail][Error] Job Failed: ", [
              'user_email' => $this->user_email,
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine()
            ]);
        }
    }

    private function sendCheckinEmail(): void
    {
        try {
            $ccEmails = [Setting::first()->admin_cc_email];
            
            Log::info("[SendCheckinMail][Email] Starting email send", [
                'to' => $this->user_email,
                'cc' => $ccEmails
            ]);

            MailService::sendMail(
                new CheckinMail($this->data), 
                $this->user_email, 
                $ccEmails,
                'checkin',
                'Asset Checkin Notification'
            );
            
            Log::info("[SendCheckinMail][Email] Email sent successfully", [
                'to' => $this->user_email
            ]);

        } catch (\Exception $e) {
            Log::error("[SendCheckinMail][Email] Email send failed", [
                'to' => $this->user_email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function sendCheckinKomuMessage(): void
    {
        $user_name = null;
        try {
            $user_name = explode('@', $this->user_email)[0];
            $message = KomuMessages::assetCheckin($this->data);

            Log::info("[SendCheckinMail][Komu] Starting Komu message send", [
                'username' => $user_name
            ]);
            
            KomuService::sendMessage($user_name, $message);
            
            Log::info("[SendCheckinMail][Komu] Komu message sent successfully", [
                'username' => $user_name
            ]);

        } catch (\Exception $e) {
            Log::error("[SendCheckinMail][Komu] Komu message send failed", [
                'username' => $user_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
