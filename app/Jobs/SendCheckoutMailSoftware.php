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
use App\Mail\CheckoutMailSoftware;
use App\Services\MailService;
use Illuminate\Support\Facades\Log;

class SendCheckoutMailSoftware implements ShouldQueue
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
        $context = ['job' => 'SendCheckoutMailSoftware', 'email' => $this->user_email];
        
        // Send email
        $ccEmails = [Setting::first()->admin_cc_email];
        $mailSuccess = MailService::sendMail(
            new CheckoutMailSoftware($this->data),
            $this->user_email,
            $ccEmails,
            'checkout_software',
            'Software Checkout Notification'
        );

        if ($mailSuccess) {
            Log::info("[Job] Email sent successfully", $context);
        } else {
            Log::error("[Job] Email failed", $context);
        }
    }
}
