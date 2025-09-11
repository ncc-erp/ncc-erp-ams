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
        $context = ['job' => 'SendRejectAllocateMail', 'email' => $this->it_ncc_email];
        
        // Send email
        $mailSuccess = MailService::sendMail(
            new RejectMail($this->data),
            $this->it_ncc_email,
            [],
            'reject_allocate',
            'Reject Allocate Request'
        );

        // Send Komu message
        $username = explode('@', $this->it_ncc_email)[0];
        $message = KomuMessages::assetRejectAllocate($this->data);
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
