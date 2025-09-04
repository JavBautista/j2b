<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;

class ClientServiceNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $shop_id;

    public function __construct(Notification $notification, $shop_id)
    {
        $this->notification = $notification;
        $this->shop_id = $shop_id;
    }

    public function broadcastOn()
    {
        // CANAL POR TIENDA, no por usuario individual
        return new PrivateChannel('shop.'.$this->shop_id);
    }

    public function broadcastAs()
    {
        return 'client.service.notification';
    }
    
    public function broadcastWith()
    {
        return [
            'id' => $this->notification->id,
            'description' => $this->notification->description,
            'type' => $this->notification->type,
            'action' => $this->notification->action,
            'data' => $this->notification->data,
            'created_at' => $this->notification->created_at->toISOString(),
        ];
    }
}
