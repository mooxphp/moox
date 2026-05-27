<?php

declare(strict_types=1);

namespace Moox\Demo\Demo\Steps;

use Illuminate\Console\Command;
use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Demo\DemoContext;

final class DemoUserStep
{
    public function __construct(
        private readonly Command $command,
        private readonly DemoConsole $console,
    ) {}

    public function run(DemoContext $context): void
    {
        if (class_exists(\Moox\User\Database\Seeders\UserSeeder::class)) {
            if ($this->command->getOutput()->isVerbose()) {
                $this->console->detail('Demo user step skipped — UserSeeder handles demo users.');
            }

            return;
        }

        $config = config('demo.demo_user', []);

        if (! ($config['enabled'] ?? true)) {
            $this->console->skip('Demo user', 'disabled in config');

            return;
        }

        $userClass = config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($userClass)) {
            $this->console->skip('Demo user', 'user model not found');

            return;
        }

        if ($userClass::query()->exists()) {
            if ($this->command->getOutput()->isVerbose()) {
                $this->console->skip('Demo user', 'users already exist');
            }

            return;
        }

        $email = (string) ($config['email'] ?? 'demo@moox.org');
        $name = (string) ($config['name'] ?? 'Moox Demo');

        $this->console->beginNestedOutput('Demo user');

        $userClass::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make((string) ($config['password'] ?? 'password')),
            'email_verified_at' => now(),
        ]);

        $this->console->created("User {$email}");
        $this->console->finishTask('Demo user');
    }
}
