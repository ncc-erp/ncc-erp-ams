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
            // Extract username from email for logging purposes
            $user_name = explode('@', $this->it_ncc_email)[0];
            if ($user_name === "it") {
                $user_name = config('admin.it_admin_username');
            }
            
            Log::debug("[SendRejectAllocateMail / handle] Raw email: " . $this->it_ncc_email);
            Log::debug("[SendRejectAllocateMail / handle] Raw username is extracted from email: " . $user_name);
            Log::debug("[SendRejectAllocateMail / handle] Resolved username: " . $user_name);
            
            // Send mail with logging
            // MailService::sendMail(
            //     new RejectMail($this->data), 
            //     $this->it_ncc_email, 
            //     [],
            //     'reject_allocate',
            //     'Reject Allocate Request'
            // );
            
        } catch (\Exception $e) {
            Log::error('SendRejectAllocateMail: ' . $e->getMessage());
        }
    }
}
