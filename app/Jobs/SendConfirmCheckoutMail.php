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
            // Send mail with logging (no CC for IT emails)
            $this->sendConfirmEmail();

            // Send Komu message
            $this->sendConfirmKomuMessage();

        } catch (\Exception $e) {
            Log::error("[SendConfirmCheckoutMail][Error] Job Failed: ", [
              'it_ncc_email' => $this->it_ncc_email,
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine()
            ]);
        }
    }

    private function sendConfirmEmail(): void
    {
        try {
            Log::info("[SendConfirmCheckoutMail][Email] Starting email send", [
                'to' => $this->it_ncc_email
            ]);

            MailService::sendMail(
                new ConfirmCheckoutDigitalSignature($this->data), 
                $this->it_ncc_email, 
                [],
                'confirm_checkout',
                'Confirm Checkout Digital Signature'
            );
            
            Log::info("[SendConfirmCheckoutMail][Email] Email sent successfully", [
                'to' => $this->it_ncc_email
            ]);

        } catch (\Exception $e) {
            Log::error("[SendConfirmCheckoutMail][Email] Email send failed", [
                'to' => $this->it_ncc_email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function sendConfirmKomuMessage(): void
    {
        $user_name = null;
        try {
            $user_name = explode('@', $this->it_ncc_email)[0];
            $message   = KomuMessages::confirmCheckoutDigitalSignature($this->data);

            Log::info("[SendConfirmCheckoutMail][Komu] Starting Komu message send", [
                'username' => $user_name
            ]);
            
            KomuService::sendMessage($user_name, $message);
            
            Log::info("[SendConfirmCheckoutMail][Komu] Komu message sent successfully", [
                'username' => $user_name
            ]);

        } catch (\Exception $e) {
            Log::error("[SendConfirmCheckoutMail][Komu] Komu message send failed", [
                'username' => $user_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
