<?php

declare(strict_types=1);

namespace Moox\Page\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Moox\Page\Models\PageTranslation;
use Moox\Page\Support\PageModels;
use Moox\Page\Support\PagePermalink;

#[Signature('pages:normalize-permalinks')]
#[Description('Normalize page translation permalinks to /{slug} format')]
class NormalizePagePermalinks extends Command
{
    public function handle(): int
    {
        $updated = 0;

        PageModels::pageTranslation()::query()
            ->orderBy('id')
            ->each(function (PageTranslation $translation) use (&$updated): void {
                $slug = $translation->slug;

                if (! is_string($slug) || $slug === '') {
                    return;
                }

                $normalizedPermalink = PagePermalink::fromSlug($slug);

                if ($translation->permalink === $normalizedPermalink) {
                    return;
                }

                $translation->update([
                    'permalink' => $normalizedPermalink,
                ]);

                $updated++;
            });

        $this->components->info("Normalized {$updated} page translation permalink(s).");

        return self::SUCCESS;
    }
}
