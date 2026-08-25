<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Moox\MailOutbox\Models\MailTemplate;
use Moox\MailOutbox\Resources\MailTemplateResource;
use Moox\MailOutbox\Support\MailTemplatePreview;
use Moox\MailOutbox\Support\MailTemplateRenderer;
use Throwable;

class PreviewMailTemplateController
{
    public function __invoke(MailTemplate $mailTemplate, MailTemplateRenderer $renderer, MailTemplatePreview $preview): Response
    {
        abort_unless(MailTemplateResource::canView($mailTemplate), 403);

        $previewRoot = rtrim(request()->root(), '/');

        URL::forceRootUrl($previewRoot);
        URL::forceScheme(request()->getScheme());
        URL::useAssetOrigin($previewRoot);

        try {
            $html = $this->rewriteAssetHost(
                $renderer->toHtml($mailTemplate, $preview->viewData()),
                $previewRoot,
            );
            $html = $preview->highlight($html);
        } catch (Throwable $exception) {
            return response(
                '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Preview failed</title></head><body><p>'
                .e($exception->getMessage())
                .'</p></body></html>',
                500,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        } finally {
            $this->restoreApplicationUrl();
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function rewriteAssetHost(string $html, string $previewRoot): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl === '' || $appUrl === $previewRoot) {
            return $html;
        }

        return str_replace($appUrl, $previewRoot, $html);
    }

    private function restoreApplicationUrl(): void
    {
        $root = (string) config('app.url');
        URL::forceRootUrl($root);
        URL::useAssetOrigin(null);

        $scheme = parse_url($root, PHP_URL_SCHEME);

        if (is_string($scheme) && $scheme !== '') {
            URL::forceScheme($scheme);
        }
    }
}
