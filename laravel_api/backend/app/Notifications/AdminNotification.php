<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AdminNotification extends Notification
{
    use Queueable;

    public $message;
    public $type;
    public $link;
    public $category;

    public function __construct($message, $type = 'info', $link = '#', $category = null)
    {
        $this->message = $message;
        $this->type = $type;
        $this->link = $link;
        $this->category = $category;
    }

    public function via(object $notifiable): array
    {
        if ($this->category) {
            // Bypass preference check for critical admission_new notifications during debugging
            if ($this->category === 'admission_new') {
                return ['database', 'broadcast'];
            }

            $settings = $notifiable->profile_settings['notifications'] ?? [];
            if (isset($settings[$this->category]) && ($settings[$this->category] == 'off' || $settings[$this->category] === false)) {
                return []; // Do not send if explicitly disabled
            }
        }
        
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'link' => $this->link,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => $this->message,
            'type' => $this->type,
            'link' => $this->link,
        ]);
    }
}
