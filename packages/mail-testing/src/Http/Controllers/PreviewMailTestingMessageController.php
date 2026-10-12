<?php

declare(strict_types=1);

namespace Moox\MailTesting\Http\Controllers;

use Illuminate\Http\Response;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Resources\MailTestingMessageResource;

class PreviewMailTestingMessageController
{
    public function __invoke(MailTestingMessage $mailTestingMessage): Response
    {
        abort_unless(MailTestingMessageResource::canView($mailTestingMessage), 403);

        $html = $mailTestingMessage->resolvedHtml();

        abort_if(! is_string($html) || $html === '', 404);

        $previewRoot = rtrim((string) request()->root(), '/');

        return response(
            $this->rewriteAssetHost($html, $previewRoot),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    private function rewriteAssetHost(string $html, string $previewRoot): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl === '' || $appUrl === $previewRoot) {
            return $html;
        }

        return str_replace($appUrl, $previewRoot, $html);
    }
}
