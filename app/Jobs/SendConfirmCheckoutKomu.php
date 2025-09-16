<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\KomuService;
use App\Helpers\KomuMessages;

class SendConfirmCheckoutKomu implements ShouldQueue
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
        $context = ['job' => 'SendConfirmCheckoutKomu', 'email' => $this->it_ncc_email];
        
        $username = explode('@', $this->it_ncc_email)[0];
        $message = KomuMessages::confirmCheckoutDigitalSignature($this->data);
        $komuSuccess = KomuService::sendMessage($username, $message);
        
        if ($komuSuccess) {
            Log::info("[Job] Komu sent successfully", $context);
        } else {
            Log::error("[Job] Komu failed", $context);
        }
    }
}
