<?php

declare(strict_types=1);

namespace Moox\Page\Observers;

use Moox\Page\Models\Page;
use Moox\Page\Models\PageTranslation;
use Moox\Page\Support\PageResponseCache;

class PageCacheObserver
{
    public function __construct(
        private readonly PageResponseCache $responseCache,
    ) {}

    public function saved(Page|PageTranslation $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Page|PageTranslation $model): void
    {
        $this->invalidate($model);
    }

    public function restored(Page|PageTranslation $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Page|PageTranslation $model): void
    {
        unset($model);

        $this->responseCache->forgetAll();
    }
}
