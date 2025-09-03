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
            $user_name = explode('@', $this->user_email)[0];
            $message = KomuMessages::toolCheckinDigitalSignature($this->data);

            Log::debug("[SendCheckinMailDigitalSignature] Username to send Komu: " . $user_name);

            // Send Komu message
            KomuService::sendMessage($user_name, $message);
            
            // Send mail with logging
            $ccEmails = [Setting::first()->admin_cc_email];
            MailService::sendMail(
                new CheckinMailDigitalSignature($this->data), 
                $this->user_email, 
                $ccEmails,
                'checkin_digital_signature',
                'Digital Signature Checkin Notification'
            );
            
        } catch (\Exception $e) {
            Log::error('SendCheckinMailDigitalSignature: ' . $e->getMessage());
        }
    }
}
