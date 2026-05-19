<?php

declare(strict_types=1);

namespace Moox\User\Support;

use Illuminate\Validation\Rules\Password;

class PasswordValidation
{
    /**
     * Simple defaults when {@see config()} is empty or keys are omitted.
     *
     * @return array<string, bool|int>
     */
    public static function defaults(): array
    {
        return [
            'min' => 8,
            'max' => 255,
            'mixed_case' => false,
            'numbers' => false,
            'symbols' => false,
            'uncompromised' => false,
        ];
    }

    public static function isEnabled(): bool
    {
        $config = config('user.password.validation');

        return $config !== null && $config !== false;
    }

    /**
     * @return array<string, bool|int>
     */
    public static function resolvedConfig(): array
    {
        if (! self::isEnabled()) {
            return [];
        }

        $config = config('user.password.validation');

        if (! is_array($config)) {
            return self::defaults();
        }

        return array_merge(self::defaults(), $config);
    }

    /**
     * @return array<int, Password>
     */
    public static function rules(): array
    {
        if (! self::isEnabled()) {
            return [];
        }

        return [self::rule()];
    }

    public static function rule(): Password
    {
        $config = self::resolvedConfig();

        $rule = Password::min((int) $config['min'])
            ->max((int) $config['max']);

        if ($config['mixed_case'] === true) {
            $rule = $rule->mixedCase();
        }

        if ($config['numbers'] === true) {
            $rule = $rule->numbers();
        }

        if ($config['symbols'] === true) {
            $rule = $rule->symbols();
        }

        if ($config['uncompromised'] === true) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }

    public static function helperText(): ?string
    {
        if (! self::isEnabled()) {
            return null;
        }

        $helperText = config('user.password.helperText');

        if (is_string($helperText) && $helperText !== '') {
            return $helperText;
        }

        $min = (int) self::resolvedConfig()['min'];

        return __('core::user.password_helper_default', ['min' => $min]);
    }
}
