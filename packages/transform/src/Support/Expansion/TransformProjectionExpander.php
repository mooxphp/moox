<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Expansion;

use Illuminate\Support\Arr;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\ConfiguredImportRecordPayloadReader;
use Moox\Transform\Support\ConfiguredLocaleVariantResolver;
use Moox\Transform\Support\DbTableSourceQuery;
use Moox\Transform\Support\SourcePayloadResolver;
use Moox\Transform\Support\TemplateValueResolver;

final class TransformProjectionExpander
{
    public function __construct(
        private readonly SourcePayloadResolver $sourcePayloadResolver,
        private readonly ConfiguredImportRecordPayloadReader $importRecordPayloadReader,
        private readonly ConfiguredLocaleVariantResolver $localeVariantResolver,
        private readonly TemplateValueResolver $templateValueResolver,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function expand(TransformRecord $record, TransformDefinition $definition): array
    {
        $baseProjections = $this->resolveBaseProjections($record, $definition);
        $expand = $this->expandConfig($definition);

        if ($expand === []) {
            return $baseProjections;
        }

        $projections = $baseProjections;

        if (($expand['dedupe_by'] ?? null) !== null) {
            $projections = $this->dedupeProjections($projections, $expand);
        }

        if (is_array($expand['nested'] ?? null) && $expand['nested'] !== []) {
            $projections = $this->expandNested($projections, $expand['nested']);
        }

        if (is_array($expand['locales'] ?? null) && $expand['locales'] !== []) {
            $projections = $this->expandLocales($projections, $expand['locales']);
        }

        return $projections;
    }

    /**
     * @return iterable<int, list<array<string, mixed>>>
     */
    public function expandInChunks(TransformRecord $record, TransformDefinition $definition, int $chunkSize): iterable
    {
        $chunkSize = max(1, $chunkSize);

        if (! $this->shouldUseCursorStrategy($record, $definition)) {
            foreach (array_chunk($this->expand($record, $definition), $chunkSize) as $chunk) {
                yield $chunk;
            }

            return;
        }

        $iterableReference = $this->resolveIterableSourceReference($record, $definition);
        if (! is_array($iterableReference) || ($iterableReference['source_type'] ?? null) !== 'db_table') {
            foreach (array_chunk($this->expand($record, $definition), $chunkSize) as $chunk) {
                yield $chunk;
            }

            return;
        }

        $definitionReferences = $this->arrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->arrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;
        $connection = $iterableReference['connection'] ?? null;
        $keyColumn = is_string($iterableReference['key_column'] ?? null) && $iterableReference['key_column'] !== ''
            ? $iterableReference['key_column']
            : 'id';

        $query = DbTableSourceQuery::table(
            is_string($connection) ? $connection : null,
            $iterableReference,
        );
        DbTableSourceQuery::applyWhereClauses($query, $iterableReference);

        if (is_array($iterableReference['columns'] ?? null) && $iterableReference['columns'] !== []) {
            $query->select($iterableReference['columns']);
        }

        $alias = is_string($iterableReference['alias'] ?? null) && $iterableReference['alias'] !== ''
            ? $iterableReference['alias']
            : null;
        $sharedProjection = $this->resolveSharedDbTableProjection($record, $definition, $references);

        foreach (DbTableSourceQuery::orderedChunk($query, $keyColumn, $chunkSize) as $rows) {
            $chunk = [];

            foreach ($rows as $rowData) {
                $merged = $sharedProjection;

                if ($alias !== null) {
                    $merged[$alias] = $rowData;
                }

                $merged = array_replace_recursive($merged, $rowData);
                if ($merged !== []) {
                    $chunk[] = $merged;
                }
            }

            if ($chunk !== []) {
                yield $chunk;
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveBaseProjections(TransformRecord $record, TransformDefinition $definition): array
    {
        $iterableReference = $this->resolveIterableSourceReference($record, $definition);

        if ($iterableReference === null) {
            $resolution = $this->sourcePayloadResolver->resolve($record, $definition);

            return $resolution['payload'] === [] ? [] : [$resolution['payload']];
        }

        return match ($iterableReference['source_type'] ?? null) {
            'db_table' => $this->resolveDbTableProjections($record, $definition, $iterableReference),
            'api_import_record' => $this->resolveImportRecordProjections($record, $definition, $iterableReference),
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveDbTableProjections(
        TransformRecord $record,
        TransformDefinition $definition,
        array $reference,
    ): array {
        $definitionReferences = $this->arrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->arrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;

        $connection = $reference['connection'] ?? null;
        $keyColumn = is_string($reference['key_column'] ?? null) && $reference['key_column'] !== ''
            ? $reference['key_column']
            : 'id';

        $query = DbTableSourceQuery::table(
            is_string($connection) ? $connection : null,
            $reference,
        );
        DbTableSourceQuery::applyWhereClauses($query, $reference);
        $query->orderBy($keyColumn);

        if (is_array($reference['columns'] ?? null) && $reference['columns'] !== []) {
            $query->select($reference['columns']);
        }

        $alias = is_string($reference['alias'] ?? null) && $reference['alias'] !== ''
            ? $reference['alias']
            : null;
        $sharedProjection = $this->resolveSharedDbTableProjection($record, $definition, $references);
        $projections = [];

        foreach ($query->get() as $row) {
            $rowData = DbTableSourceQuery::normalizeRow((array) $row);
            $merged = $sharedProjection;

            if ($alias !== null) {
                $merged[$alias] = $rowData;
            }

            $merged = array_replace_recursive($merged, $rowData);

            if ($merged !== []) {
                $projections[] = $merged;
            }
        }

        return $projections;
    }

    /**
     * @param  array<int, mixed>  $references
     * @return array<string, mixed>
     */
    private function resolveSharedDbTableProjection(
        TransformRecord $record,
        TransformDefinition $definition,
        array $references,
    ): array {
        $iterableIndex = $this->iterableReferenceIndex($references);
        $hasAdditionalReferences = false;

        foreach ($references as $index => $reference) {
            if (! is_array($reference)) {
                continue;
            }

            if ($index === $iterableIndex) {
                continue;
            }

            $hasAdditionalReferences = true;
            break;
        }

        if (! $hasAdditionalReferences) {
            return $this->arrayAttribute($record, 'source_projection');
        }

        $resolution = $this->sourcePayloadResolver->resolve($record, $definition);

        return $resolution['payload'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveImportRecordProjections(
        TransformRecord $record,
        TransformDefinition $definition,
        array $reference,
    ): array {
        $projection = $this->arrayAttribute($record, 'source_projection');
        $recordId = $this->templateValueResolver->resolve($reference['record_id'] ?? null, $projection);
        if (! is_numeric($recordId)) {
            return [];
        }

        $payload = $this->importRecordPayloadReader->read((int) $recordId);
        $items = $this->normalizeListPayload($payload, $reference);
        $alias = is_string($reference['alias'] ?? null) && $reference['alias'] !== ''
            ? $reference['alias']
            : 'item';

        $projections = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $merged = $projection;
            $merged[$alias] = $item;
            $projections[] = array_replace_recursive($merged, $item);
        }

        return $projections;
    }

    /**
     * @param  array<mixed>  $payload
     * @param  array<string, mixed>  $reference
     * @return list<array<string, mixed>>
     */
    private function normalizeListPayload(array $payload, array $reference): array
    {
        $selector = $reference['selector'] ?? null;
        if (is_string($selector) && $selector !== '') {
            $selected = Arr::get($payload, $selector);

            return is_array($selected) ? $this->normalizeList($selected) : [];
        }

        return $this->normalizeList($payload);
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function normalizeList(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        if (array_is_list($payload)) {
            /** @var list<array<string, mixed>> $payload */
            return array_values(array_filter($payload, is_array(...)));
        }

        return [$payload];
    }

    /**
     * @param  list<array<string, mixed>>  $projections
     * @param  array<string, mixed>  $expand
     * @return list<array<string, mixed>>
     */
    private function dedupeProjections(array $projections, array $expand): array
    {
        $dedupeBy = $expand['dedupe_by'] ?? null;
        if (! is_string($dedupeBy) || $dedupeBy === '') {
            return $projections;
        }

        $prefer = is_array($expand['prefer'] ?? null) ? $expand['prefer'] : [];
        $selected = [];

        foreach ($projections as $projection) {
            $key = (string) Arr::get($projection, $dedupeBy);
            if ($key === '') {
                continue;
            }

            if (! array_key_exists($key, $selected)) {
                $selected[$key] = $projection;

                continue;
            }

            if ($this->shouldPreferProjection($projection, $selected[$key], $prefer)) {
                $selected[$key] = $projection;
            }
        }

        return array_values($selected);
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @param  array<string, mixed>  $current
     * @param  list<array<string, mixed>>  $preferRules
     */
    private function shouldPreferProjection(array $candidate, array $current, array $preferRules): bool
    {
        if ($preferRules === []) {
            return false;
        }

        $candidateScore = $this->preferenceScore($candidate, $preferRules);
        $currentScore = $this->preferenceScore($current, $preferRules);

        return $candidateScore > $currentScore;
    }

    /**
     * @param  array<string, mixed>  $projection
     * @param  list<array<string, mixed>>  $preferRules
     */
    private function preferenceScore(array $projection, array $preferRules): int
    {
        $score = 0;

        foreach ($preferRules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $path = $rule['path'] ?? null;
            if (! is_string($path) || $path === '') {
                continue;
            }

            $value = Arr::get($projection, $path);
            $equals = $rule['equals'] ?? true;
            if ($value == $equals) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @param  list<array<string, mixed>>  $projections
     * @param  array<string, mixed>  $nested
     * @return list<array<string, mixed>>
     */
    private function expandNested(array $projections, array $nested): array
    {
        $path = $nested['path'] ?? null;
        if (! is_string($path) || $path === '') {
            return $projections;
        }

        $alias = is_string($nested['alias'] ?? null) && $nested['alias'] !== ''
            ? $nested['alias']
            : 'nested';
        $dedupeBy = $nested['dedupe_by'] ?? null;

        $expanded = [];
        $seen = [];

        foreach ($projections as $projection) {
            $nestedItems = Arr::get($projection, $path);
            if (! is_array($nestedItems)) {
                continue;
            }

            foreach ($nestedItems as $nestedItem) {
                if (! is_array($nestedItem)) {
                    continue;
                }

                if (is_string($dedupeBy) && $dedupeBy !== '') {
                    $dedupeKey = (string) Arr::get($nestedItem, $dedupeBy);
                    if ($dedupeKey === '' || isset($seen[$dedupeKey])) {
                        continue;
                    }

                    $seen[$dedupeKey] = true;
                }

                $expanded[] = array_replace_recursive($projection, [
                    $alias => $nestedItem,
                ]);
            }
        }

        return $expanded === [] ? $projections : $expanded;
    }

    /**
     * @param  list<array<string, mixed>>  $projections
     * @param  array<string, mixed>  $locales
     * @return list<array<string, mixed>>
     */
    private function expandLocales(array $projections, array $locales): array
    {
        $source = $locales['source'] ?? null;
        if (! is_string($source) || $source === '') {
            return $projections;
        }

        $languageKey = is_string($locales['language_key'] ?? null) && $locales['language_key'] !== ''
            ? $locales['language_key']
            : 'language';
        $alias = is_string($locales['alias'] ?? null) && $locales['alias'] !== ''
            ? $locales['alias']
            : 'lang';
        $localeField = is_string($locales['locale_field'] ?? null) && $locales['locale_field'] !== ''
            ? $locales['locale_field']
            : 'locale';
        $only = $this->normalizeLanguageFilter($locales['only'] ?? null);

        $expanded = [];

        foreach ($projections as $projection) {
            $localeItems = Arr::get($projection, $source);
            if (! is_array($localeItems) || $localeItems === []) {
                $expanded[] = $projection;

                continue;
            }

            foreach ($localeItems as $localeItem) {
                if (! is_array($localeItem)) {
                    continue;
                }

                $language = $localeItem[$languageKey] ?? null;
                if ($only !== [] && ! $this->languageMatchesFilter($language, $only)) {
                    continue;
                }

                $expanded[] = array_replace_recursive($projection, [
                    $alias => $localeItem,
                    $localeField => $this->localeVariantResolver->resolve($language),
                ]);
            }
        }

        return $expanded === [] ? $projections : $expanded;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveIterableSourceReference(TransformRecord $record, TransformDefinition $definition): ?array
    {
        $definitionReferences = $this->arrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->arrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! in_array($sourceType, ['db_table', 'api_import_record'], true)) {
                continue;
            }

            $rowKey = $reference['row_key'] ?? null;
            $rowKeyFrom = $reference['row_key_from'] ?? null;

            if (DbTableSourceQuery::hasRowKey($rowKey) || DbTableSourceQuery::hasRowKeyFrom($rowKeyFrom)) {
                continue;
            }

            return $reference;
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $references
     */
    private function iterableReferenceIndex(array $references): ?int
    {
        foreach ($references as $index => $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! in_array($sourceType, ['db_table', 'api_import_record'], true)) {
                continue;
            }

            $rowKey = $reference['row_key'] ?? null;
            $rowKeyFrom = $reference['row_key_from'] ?? null;
            if (! DbTableSourceQuery::hasRowKey($rowKey) && ! DbTableSourceQuery::hasRowKeyFrom($rowKeyFrom)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function expandConfig(TransformDefinition $definition): array
    {
        $expand = $definition->getAttribute('expand');

        return is_array($expand) ? $expand : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayAttribute(TransformDefinition|TransformRecord $model, string $attribute): array
    {
        $value = $model->getAttribute($attribute);

        return is_array($value) ? $value : [];
    }

    /**
     * @return list<string>
     */
    private function normalizeLanguageFilter(mixed $only): array
    {
        if (! is_array($only)) {
            return [];
        }

        $normalized = [];

        foreach ($only as $language) {
            if (! is_string($language) && ! is_int($language)) {
                continue;
            }

            $language = $this->normalizeLanguageKey((string) $language);
            if ($language === '') {
                continue;
            }

            $normalized[] = $language;
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeLanguageKey(string $languageKey): string
    {
        return strtolower(trim($languageKey));
    }

    /**
     * @param  list<string>  $only
     */
    private function languageMatchesFilter(mixed $language, array $only): bool
    {
        if (! is_string($language) && ! is_int($language)) {
            return false;
        }

        return in_array($this->normalizeLanguageKey((string) $language), $only, true);
    }

    private function shouldUseCursorStrategy(TransformRecord $record, TransformDefinition $definition): bool
    {
        $bulk = $definition->getAttribute('bulk');
        $strategy = is_array($bulk) && is_array($bulk['source'] ?? null)
            ? (string) ($bulk['source']['strategy'] ?? config('transform.bulk.source.strategy', 'eager'))
            : (string) config('transform.bulk.source.strategy', 'eager');

        if ($strategy !== 'cursor') {
            return false;
        }

        if ($this->expandConfig($definition) !== []) {
            return false;
        }

        $iterableReference = $this->resolveIterableSourceReference($record, $definition);

        return is_array($iterableReference) && ($iterableReference['source_type'] ?? null) === 'db_table';
    }
}
