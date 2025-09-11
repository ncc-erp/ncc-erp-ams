<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Mail\RejectCheckoutDigitalSignature;
use App\Services\KomuService;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRejectCheckoutMail implements ShouldQueue
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
        $context = ['job' => 'SendRejectCheckoutMail', 'email' => $this->it_ncc_email];
        
        // Send email
        $mailSuccess = MailService::sendMail(
            new RejectCheckoutDigitalSignature($this->data),
            $this->it_ncc_email,
            [],
            'reject_checkout',
            'Reject Checkout Digital Signature'
        );

        // Send Komu message
        $username = explode('@', $this->it_ncc_email)[0];
        $message = KomuMessages::rejectCheckoutDigitalSignature($this->data);
        $komuSuccess = KomuService::sendMessage($username, $message);
        
        // Final result log
        if ($mailSuccess && $komuSuccess) {
            Log::info("[Job] Completed successfully", $context);
        } elseif (!$mailSuccess && !$komuSuccess) {
            Log::error("[Job] Both email and komu failed", $context);
        } else {
            Log::warning("[Job] Partial success", array_merge($context, [
                'mail_ok' => $mailSuccess,
                'komu_ok' => $komuSuccess
            ]));
        }
    }
}
