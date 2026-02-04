<?php

namespace App\Jobs;

use App\Mail\CheckoutMailTool;
use App\Models\Setting;
use App\Services\MailService;
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
    
    public function __construct($data, $user_email)
    {
        $this->data = $data;
        $this->user_email = $user_email;
    }

    public function handle()
    {
        $context = ['job' => 'SendCheckoutMailTool', 'email' => $this->user_email];
        
        // Send email
        $ccEmails = [Setting::first()->admin_cc_email];
        $mailSuccess = MailService::sendMail(
            new CheckoutMailTool($this->data),
            $this->user_email,
            $ccEmails,
            'checkout_tool',
            'Tool Checkout Notification'
        );

        if ($mailSuccess) {
            Log::info("[Job] Email sent successfully", $context);
        } else {
            Log::error("[Job] Email failed", $context);
        }
    }
}
