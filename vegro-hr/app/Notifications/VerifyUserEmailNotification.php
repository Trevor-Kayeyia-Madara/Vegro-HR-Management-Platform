<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyUserEmailNotification extends VerifyEmail
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Vegro HR Account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Please verify your email address before using your account.')
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not create this account, no further action is required.');
    }
}

