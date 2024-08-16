<?php

namespace Moox\Security\Commands;

use Illuminate\Console\Command;
use Moox\Security\Jobs\Passwords\SendPasswordResetLinksJob;

class GetPasswordResetLinksCommand extends Command
{
    protected $signature = 'users:generate-reset-links';

    protected $description = 'Generate password reset links for all users';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting Sending Reset Password Links');

        SendPasswordResetLinksJob::dispatch();

        $this->info('Password Reset Links sent successfully.');
    }
}
