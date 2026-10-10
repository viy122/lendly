<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.config('app.name').' password reset code')
            ->line('Use this code to reset your password:')
            ->line($this->code)
            ->line('This code expires in 10 minutes and can only be used once.')
            ->action('Reset password', route('password.reset', ['email' => $notifiable->email]))
            ->line('After 3 incorrect passwords or codes, your account is locked for 15 minutes.')
            ->line('If you did not request a password reset, you can ignore this email.');
    }
}
