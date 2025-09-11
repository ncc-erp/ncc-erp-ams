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
    protected static function resolveUsername(string $username): string {
      $originalUsername = $username;

      if (strtolower($username) === "it") {
        $transformedUsername = Config::get('admin.it_admin_username');

        Log::debug("[KomuService] Username resolved", [
            'original' => $originalUsername,
            'resolved' => $transformedUsername
        ]);

        // Return the resolved username
        return $transformedUsername;
      }

      // If no transformation was applied, return the original username
      return $username;
    }

    public static function sendMessage(string $username, string $message): void
    {
        if (empty($username)) {
            Log::warning("[KomuService] Empty username provided, skipping message send");
            return;
        }

        // Fix: Replace $username="it" with ADMIN_USERNAME from env or default to "thiet.nguyenba"
        $resolvedUsername = self::resolveUsername($username);
        
        $user = Auth::user();

        Log::info("[KomuService] Starting message send", [
            'username' => $resolvedUsername,
            'message' => $message
        ]);

        $komuApiUrl = env('KOMU_API_URL');
        $komuSecretKey = env('KOMU_SECRET_KEY');
        $enableTesting = env('ENABLE_TESTING');

        $requestData = [
            'username' => $resolvedUsername,
            'message' => $message
        ];

        $responseBody = null;

        try {
            if (!$enableTesting) {
                Log::debug("[KomuService] Making API call to Komu", [
                    'url' => $komuApiUrl . 'sendMessageToUser',
                    'username' => $resolvedUsername
                ]);

                $response = Http::withHeaders([
                    'X-Secret-Key' => $komuSecretKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($komuApiUrl . 'sendMessageToUser', $requestData);

                $responseBody = $response->body();

                Log::debug("[KomuService] API call completed", [
                    'username' => $resolvedUsername,
                    'status_code' => $response->status(),
                    'response' => $responseBody
                ]);
            }

            KomuMessageLog::create([
                'send_to' => $resolvedUsername,
                'message' => $message,
                'system_response' => $responseBody ?? 'Testing mode - mail not sent',
                'status' => 1,
                'creator_id' => $user->id,
                'company_id' => $user->company_id ?? null,
            ]);

            Log::info("[KomuService] Message sent successfully", [
                'username' => $resolvedUsername
            ]);

        } catch (Exception $ex) {
            KomuMessageLog::create([
                'send_to' => $resolvedUsername,
                'message' => $message,
                'system_response' => $ex->getMessage(),
                'status' => 0,
                'creator_id' => $user->id,
                'company_id' => $user->company_id ?? null,
            ]);

            Log::error("[KomuService] Message send failed", [
                'username' => $resolvedUsername,
                'error' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ]);
        }
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