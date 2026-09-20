<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A single generic, database-only notification class parameterized by type
 * rather than one class per event — this system only ever needs an
 * in-app notification center (no mail/SMS/broadcast channels), so a
 * dedicated class per notification type would just be boilerplate.
 */
class TalaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
