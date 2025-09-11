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
use App\Mail\CheckinMailDigitalSignature;
use App\Services\KomuService;
use App\Services\MailService;
use App\Helpers\KomuMessages;
use Illuminate\Support\Facades\Log;

class SendCheckinMailDigitalSignature implements ShouldQueue
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
        $context = ['job' => 'SendCheckinMailDigitalSignature', 'email' => $this->user_email];
        
        // Send email
        $ccEmails = [Setting::first()->admin_cc_email];
        $mailSuccess = MailService::sendMail(
            new CheckinMailDigitalSignature($this->data),
            $this->user_email,
            $ccEmails,
            'checkin_digital_signature',
            'Digital Signature Checkin Notification'
        );

        // Send Komu message
        $username = explode('@', $this->user_email)[0];
        $message = KomuMessages::toolCheckinDigitalSignature($this->data);
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
