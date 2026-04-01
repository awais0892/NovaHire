<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorOtpCode extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $expiresInMinutes = 10
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('NovaHire Login Verification Code')
            ->greeting("Hi {$notifiable->name},")
            ->line('Use this verification code to complete your login:')
            ->line("**{$this->code}**")
            ->line("This code expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not try to sign in, you can ignore this email.');
    }
}

