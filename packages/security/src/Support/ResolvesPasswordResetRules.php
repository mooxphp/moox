<?php

declare(strict_types=1);

namespace Moox\Security\Support;

use Illuminate\Validation\Rules\Password;

trait ResolvesPasswordResetRules
{
    use ResolvesAuthPasswordContext;

    /**
     * @return array<int, Password|string>
     */
    protected function getPasswordResetValidationRules(): array
    {
        if ($this->panelAuthUsesWordPressPassword()) {
            return $this->getPressPasswordResetValidationRules();
        }

        $userConfig = config('user.password.validation');

        if ($userConfig === null || $userConfig === false) {
            return [];
        }

        if (is_array($userConfig)) {
            return [$this->passwordRuleFromConfig($userConfig)];
        }

        return $this->getPressPasswordResetValidationRules();
    }

    protected function getPasswordResetHelperText(): ?string
    {
        if ($this->panelAuthUsesWordPressPassword()) {
            $helperText = config('press.password.helperText');

            return is_string($helperText) && $helperText !== '' ? $helperText : null;
        }

        $userConfig = config('user.password.validation');

        if ($userConfig === null || $userConfig === false) {
            return null;
        }

        if (is_array($userConfig)) {
            $helperText = config('user.password.helperText');

            if (is_string($helperText) && $helperText !== '') {
                return $helperText;
            }

            $config = $this->resolvedUserPasswordConfig($userConfig);

            return __('core::user.password_helper_default', ['min' => $config['min']]);
        }

        $helperText = config('press.password.helperText');

        return is_string($helperText) && $helperText !== '' ? $helperText : null;
    }

    /**
     * @return array<int, Password|string>
     */
    protected function getPressPasswordResetValidationRules(): array
    {
        $pressRules = config('press.password.validation.rules');

        if ($pressRules instanceof Password) {
            return [$pressRules];
        }

        if (is_array($pressRules) && $pressRules !== []) {
            return $pressRules;
        }

        return [Password::min(8)->mixedCase()->numbers()->symbols()];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, bool|int>
     */
    protected function resolvedUserPasswordConfig(array $config): array
    {
        return array_merge([
            'min' => 8,
            'max' => 255,
            'mixed_case' => false,
            'numbers' => false,
            'symbols' => false,
            'uncompromised' => false,
        ], $config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function passwordRuleFromConfig(array $config): Password
    {
        $config = $this->resolvedUserPasswordConfig($config);

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
}
