<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Mail\ConfirmCheckoutDigitalSignature;
use App\Services\KomuService;
use App\Services\MailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendConfirmCheckoutMail implements ShouldQueue
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
        $this->data         = $data;
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
            $message   = KomuMessages::confirmCheckoutDigitalSignature($this->data);

            Log::debug("[SendConfirmCheckoutMail / handle] Raw email: " . $this->it_ncc_email);
            Log::debug("[SendConfirmCheckoutMail / handle] Raw username is extracted from email: " . $user_name);

            // Send Komu message
            KomuService::sendMessage($user_name, $message);
            
            // Send mail with logging (no CC for IT emails)
            // MailService::sendMail(
            //     new ConfirmCheckoutDigitalSignature($this->data), 
            //     $this->it_ncc_email, 
            //     [],
            //     'confirm_checkout',
            //     'Confirm Checkout Digital Signature'
            // );
            
        } catch (\Exception $e) {
            Log::error('SendConfirmCheckoutMail: ' . $e->getMessage());
        }
    }
}
