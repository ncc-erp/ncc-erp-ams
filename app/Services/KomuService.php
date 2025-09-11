<?php

namespace App\Services;

use App\Models\KomuMessageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendKomuMessage;
use Exception;
use Illuminate\Support\Facades\Config;

class KomuService
{
    protected static function resolveUsername(string $username): string 
    {
        if (strtolower($username) === "it") {
            return Config::get('admin.it_admin_username');
        }
        return $username;
    }

    public static function sendMessage(string $username, string $message): bool
    {
        if (empty($username)) {
            Log::warning("[KomuService] Empty username provided, skipping message send");
            return false;
        }

        // Fix: Replace $username="it" with ADMIN_USERNAME from env or default to "thiet.nguyenba"
        $resolvedUsername = self::resolveUsername($username);
        $user = Auth::user();
        $komuApiUrl = env('KOMU_API_URL');
        $komuSecretKey = env('KOMU_SECRET_KEY');
        $enableTesting = env('ENABLE_TESTING');

        $requestData = [
            'username' => $resolvedUsername,
            'message' => $message
        ];

        $responseBody = null;
        $success = false;

        try {
            Log::info("[KomuService] Sending message", [
                'to' => $resolvedUsername,
                'message' => $message,
                'url' => $komuApiUrl . 'sendMessageToUser'
            ]);

            if (!$enableTesting) {
                $response = Http::withHeaders([
                    'X-Secret-Key' => $komuSecretKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($komuApiUrl . 'sendMessageToUser', $requestData);

                $responseBody = $response->body();
                
                if (!$response->successful()) {
                    throw new Exception("API error: {$response->status()} - {$responseBody}");
                }
                $success = true;
            } else {
                $success = true; // Testing mode
                $responseBody = 'Testing mode - message not sent';
            }

            // Log success
            KomuMessageLog::create([
                'send_to' => $resolvedUsername,
                'message' => $message,
                'system_response' => $responseBody,
                'status' => 1,
                'creator_id' => $user->id ?? null,
                'company_id' => $user->company_id ?? null,
            ]);

        } catch (Exception $ex) {
            // Log error
            KomuMessageLog::create([
                'send_to' => $resolvedUsername,
                'message' => $message,
                'system_response' => $ex->getMessage(),
                'status' => 0,
                'creator_id' => $user->id ?? null,
                'company_id' => $user->company_id ?? null,
            ]);

            Log::error("[KomuService] Message send failed", [
                'username' => $resolvedUsername,
                'error' => $ex->getMessage()
            ]);
        }

        return $success;
    }

    public static function sendBatchMessagesWithRateLimit(array $messages, int $batchSize = 5, int $delayBetween = 1): void
    {
        $chunks = array_chunk($messages, $batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $messageData) {
                self::sendMessage($messageData['username'], $messageData['message']);
            }
            
            if ($chunkIndex < count($chunks) - 1) {
                sleep($delayBetween);
            }
        }
    }
} 