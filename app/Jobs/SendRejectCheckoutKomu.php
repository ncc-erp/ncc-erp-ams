<?php
namespace App\Jobs;

use App\Helpers\KomuMessages;
use App\Services\KomuService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRejectCheckoutKomu implements ShouldQueue
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
        $context = ['job' => 'SendRejectCheckoutKomu', 'email' => $this->it_ncc_email];
        
        // Send Komu message
        $username = explode('@', $this->it_ncc_email)[0];
        $message = KomuMessages::rejectCheckoutDigitalSignature($this->data);
        $komuSuccess = KomuService::sendMessage($username, $message);
        
        if ($komuSuccess) {
            Log::info("[Job] Komu sent successfully", $context);
        } else {
            Log::error("[Job] Komu failed", $context);
        }
    }
}