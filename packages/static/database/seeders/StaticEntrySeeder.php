<?php

declare(strict_types=1);

namespace Moox\Static\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\Static\Models\StaticEntry;

class StaticEntrySeeder extends Seeder
{
    /** @var list<string> */
    private const LOCALES = ['en_US', 'de_DE'];

    public function run(): void
    {
        $entries = [
            ['code' => 'REF-001', 'en' => 'Reference Entry One', 'de' => 'Referenzeintrag Eins'],
            ['code' => 'REF-002', 'en' => 'Reference Entry Two', 'de' => 'Referenzeintrag Zwei'],
            ['code' => 'REF-003', 'en' => 'Reference Entry Three', 'de' => 'Referenzeintrag Drei'],
        ];

        foreach ($entries as $entryData) {
            $entry = StaticEntry::query()->firstOrCreate([
                'code' => $entryData['code'],
            ]);

            $entry->translateOrNew('en_US')->fill([
                'common_name' => $entryData['en'],
                'description' => 'Seeded reference entry '.$entryData['code'],
            ]);
            $entry->translateOrNew('de_DE')->fill([
                'common_name' => $entryData['de'],
                'description' => 'Geseedeter Referenzeintrag '.$entryData['code'],
            ]);

            $entry->save();
        }
    }
}
