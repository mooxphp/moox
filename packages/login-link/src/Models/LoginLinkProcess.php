<?php

declare(strict_types=1);

namespace Moox\LoginLink\Models;

use Illuminate\Validation\ValidationException;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Support\LinkProcessContext;

class LoginLinkProcess extends BaseRecordModel
{
    /**
     * Soft-coupling entry point for optional moox/mail-template.
     * Consumers outside this package should use MailTemplateBridge directly.
     */
    public const MAIL_TEMPLATE_BRIDGE = 'Moox\\MailTemplate\\Support\\MailTemplateBridge';

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

    /**
     * @return class-string|null
     */
    public static function mailTemplateBridge(): ?string
    {
        $bridge = self::MAIL_TEMPLATE_BRIDGE;

        if (! class_exists($bridge)
            || ! (bool) config('login-link.mail_template.enabled', true)
            || ! $bridge::isAvailable()) {
            return null;
        }

        return $bridge;
    }

    public static function usesMailTemplate(): bool
    {
        return self::mailTemplateBridge() !== null;
    }

    public static function defaultMailTemplateContent(): string
    {
        $intro = htmlspecialchars(__('login-link::translations.mail_intro'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $cta = htmlspecialchars(__('login-link::translations.mail_cta'), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<mj-text>'.$intro.'</mj-text><mj-button href="{magicLink}">'.$cta.'</mj-button>';
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

            $templateKey = trim((string) $process->template_key);
            $process->template_key = $templateKey === '' ? null : $templateKey;

            if (self::usesMailTemplate() && $process->template_key === null) {
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
