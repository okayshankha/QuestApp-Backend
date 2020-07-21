<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationToStudent extends Notification
{
    use Queueable;

    private $recipient, $payload;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($recipient, $payload)
    {
        $this->recipient = $recipient;
        $this->payload = $payload;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $sender = request()->user();
        return (new MailMessage)
            ->greeting("Hello, {$this->recipient->name}")
            ->line("{$sender->name} has invited you to the {$this->payload["type"]} '{$this->payload["data"]->name}'")
            ->action('Join Class', url('/'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
