<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Moox\MailTemplate\MailTemplateServiceProvider;
use ReflectionClass;

final class LoadMailTemplateMigrations
{
    public static function run(): void
    {
        if (Schema::hasTable('mail_layouts')) {
            return;
        }
        $directory = dirname((new ReflectionClass(MailTemplateServiceProvider::class))->getFileName())
            .DIRECTORY_SEPARATOR.'..'
            .DIRECTORY_SEPARATOR.'database'
            .DIRECTORY_SEPARATOR.'migrations';

        foreach ([
            'create_mail_layouts_table.php.stub',
            'create_mail_layout_translations_table.php.stub',
            'create_mail_templates_table.php.stub',
            'create_mail_template_translations_table.php.stub',
        ] as $file) {
            $path = $directory.DIRECTORY_SEPARATOR.$file;
            $migration = require $path;

            if ($migration instanceof Migration) {
                $migration->up();
            }
        }
    }
}
