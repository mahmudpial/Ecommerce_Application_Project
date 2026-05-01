<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogOtpMailSent
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        // Log mail message details
        Log::info('📧 Mail Message Sent', [
            'subject' => $message->getSubject(),
            'from' => $message->getFrom() ? array_keys($message->getFrom())[0] : 'Unknown',
            'to' => $message->getTo() ? array_keys($message->getTo())[0] : 'Unknown',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        // Log OTP-specific details if this is an OTP mail
        if (strpos($message->getSubject(), 'Otp') !== false || strpos($message->getSubject(), 'OTP') !== false) {
            Log::info('🔐 OTP Mail Detected', [
                'type' => 'OTP_VERIFICATION',
                'subject' => $message->getSubject(),
                'recipient' => $message->getTo() ? array_keys($message->getTo())[0] : 'Unknown',
                'sent_at' => now()->format('Y-m-d H:i:s'),
                'status' => 'SUCCESS',
            ]);
        }
    }
}
