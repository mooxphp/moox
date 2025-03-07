<?php

declare(strict_types=1);

namespace Moox\Connect\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Moox\Connect\Models\ApiConnection;

final class ApiErrorNotification extends Notification
{
    public function __construct(
        private ApiConnection $api,
        private string $error
    ) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("API Error: {$this->api->name}")
            ->line("There was an error with the API connection: {$this->api->name}")
            ->line("Error: {$this->error}");
    }
}
