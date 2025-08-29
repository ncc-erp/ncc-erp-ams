<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Mail\CheckoutMail;
use App\Models\Setting;
use App\Services\KomuService;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
class SendCheckoutMail implements ShouldQueue
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
        $this->data       = $data;
        $this->user_email = $user_email;

        Log::debug("SendCheckoutMail job created for: " . $user_email);
        Log::debug("Job data: " . json_encode($data));
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
            $message   = KomuMessages::assetCheckout($this->data);

            Log::debug("[SendCheckoutMail] Sending tool check-in notification to: " . $user_name);
            Log::debug("Check-in message is sent: $message");

            // Send Komu message
            KomuService::sendMessage($user_name, $message);

            // TODO: Turn off (When fixed all -> turn on)
            // Send mail with logging
            // $ccEmails = [Setting::first()->admin_cc_email];
            // MailService::sendMail(
            //     new CheckoutMail($this->data), 
            //     $this->user_email,
            //     $ccEmails,
            //     'checkout',
            //     'Asset Checkout Notification'
            // );
            
        } catch (\Exception $e) {
            Log::error('SendCheckoutMail: ' . $e->getMessage());
        }
    }
}
