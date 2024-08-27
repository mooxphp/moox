<?php

namespace Moox\UserDevice\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDeviceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $deviceDetails;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($deviceDetails)
    {
        $this->deviceDetails = $deviceDetails;
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
        return (new MailMessage)
            ->subject('New Device Registered')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new device has been registered on your account.')
            ->line('Device Details: '.$this->deviceDetails['title'])
            ->line('If this was not you, please secure your account immediately.')
            // TODO: Add a button to review devices or Magic Link or to secure account
            //->action('Review Devices', url('/user/devices'))
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
            'deviceDetails' => $this->deviceDetails,
        ];
    }
}
