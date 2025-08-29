<?php

namespace App\Jobs;

use App\Mail\ConfirmCheckinDigitalSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Services\KomuService;
use App\Helpers\KomuMessages;
use App\Services\MailService;
use Illuminate\Support\Facades\Log;

class SendConfirmCheckinMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    protected $it_ncc_email;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $it_ncc_email)
    {
        $this->data = $data;
        $this->it_ncc_email = $it_ncc_email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $user_name = explode('@', $this->it_ncc_email)[0];
            $message   = KomuMessages::confirmCheckinDigitalSignature($this->data);

            Log::debug("[SendConfirmCheckinMail / handle] Raw email: " . $this->it_ncc_email);
            Log::debug("[SendConfirmCheckinMail / handle] Raw username is extracted from email: " . $user_name);

            // Send Komu message
            KomuService::sendMessage($user_name, $message);
            
            // Send mail with logging
            // MailService::sendMail(
            //     new ConfirmCheckinDigitalSignature($this->data), 
            //     $this->it_ncc_email, 
            //     [],
            //     'confirm_checkin',
            //     'Confirm Checkin Digital Signature'
            // );
        } catch (\Exception $e) {
            Log::error('SendConfirmCheckinMail: ' . $e->getMessage());
        }
    }
}
