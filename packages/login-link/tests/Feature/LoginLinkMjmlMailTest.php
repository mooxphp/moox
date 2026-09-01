<?php

declare(strict_types=1);

it('renders the login-link mail as mjml with database fragments', function (): void {
    $mjml = view('login-link::mail.login-link', [
        'user' => (object) ['last_name' => 'Müller', 'name' => 'Müller'],
        'logoUrl' => 'https://example.test/logo.png',
        'brandName' => 'Acme',
        'headline' => 'Login',
        'mailContent' => '<mj-text>Bitte einloggen.</mj-text><mj-button href="https://example.test/login">Login</mj-button>',
        'magicLink' => 'https://example.test/login',
        'url' => 'https://example.test/login',
        'footer' => '<mj-text>Footer-MJML</mj-text>',
        'expiresMinutes' => 60,
    ])->render();

    preg_match_all('/<mj-section\b[\s\S]*?<\/mj-section>/', $mjml, $matches);

    expect($mjml)
        ->toContain('<mjml>')
        ->toContain('Bitte einloggen.')
        ->toContain('Footer-MJML')
        ->toContain('https://example.test/login')
        ->not->toContain('font-size="28px"')
        ->not->toContain(__('login-link::translations.mail_intro'))
        ->not->toContain(__('login-link::translations.mail_security_hint'));

    expect($matches[0])->toHaveCount(3);
    expect($matches[0][1])->toContain('Bitte einloggen.');
    expect($matches[0][1])->toContain('https://example.test/login');
    expect($matches[0][1])->not->toContain('Müller');
    expect($matches[0][2])->toContain('Footer-MJML');
});

it('renders the default login-link body when mail content is empty', function (): void {
    $mjml = view('login-link::mail.content', [
        'user' => (object) ['last_name' => 'Müller', 'name' => 'Müller'],
        'headline' => 'Login',
        'magicLink' => 'https://example.test/login',
        'expiresMinutes' => 60,
    ])->render();

    expect($mjml)
        ->toContain('Login')
        ->toContain(__('login-link::translations.mail_greeting'))
        ->toContain('Müller')
        ->toContain(__('login-link::translations.mail_intro'))
        ->toContain('https://example.test/login')
        ->toContain('css-class="mail-button"')
        ->toContain('ignore it');
});

it('replaces the default login-link body completely when mail content is set', function (): void {
    $mjml = view('login-link::mail.content', [
        'user' => (object) ['last_name' => 'Müller', 'name' => 'Müller'],
        'headline' => 'Login',
        'mailContent' => '<mj-text>Bitte einloggen.</mj-text>',
        'magicLink' => 'https://example.test/login',
        'expiresMinutes' => 60,
    ])->render();

    expect($mjml)
        ->toContain('Bitte einloggen.')
        ->not->toContain('font-size="28px"')
        ->not->toContain(__('login-link::translations.mail_intro'))
        ->not->toContain(__('login-link::translations.mail_security_hint'))
        ->not->toContain('css-class="mail-button"')
        ->not->toContain('https://example.test/login')
        ->not->toContain('Müller');
});
