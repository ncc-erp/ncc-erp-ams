<?php

namespace App\Services;

use App\Models\MailLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class MailService
{
    public static function sendMail($mailable, ?string $sendTo, array $ccEmails = [], string $messageType = 'default', ?string $subject = null): bool
    {
        if (empty($sendTo)) {
            Log::warning("[MailService] Empty recipient provided");
            return false;
        }

        $user = Auth::user();
        $enableTesting = env('ENABLE_TESTING');
        $isEmptyCcEmails = empty($ccEmails);
        $recipients = $sendTo . (!empty($ccEmails) ? ' (CC: ' . implode(', ', $ccEmails) . ')' : '');
        $success = false;
        $emailContent = '';
        
        $emailSubject = $subject ?? self::extractSubject($mailable);

        try {
            $emailContent = $mailable->render();
            
            Log::info("[MailService] Sending email", [
                'to' => $sendTo,
                'cc' => $ccEmails,
                'subject' => $emailSubject,
                'type' => $messageType
            ]);

            if (!$enableTesting) {
                if (!$isEmptyCcEmails) {
                    Mail::to($sendTo)->cc($ccEmails)->send($mailable);
                } else {
                    Mail::to($sendTo)->send($mailable);
                }
                $success = true;
            } else {
                $success = true; // Testing mode
            }

            // Log success
            MailLog::create([
                'send_to' => $recipients,
                'subject' => $emailSubject ?? 'Mail Sent',
                'message_type' => $messageType,
                'message_content' => $emailContent,
                'system_response' => $enableTesting ? 'Testing mode - mail not sent' : 'Mail sent successfully',
                'status' => 1,
                'creator_id' => $user->id ?? null,
                'company_id' => $user->company_id ?? null,
            ]);

        } catch (Exception $ex) {
            // Log error
            MailLog::create([
                'send_to' => $recipients,
                'subject' => $emailSubject,
                'message_type' => $messageType,
                'message_content' => $emailContent,
                'system_response' => $ex->getMessage(),
                'status' => 0,
                'creator_id' => $user->id ?? null,
                'company_id' => $user->company_id ?? null,
            ]);

            Log::error("[MailService] Send failed", [
                'to' => $sendTo,
                'type' => $messageType,
                'error' => $ex->getMessage()
            ]);
        }

        return $success;
    }

    private static function extractSubject($mailable): string
    {
        try {
            if (method_exists($mailable, 'build')) {
                $built = $mailable->build();
                return $built->subject ?? 'No Subject';
            }
        } catch (Exception $e) {
            // Ignore extraction errors
        }
        return 'No Subject';
    }
} 