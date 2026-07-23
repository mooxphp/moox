<?php

declare(strict_types=1);

namespace Moox\Static\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Static\Models\StaticEntry;

/**
 * @extends Factory<StaticEntry>
 */
class StaticEntryFactory extends Factory
{
    protected $model = StaticEntry::class;

    /** @var list<string> */
    private const LOCALES = ['en_US', 'de_DE'];

    public function definition(): array
    {
        return [
            'code' => 'SE-'.strtoupper(substr(str_replace('-', '', (string) str()->uuid()), 0, 8)),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (StaticEntry $entry): void {
            foreach (self::LOCALES as $locale) {
                $entry->translateOrNew($locale)->fill([
                    'common_name' => $this->faker->words(3, true).' ('.$locale.')',
                    'description' => $this->faker->optional()->sentence(),
                ]);
            }

            $entry->save();
        });
    }
}
