<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Task;

class TaskStatusUpdated extends Notification
{
    use Queueable;
    protected $task;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['custom_database'];
    }

    public function toCustomDatabase($notifiable)
    {
        return [
            'user_id' => $notifiable->id,
            'description' => 'El estado de la tarea ha sido actualizado a ' . $this->task->status,
            'action' => 'client_id', //$this->task->client_id // Ajusta esto según lo que necesites
            'type' => 'task',
            'data' => $this->task->id,
            'read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }


    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
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
            'task_id' => $this->task->id,
            'status' => $this->task->status,
            'message' => 'El estado de la tarea ha sido actualizado a '.$this->task->status,
        ];
    }
}
