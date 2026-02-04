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
        $context = ['job' => 'SendConfirmCheckoutMail', 'email' => $this->it_ncc_email];
        
        // Send email
        $mailSuccess = MailService::sendMail(
            new ConfirmCheckoutDigitalSignature($this->data),
            $this->it_ncc_email,
            [],
            'confirm_checkout',
            'Confirm Checkout Digital Signature'
        );

        if ($mailSuccess) {
            Log::info("[Job] Email sent successfully", $context);
        } else {
            Log::error("[Job] Email failed", $context);
        }
    }
}
