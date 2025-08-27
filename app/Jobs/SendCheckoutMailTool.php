<?php

namespace App\Jobs;

use App\Mail\CheckoutMailTool;
use App\Models\Setting;
use App\Services\KomuService;
use App\Services\MailService;
use App\Helpers\KomuMessages;
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
            $user_name = explode('@', $this->user_email)[0];
            $message = KomuMessages::toolCheckout($this->data);
            
            // Send Komu message
            // KomuService::sendMessage($user_name, $message);
            
            // Send mail with logging
            $ccEmails = [Setting::first()->admin_cc_email];
            MailService::sendMail(
                new CheckoutMailTool($this->data), 
                $this->user_email, 
                $ccEmails,
                'checkout_tool',
                'Tool Checkout Notification'
            );
            
        } catch (\Exception $e) {
            \Log::error('SendCheckoutMailTool: ' . $e->getMessage());
        }
    }
}
