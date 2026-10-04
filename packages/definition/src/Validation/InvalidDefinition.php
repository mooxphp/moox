<?php

declare(strict_types=1);

namespace Moox\Definition\Validation;

final class InvalidDefinition extends \InvalidArgumentException
{
    /**
     * @param  list<DefinitionIssue>  $issues
     */
    public function __construct(
        private readonly array $issues,
    ) {
        $messages = array_map(
            static fn (DefinitionIssue $issue): string => $issue->path().': '.$issue->message(),
            $issues,
        );

        parent::__construct($messages === [] ? 'Invalid definition.' : implode("\n", $messages));
    }

    public static function because(string $message): self
    {
        return new self([new DefinitionIssue('definition', $message)]);
    }

    /**
     * @return list<DefinitionIssue>
     */
    public function issues(): array
    {
        return $this->issues;
    }
}
