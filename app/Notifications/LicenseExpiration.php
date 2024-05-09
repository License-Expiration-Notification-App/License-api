<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiration extends Notification implements ShouldQueue
{
    use Queueable;
    public $title;
    public $description;
    public $status;
    /**
     * Create a new notification instance.
     */
    public function __construct($title, $description, $status)
    {
        //
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', /*'broadcast'*/];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line($this->title)
                    ->line($this->description)
                    // ->action('Notification Action', url('/'))
                    ->line('Kindly disregard this mail if it does not concern you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tag' => 'License Expiration Notice',
            'status' => $this->status,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
