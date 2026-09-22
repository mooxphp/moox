<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;
use RuntimeException;

final class EnsureTestTemplate
{
    public function ensure(?int $sourceLayoutId, ?string $mailContent = null): MailTemplate
    {
        $template = MailTemplate::query()
            ->with(['translations', 'mailLayout.translations'])
            ->where('slug', (string) config('mail-testing.template_slug', 'test'))
            ->first();
        $content = $this->resolvedMailContent($mailContent);
        $layout = $this->layoutFor($sourceLayoutId, $template);

        if (! $template instanceof MailTemplate) {
            $template = new MailTemplate;
            $template->slug = (string) config('mail-testing.template_slug', 'test');
            $template->status = 'draft';
        }

        $template->mail_layout_id = $layout->getKey();
        $template->setRelation('mailLayout', $layout);
        $template->save();
        $this->writeTemplateCopy($template, $content);

        return $template->fresh(['translations', 'mailLayout.translations']) ?? $template;
    }

    public static function currentOrDefaultContent(): string
    {
        $template = MailTemplate::query()
            ->with('translations')
            ->where('slug', (string) config('mail-testing.template_slug', 'test'))
            ->first();

        if ($template instanceof MailTemplate) {
            foreach ($template->translations as $translation) {
                $content = $translation->getAttribute('mail_content');

                if (is_string($content) && trim($content) !== '') {
                    return $content;
                }
            }
        }

        return self::defaultMailContent();
    }

    public static function defaultMailContent(): string
    {
        return <<<'MJML'
<mj-text font-size="28px" font-weight="600" line-height="36px" padding="0 0 20px">MJML Rendering Test</mj-text>
<mj-text padding="0 0 16px">{anrede}</mj-text>
<mj-text padding="0 0 8px">{displayName}</mj-text>
<mj-text padding="0 0 24px">{firstName} {lastName}</mj-text>
MJML;
    }

    private function layoutFor(?int $sourceLayoutId, ?MailTemplate $template): MailLayout
    {
        if ($sourceLayoutId !== null && $sourceLayoutId > 0) {
            $layout = MailLayout::query()->with('translations')->find($sourceLayoutId);

            if (! $layout instanceof MailLayout) {
                throw new RuntimeException(__('mail-testing::translations.layout_missing'));
            }

            return $layout;
        }

        $current = $template?->mailLayout;

        if ($current instanceof MailLayout) {
            return $current;
        }

        throw new RuntimeException(__('mail-testing::translations.layout_required'));
    }

    private function writeTemplateCopy(MailTemplate $template, string $mailContent): void
    {
        $locales = $template->mailLayout?->translations
            ->map(fn (Model $translation): string => (string) $translation->getAttribute('locale'))
            ->filter()
            ->values()
            ?? collect();

        if ($locales->isEmpty()) {
            $locales = collect(['de_DE']);
        }

        foreach ($locales as $locale) {
            $translation = $template->translateOrNew((string) $locale);
            $translation->fill([
                'title' => 'MJML Rendering Test',
                'mail_content' => $mailContent,
                'footer' => $translation->footer,
            ])->save();
        }
    }

    private function resolvedMailContent(?string $mailContent): string
    {
        if (is_string($mailContent) && trim($mailContent) !== '') {
            return $mailContent;
        }

        return self::defaultMailContent();
    }
}
