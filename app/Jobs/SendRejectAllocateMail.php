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
            // Send mail with logging
            MailService::sendMail(
                new RejectMail($this->data), 
                $this->it_ncc_email, 
                [],
                'reject_allocate',
                'Reject Allocate Request'
            );

        } catch (\Exception $e) {
            Log::error('SendRejectAllocateMail: ' . $e->getMessage());
        }
    }
}
