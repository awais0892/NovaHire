<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CandidateRegistrationVerification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'register.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Verify Your NovaHire Email')
            ->greeting("Hi {$notifiable->name},")
            ->line('Welcome to NovaHire. Verify your email to activate your candidate account.')
            ->action('Verify Email', $verificationUrl)
            ->line('This verification link expires in 60 minutes.')
            ->line('If you did not create this account, you can ignore this email.');
    }
}
