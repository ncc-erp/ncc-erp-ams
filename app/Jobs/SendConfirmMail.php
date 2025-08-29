<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Services\KomuService;
use App\Mail\ConfirmMail;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendConfirmMail implements ShouldQueue
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
            // Extract username from email
            $user_name = explode('@', $this->it_ncc_email)[0];

            Log::debug("[SendConfirmMail / handle] Raw email: " . $this->it_ncc_email);
            Log::debug("[SendConfirmMail / handle] Raw username is extracted from email: " . $user_name);

            $message   = KomuMessages::assetConfirmCheckout($this->data);
            
            // Send Komu message
            KomuService::sendMessage($user_name, $message);

            // Send mail with logging
            // MailService::sendMail(
            //     new ConfirmMail($this->data), 
            //     $this->it_ncc_email, 
            //     [],
            //     'confirm_allocate',
            //     'Confirm Allocate Request'
            // );
            
        } catch (\Exception $e) {
            Log::error('SendConfirmMail: ' . $e->getMessage());
        }
    }
}
