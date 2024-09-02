<?php

namespace Moox\Core\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

trait LogLevel
{
    protected function verboseLog($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    protected function logInfo($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    protected function log($level, $message, array $context = [])
    {
        $verboseLevel = Config::get('core.logging.verbose_level', 0);
        $logInProduction = Config::get('core.logging.log_in_production', false);

        if (
            ($verboseLevel > 0 && app()->environment() !== 'production') ||
            ($logInProduction && app()->environment() === 'production')
        ) {
            if (
                ($level === 'debug' && $verboseLevel >= 1) ||
                ($level === 'info' && $verboseLevel >= 2) ||
                $verboseLevel >= 3
            ) {
                Log::$level($message, $context);
            }
        }
    }
}
