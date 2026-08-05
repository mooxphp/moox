<?php

declare(strict_types=1);

namespace Moox\LoginLink\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;

class LoginLinkProcess extends Model
{
    protected $table = 'login_link_processes';

    protected $fillable = [
        'title',
        'slug',
        'mail_from',
        'content',
        'handler_key',
        'expiry_minutes',
    ];

    protected function casts(): array
    {
        return [
            'expiry_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (LoginLinkProcess $process): void {
            $handlerKey = (string) $process->handler_key;

            if ($handlerKey === '' || ! app(RedemptionHandlerRegistry::class)->has($handlerKey)) {
                throw ValidationException::withMessages([
                    'handler_key' => __('login-link::translations.handler_key_unregistered'),
                ]);
            }
        });
    }

    public function resolveExpiryMinutes(): int
    {
        if ($this->expiry_minutes !== null) {
            return (int) $this->expiry_minutes;
        }

        return (int) config('login-link.expiration_minutes', 60);
    }
}
