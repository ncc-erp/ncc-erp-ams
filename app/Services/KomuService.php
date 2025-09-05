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
      $transformedUsername = $username;

      if (strtolower($username) === "it") {
        $transformedUsername = Config::get('admin.it_admin_username');
        Log::debug("[KomuService / resolveUsername] Replaced 'it' with admin username: " . $transformedUsername);
      } else {
        Log::debug("[KomuService / resolveUsername] No replacement needed for username: " . $transformedUsername);
      }

      return $transformedUsername;
    }

    public static function sendMessage(string $username, string $message): void
    {
        if (empty($username)) {
            return;
        }

        // Fix: Replace $username="it" with ADMIN_USERNAME from env or default to "thiet.nguyenba"
        $username = self::resolveUsername($username);
        
        Log::debug("[KomuService / sendMessage] Sending message to username: " . $username);

        $user = Auth::user();

        $komuApiUrl = env('KOMU_API_URL');
        $komuSecretKey = env('KOMU_SECRET_KEY');
        $enableTesting = env('ENABLE_TESTING');

        $requestData = [
            'username' => $username,
            'message' => $message
        ];

        Log::debug('Komu API response', [
            'requestData' => $requestData
        ]);

        $responseBody = null;

        try {
            if (!$enableTesting) {
                $response = Http::withHeaders([
                    'X-Secret-Key' => $komuSecretKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($komuApiUrl . 'sendMessageToUser', $requestData);

                $responseBody = $response->body();
                Log::debug('Komu API response', [
                    'response' => $responseBody
                ]);
            }

            KomuMessageLog::create([
                'send_to' => $username,
                'message' => $message,
                'system_response' => $responseBody ?? 'Testing mode - mail not sent',
                'status' => 1,
                'creator_id' => $user->id,
                'company_id' => $user->company_id ?? null,
            ]);

        } catch (Exception $ex) {
            KomuMessageLog::create([
                'send_to' => $username,
                'message' => $message,
                'system_response' => $ex->getMessage(),
                'status' => 0,
                'creator_id' => $user->id,
                'company_id' => $user->company_id ?? null,
            ]);

            Log::error('Komu send message failed', [
                'username' => $username,
                'message' => $message,
                'error' => $ex->getMessage()
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