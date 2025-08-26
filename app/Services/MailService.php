<?php

namespace App\Services;

use App\Models\MailLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class MailService
{
    public static function sendMail($mailable, ?string $sendTo, array $ccEmails = [], string $messageType = 'default', ?string $subject = null): void
    {
        
        if (empty($sendTo)) {
            return;
        }

        $user = Auth::user();
        $enableTesting = env('ENABLE_TESTING');
        $isEmptyCcEmails = empty($ccEmails);
        $recipients = $sendTo;
        $emailSubject = $subject;
        $emailContent = $mailable->render();

        if (!$isEmptyCcEmails) {
            $recipients .= ' (CC: ' . implode(', ', $ccEmails) . ')';
        }

        if (!$emailSubject && method_exists($mailable, 'build')) {
            $built = $mailable->build();
            $emailSubject = $built->subject ?? 'No Subject';
        }

        try {
            if (!$enableTesting) {
                if (!$isEmptyCcEmails) {
                    Mail::to($sendTo)->cc($ccEmails)->send($mailable);
                } else {
                    Mail::to($sendTo)->send($mailable); 
                }
            }

            MailLog::create([
                'send_to' => $recipients,
                'subject' => $emailSubject ?? 'Mail Sent',
                'message_type' => $messageType,
                'message_content' => $emailContent,
                'system_response' => 'Mail sent successfully via SMTP',
                'status' => 1,
                'creator_id' => $user->id ?? null,
                'company_id' => $user->company_id ?? null,
            ]);

        } catch (Exception $ex) {
            MailLog::create([
                'send_to' => $recipients,
                'subject' => $emailSubject ?? 'Mail Failed',
                'message_type' => $messageType,
                'message_content' => $emailContent,
                'system_response' => $ex->getMessage(),
                'status' => 0,
                'creator_id' => $user->id ?? null,
                'company_id' => $user->company_id ?? null,
            ]);

            Log::error('Mail send failed', [
                'send_to' => $sendTo,
                'cc_emails' => $ccEmails,
                'message_type' => $messageType,
                'error' => $ex->getMessage()
            ]);
        }
    }
} 