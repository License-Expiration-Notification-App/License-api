<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuditTrail extends Notification implements ShouldQueue
{
    use Queueable;
    protected $title;
    protected $description;
    protected $type;
    protected $color_code;
    /**
     * Create a new notification instance.
     */
    public function __construct($title, $description, $type='Authentication', $action_type= 'add')
    {
        //
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->setColorCode($action_type);
    }
    private function setColorCode($type)
    {
        
        switch ($type) {
            case 'add':
                $code = '#039855';
                break;
            case 'edit':
                    $code = '#F79009';
                    break;
            case 'remove':
                    $code = '#D92D20';
                    break;
            default:
                $code = '#64748B';
                break;
        }
        $this->color_code = $code;
    }
    public function databaseType(object $notifiable): string
    {
        return $this->type;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
    return [/*'mail',*/'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        $title = $this->title;
        $title = str_replace('<strong>', '', $title);
        $title = str_replace('</strong>', '', $title);
        $body = $this->description;
        $body = str_replace('<strong>', '', $body);
        $body = str_replace('</strong>', '', $body);

        try {
            return (new MailMessage)
                    ->line($title)
                    ->line($body)
                    // ->action('Notification Action', url('/'))
                    ->line('Kindly disregard this mail if it does not concern you!');
        } catch (\Throwable $th) {
            return response()->json(['message',$th]);
        }
        
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tag' => 'Audit Trail',
            'title' => $this->title,
            'description' => $this->description,
            'color_code' => $this->color_code,
        ];
    }
}
