<?php
namespace App\Jobs;

use App\Mail\RejectMail;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Helpers\KomuMessages;
use App\Services\KomuService;

class SendRejectAllocateMail implements ShouldQueue
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
            // Send mail with logging
            $this->sendRejectEmail();

            // Send Komu message
            $this->sendRejectKomuMessage();

        } catch (\Exception $e) {
            Log::error("[SendRejectAllocateMail][Error] Job Failed: ", [
              'it_ncc_email' => $this->it_ncc_email,
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine()
            ]);
        }
    }

    private function sendRejectEmail(): void
    {
        try {
            Log::info("[SendRejectAllocateMail][Email] Starting email send", [
                'to' => $this->it_ncc_email
            ]);

            MailService::sendMail(
                new RejectMail($this->data), 
                $this->it_ncc_email, 
                [],
                'reject_allocate',
                'Reject Allocate Request'
            );
            
            Log::info("[SendRejectAllocateMail][Email] Email sent successfully", [
                'to' => $this->it_ncc_email
            ]);

        } catch (\Exception $e) {
            Log::error("[SendRejectAllocateMail][Email] Email send failed", [
                'to' => $this->it_ncc_email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function sendRejectKomuMessage(): void
    {
        $user_name = null;
        try {
            $user_name = explode('@', $this->it_ncc_email)[0];
            $message   = KomuMessages::assetRejectAllocate($this->data);

            Log::info("[SendRejectAllocateMail][Komu] Starting Komu message send", [
                'username' => $user_name
            ]);
            
            KomuService::sendMessage($user_name, $message);
            
            Log::info("[SendRejectAllocateMail][Komu] Komu message sent successfully", [
                'username' => $user_name
            ]);

        } catch (\Exception $e) {
            Log::error("[SendRejectAllocateMail][Komu] Komu message send failed", [
                'username' => $user_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
