<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class LicenceNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $title;
    protected $description;
    protected $status;
    /**
     * Create a new notification instance.
     */
    public function __construct($title, $description)
    {
        //
        $this->title = $title;
        $this->description = $description;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', /*'database', */'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->title;
        $title = str_replace('<strong>', '', $title);
        $title = str_replace('</strong>', '', $title);
        $body = $this->description;
        $body = str_replace('<strong>', '', $body);
        $body = str_replace('</strong>', '', $body);
        return (new MailMessage)
                    ->line($title)
                    ->line(new HtmlString($body))
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
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
