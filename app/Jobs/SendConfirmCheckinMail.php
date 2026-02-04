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
use App\Services\MailService;
use Illuminate\Support\Facades\Log;

class SendConfirmCheckinMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $data;
    protected $it_ncc_email;
    
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
        $context = ['job' => 'SendConfirmCheckinMail', 'email' => $this->it_ncc_email];
        
        // Send email
        $mailSuccess = MailService::sendMail(
            new ConfirmCheckinDigitalSignature($this->data),
            $this->it_ncc_email,
            [],
            'confirm_checkin',
            'Confirm Checkin Digital Signature'
        );

        if ($mailSuccess) {
            Log::info("[Job] Email sent successfully", $context);
        } else {
            Log::error("[Job] Email failed", $context);
        }
    }
}
