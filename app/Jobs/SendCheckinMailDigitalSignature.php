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
use App\Services\MailService;
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

        if ($mailSuccess) {
            Log::info("[Job] Email sent successfully", $context);
        } else {
            Log::error("[Job] Email failed", $context);
        }
    }
}
