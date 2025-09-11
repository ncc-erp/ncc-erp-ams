<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Mail\RejectRevokeMail;
use App\Services\KomuService;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendRejectRevokeMail implements ShouldQueue
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
            $this->sendRejectRevokeEmail();

            // Send Komu message
            $this->sendRejectRevokeKomuMessage();

        } catch (\Exception $e) {
            Log::error("[SendRejectRevokeMail][Error] Job Failed: ", [
              'it_ncc_email' => $this->it_ncc_email,
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine()
            ]);
        }
    }

    private function sendRejectRevokeEmail(): void
    {
        try {
            Log::info("[SendRejectRevokeMail][Email] Starting email send", [
                'to' => $this->it_ncc_email
            ]);

            MailService::sendMail(
                new RejectRevokeMail($this->data), 
                $this->it_ncc_email, 
                [],
                'reject_revoke',
                'Reject Revoke Request'
            );
            
            Log::info("[SendRejectRevokeMail][Email] Email sent successfully", [
                'to' => $this->it_ncc_email
            ]);

        } catch (\Exception $e) {
            Log::error("[SendRejectRevokeMail][Email] Email send failed", [
                'to' => $this->it_ncc_email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function sendRejectRevokeKomuMessage(): void
    {
        $user_name = null;
        try {
            $user_name = explode('@', $this->it_ncc_email)[0];
            $message   = KomuMessages::rejectRevoke($this->data);

            Log::info("[SendRejectRevokeMail][Komu] Starting Komu message send", [
                'username' => $user_name
            ]);
            
            KomuService::sendMessage($user_name, $message);
            
            Log::info("[SendRejectRevokeMail][Komu] Komu message sent successfully", [
                'username' => $user_name
            ]);

        } catch (\Exception $e) {
            Log::error("[SendRejectRevokeMail][Komu] Komu message send failed", [
                'username' => $user_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
