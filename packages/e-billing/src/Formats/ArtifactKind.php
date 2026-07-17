<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats;

enum ArtifactKind: string
{
    case Xml = 'xml';
    case Pdf = 'pdf';
}
