<?php

namespace Moox\Core\Support\Scopes;

use InvalidArgumentException;
use Livewire\Wireable;
use Moox\Core\Services\ScopeRegistry;

readonly class ScopeValue implements Wireable
{
    public const MODE_PRIVATE = 'private';

    public const MODE_PUBLIC = 'public';

    public const MODE_GROUP = 'group';

    public const MODE_USER = 'user';

    public const MODE_USER_TYPE = 'user_type';

    public function __construct(
        private string $origin,
        private string $source,
        private string $context,
        private string $boundary,
    ) {}

    public static function make(string $origin, string $source, string $context, string $boundary): self
    {
        return new self($origin, $source, $context, self::normalizeBoundary($boundary));
    }

    public static function parse(string | self | null $scope): ?self
    {
        if ($scope instanceof self) {
            return $scope;
        }

        if (blank($scope)) {
            return null;
        }

        $segments = explode(':', $scope, 4);

        if (count($segments) !== 4 || in_array('', $segments, true)) {
            throw new InvalidArgumentException("Invalid scope [{$scope}]. Expected format [origin:source:context:boundary].");
        }

        return self::make(...$segments);
    }

    public static function toStringOrNull(string | self | null $scope): ?string
    {
        $parsedScope = self::parse($scope);

        return $parsedScope ? (string) $parsedScope : null;
    }

    public static function forOrigin(string | self | null $scope, string $origin): ?self
    {
        return self::parse($scope)?->withOrigin($origin);
    }

    public static function forOriginString(string | self | null $scope, string $origin): ?string
    {
        return self::toStringOrNull(self::forOrigin($scope, $origin));
    }

    public static function deriveChildString(
        string | self | null $scope,
        string $origin,
        ?string $context = null,
        ?string $boundary = null,
        ?string $source = null,
    ): ?string {
        return self::toStringOrNull(
            self::parse($scope)?->deriveChild(
                origin: $origin,
                context: $context,
                boundary: $boundary,
                source: $source,
            )
        );
    }

    public static function forKey(
        string $key,
        ?string $boundary = null,
        ?string $source = null,
        ?string $context = null,
    ): self {
        return self::make(
            $key,
            $source ?: $key,
            $context ?: ($source ?: $key),
            $boundary ?: self::MODE_PRIVATE,
        );
    }

    public static function forKeyString(
        string $key,
        ?string $boundary = null,
        ?string $source = null,
        ?string $context = null,
    ): string {
        return (string) self::forKey($key, $boundary, $source, $context);
    }

    /**
     * @return list<string>
     */
    public static function allowedBoundaries(): array
    {
        return [
            self::MODE_PRIVATE,
            self::MODE_PUBLIC,
            self::MODE_GROUP,
            self::MODE_USER,
            self::MODE_USER_TYPE,
        ];
    }

    public static function isAllowedBoundary(string $boundary): bool
    {
        return in_array($boundary, self::allowedBoundaries(), true);
    }

    public static function normalizeBoundary(string $boundary): string
    {
        if (! self::isAllowedBoundary($boundary)) {
            throw new InvalidArgumentException(
                "Invalid scope boundary [{$boundary}]. Allowed boundaries: [".implode(', ', self::allowedBoundaries()).'].'
            );
        }

        return $boundary;
    }

    public function origin(): string
    {
        return $this->origin;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function boundary(): string
    {
        return $this->boundary;
    }

    public function hasBoundary(string $boundary): bool
    {
        return $this->boundary === self::normalizeBoundary($boundary);
    }

    public function contextPrefix(): string
    {
        return implode(':', [
            $this->origin,
            $this->source,
            $this->context,
        ]);
    }

    public function contextLikePattern(): string
    {
        return $this->contextPrefix().':%';
    }

    public function matchesContext(string | self $other): bool
    {
        $otherScope = self::parse($other);

        return $otherScope !== null && $this->contextPrefix() === $otherScope->contextPrefix();
    }

    public function matchesExact(string | self $other): bool
    {
        return $this->equals($other);
    }

    public function originModel(): ?string
    {
        return app(ScopeRegistry::class)->resolveOriginModel($this->origin);
    }

    public function sourceModel(): ?string
    {
        return app(ScopeRegistry::class)->resolveSourceModel($this->source);
    }

    public function withOrigin(string $origin): self
    {
        return self::make(
            $origin,
            $this->source,
            $this->context,
            $this->boundary,
        );
    }

    public function withSource(string $source): self
    {
        return self::make(
            $this->origin,
            $source,
            $this->context,
            $this->boundary,
        );
    }

    public function withContext(string $context): self
    {
        return self::make(
            $this->origin,
            $this->source,
            $context,
            $this->boundary,
        );
    }

    public function withBoundary(string $boundary): self
    {
        return self::make(
            $this->origin,
            $this->source,
            $this->context,
            $boundary,
        );
    }

    public function deriveChild(
        string $origin,
        ?string $context = null,
        ?string $boundary = null,
        ?string $source = null,
    ): self {
        $scope = $this->withOrigin($origin);

        if (filled($source)) {
            $scope = $scope->withSource($source);
        }

        if (filled($context)) {
            $scope = $scope->withContext($context);
        }

        if (filled($boundary)) {
            $scope = $scope->withBoundary($boundary);
        }

        return $scope;
    }

    public function isPublic(): bool
    {
        return $this->hasBoundary(self::MODE_PUBLIC);
    }

    public function isPrivate(): bool
    {
        return $this->hasBoundary(self::MODE_PRIVATE);
    }

    public function equals(string | self $other): bool
    {
        $otherScope = self::parse($other);

        return $otherScope !== null && ((string) $this === (string) $otherScope);
    }

    /**
     * @return array{origin: string, source: string, context: string, boundary: string}
     */
    public function toArray(): array
    {
        return [
            'origin' => $this->origin,
            'source' => $this->source,
            'context' => $this->context,
            'boundary' => $this->boundary,
        ];
    }

    public function __toString(): string
    {
        return implode(':', [
            $this->origin,
            $this->source,
            $this->context,
            $this->boundary,
        ]);
    }

    /**
     * @return array{origin: string, source: string, context: string, boundary: string}
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire($value): ?self
    {
        if (is_array($value)) {
            $origin = (string) ($value['origin'] ?? '');
            $source = (string) ($value['source'] ?? '');
            $context = (string) ($value['context'] ?? '');
            $boundary = (string) ($value['boundary'] ?? '');

            if (blank($origin) || blank($source) || blank($context) || blank($boundary)) {
                return null;
            }

            return self::make($origin, $source, $context, $boundary);
        }

        return self::parse(is_string($value) ? $value : null);
    }
}

