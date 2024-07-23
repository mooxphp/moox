<?php

namespace Moox\Press\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateWordPressURL extends Command
{
    protected $signature = 'mooxpress:updateurl';

    protected $description = 'Update WordPress Site URL in wp_options and all database tables.';

    public function handle()
    {
        $prefix = config('press.wordpress_prefix');

        $oldUrl = DB::table($prefix.'options')->where('option_name', 'siteurl')->value('option_value');
        if (! $oldUrl) {
            $this->error('Old URL could not be found.');

            return;
        }

        $newUrl = $this->ask('What is the new URL?', config('app.url').config('press.wordpress_slug'));
        if (! $newUrl) {
            $this->error('New URL is not defined.');

            return;
        }

        DB::table($prefix.'options')->where('option_name', 'siteurl')->update(['option_value' => $newUrl]);
        DB::table($prefix.'options')->where('option_name', 'home')->update(['option_value' => $newUrl]);

        $this->info("URL in wp_options updated from $oldUrl to $newUrl.");

        $query = "SHOW TABLES LIKE '".$prefix."%'";
        $tables = DB::select($query);

        foreach ($tables as $table) {
            foreach ($table as $tableName) {
                $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

                foreach ($columns as $column) {
                    DB::table($tableName)
                        ->where($column, 'like', "%$oldUrl%")
                        // TODO: Test and improve this query
                        // https://stackoverflow.com/questions/52229588/laravel-eloquent-how-to-query-not-like
                        ->where($column, 'not like', "%$newUrl%")
                        ->update([
                            $column => DB::raw("REPLACE($column, '$oldUrl', '$newUrl')"),
                        ]);
                }
            }
        }

        $this->info('URLs in all database tables updated.');
    }
}
