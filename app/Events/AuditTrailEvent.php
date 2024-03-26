<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Http\Request;

class AuditTrailEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $description;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($title, $description)
    {
        //
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    // public function broadcastOn()
    // {
    //     // return new Channel('audit-trail-channel');
    //     return ['audit-trail-channel'];
    // }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    // public function broadcastAs()
    // {
    //     return 'audit-trail';
    // }
}
