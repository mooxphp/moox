<?php

declare(strict_types=1);

namespace Moox\LoginLink\Models;

use Illuminate\Validation\ValidationException;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Support\LinkProcessContext;

class LoginLinkProcess extends BaseRecordModel
{
    protected $table = 'login_link_processes';

    protected $attributes = [
        'context' => LinkProcessContext::AUTH,
        'invalidate_prior' => true,
    ];

    protected $fillable = [
        'title',
        'slug',
        'context',
        'mail_from',
        'content',
        'template_key',
        'handler_key',
        'expiry_minutes',
        'invalidate_prior',
    ];

    protected function casts(): array
    {
        return [
            'expiry_minutes' => 'integer',
            'invalidate_prior' => 'boolean',
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

            $context = (string) $process->context;

            if ($context === '' || ! LinkProcessContext::isValid($context)) {
                throw ValidationException::withMessages([
                    'context' => __('login-link::translations.context_invalid'),
                ]);
            }

            if (trim((string) $process->template_key) === '') {
                throw ValidationException::withMessages([
                    'template_key' => __('login-link::translations.template_key_required'),
                ]);
            }
        });
    }

    public function isAuthContext(): bool
    {
        return $this->context === LinkProcessContext::AUTH;
    }

    public function isPublicContext(): bool
    {
        return $this->context === LinkProcessContext::PUBLIC;
    }

    public function shouldInvalidatePrior(): bool
    {
        return (bool) $this->invalidate_prior;
    }

    public function resolveExpiryMinutes(): int
    {
        if ($this->expiry_minutes !== null) {
            return (int) $this->expiry_minutes;
        }

        return (int) config('login-link.expiration_minutes', 60);
    }
}
