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
            ->subject(__('core::device.new_device_registered'))
            ->greeting(__('core::notifications.hello').$notifiable->name.',')
            ->line(__('core::device.new_device_registered_message'))
            ->line(__('core::device.device_details').': '.$this->deviceDetails['title'])
            ->line(__('core::device.if_not_you_secure_account'));
        // TODO: Add a button to review devices (need user profile) or Magic Link or to secure account
        // ->action('Review Devices', url('/user/devices'))
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
