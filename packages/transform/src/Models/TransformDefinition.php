<?php

declare(strict_types=1);

namespace Moox\Transform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Moox\Core\Entities\Items\Item\BaseItemModel;

class TransformDefinition extends BaseItemModel
{
    use SoftDeletes;

    protected $table = 'transform_definitions';

    protected $fillable = [
        'name',
        'destination_model',
        'destination_match',
        'source_references',
        'field_map',
        'validation_rules',
        'execution_mode',
        'expand',
        'bulk',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'source_references' => 'array',
            'destination_match' => 'array',
            'field_map' => 'array',
            'validation_rules' => 'array',
            'expand' => 'array',
            'bulk' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $definition): void {
            $errors = [];
            $fieldMap = $definition->getAttribute('field_map');
            $destinationMatch = $definition->getAttribute('destination_match');
            $sourceReferences = $definition->getAttribute('source_references');

            $destinationModel = (string) $definition->destination_model;
            if ($destinationModel === '' || ! class_exists($destinationModel) || ! is_subclass_of($destinationModel, Model::class)) {
                $errors['destination_model'][] = 'Destination model must be an existing Eloquent model class.';
            }

            if (! is_array($fieldMap) || $fieldMap === []) {
                $errors['field_map'][] = 'Field map must be a non-empty array.';
            }

            if ($destinationMatch !== null && ! is_array($destinationMatch)) {
                $errors['destination_match'][] = 'Destination match must be an array.';
            }

            if (! is_array($sourceReferences)) {
                $errors['source_references'][] = 'Source references must be an array.';
            } else {
                $errors = array_merge_recursive($errors, self::validateSourceReferences($sourceReferences));
            }

            if (is_array($destinationMatch) && $destinationMatch !== []) {
                $errors = array_merge_recursive(
                    $errors,
                    self::validateDestinationMatch($destinationMatch, is_array($fieldMap) ? $fieldMap : [])
                );
            } elseif ($definition->is_active) {
                $errors['destination_match'][] = __('transform::validation.destination_match_required');
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        });
    }

    /**
     * @param  array<int, mixed>  $references
     * @return array<string, array<int, string>>
     */
    public static function validateSourceReferences(array $references): array
    {
        $errors = [];

        foreach ($references as $index => $reference) {
            if (! is_array($reference)) {
                $errors["source_references.{$index}"][] = 'Each source reference must be an object/array.';

                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! is_string($sourceType) || $sourceType === '') {
                $errors["source_references.{$index}.source_type"][] = 'source_type is required.';

                continue;
            }

            if ($sourceType === 'db_table') {
                $table = $reference['table'] ?? null;
                $keyColumn = $reference['key_column'] ?? null;
                $connection = self::resolveConnectionName($reference['connection'] ?? null);
                $schemaBuilder = null;
                $resolvedConnection = null;

                if (is_string($connection) && $connection !== '') {
                    $resolvedConnection = $connection;
                    $knownConnections = array_keys((array) Config::get('database.connections', []));
                    if (! in_array($connection, $knownConnections, true)) {
                        $errors["source_references.{$index}.connection"][] = "Database connection [{$connection}] is not configured.";
                    } elseif ($schemaBuilder === null) {
                        $schemaBuilder = Schema::connection((string) $resolvedConnection);
                    }
                } else {
                    $resolvedConnection = (string) DB::getDefaultConnection();
                    $schemaBuilder = Schema::connection($resolvedConnection);
                }

                if (! is_string($table) || $table === '') {
                    $errors["source_references.{$index}.table"][] = 'table is required for db_table source.';
                } elseif (str_contains($table, '.')) {
                    $errors["source_references.{$index}.table"][] = 'Schema-qualified table names are not allowed. Use table name only.';
                } elseif ($schemaBuilder !== null && ! $schemaBuilder->hasTable($table)) {
                    $errors["source_references.{$index}.table"][] = "Referenced table [{$table}] does not exist.";
                }

                if (! is_string($keyColumn) || $keyColumn === '') {
                    $errors["source_references.{$index}.key_column"][] = 'key_column is required for db_table source.';
                } elseif (
                    is_string($table)
                    && $table !== ''
                    && ! str_contains($table, '.')
                    && $schemaBuilder !== null
                    && $schemaBuilder->hasTable($table)
                    && ! $schemaBuilder->hasColumn($table, $keyColumn)
                ) {
                    $errors["source_references.{$index}.key_column"][] = "Referenced key_column [{$keyColumn}] does not exist on table [{$table}].";
                }

                $columns = $reference['columns'] ?? null;
                if (is_array($columns) && is_string($table) && $table !== '' && ! str_contains($table, '.') && $schemaBuilder !== null && $schemaBuilder->hasTable($table)) {
                    $normalizedColumns = [];
                    foreach ($columns as $column) {
                        if (! is_string($column) || $column === '' || ! $schemaBuilder->hasColumn($table, $column)) {
                            $errors["source_references.{$index}.columns"][] = "Referenced column [{$column}] does not exist on table [{$table}].";
                        } else {
                            $normalizedColumns[] = $column;
                        }
                    }

                    if (count($normalizedColumns) !== count(array_unique($normalizedColumns))) {
                        $errors["source_references.{$index}.columns"][] = 'columns must not contain duplicates.';
                    }
                }
            }

            if (in_array($sourceType, ['file_json', 'file_csv'], true)) {
                $path = $reference['path'] ?? null;
                if (! is_string($path) || $path === '') {
                    $errors["source_references.{$index}.path"][] = 'path is required for file source.';
                } elseif (! File::exists($path)) {
                    $errors["source_references.{$index}.path"][] = 'Referenced file does not exist.';
                } elseif (! is_readable($path)) {
                    $errors["source_references.{$index}.path"][] = 'Referenced file is not readable.';
                } elseif ($sourceType === 'file_json') {
                    $decoded = json_decode((string) File::get($path), true);
                    if (! is_array($decoded)) {
                        $errors["source_references.{$index}.path"][] = 'JSON file must contain a valid JSON object/array.';
                    }
                } elseif ($sourceType === 'file_csv') {
                    $rows = @file($path);
                    if (! is_array($rows) || $rows === []) {
                        $errors["source_references.{$index}.path"][] = 'CSV file is empty.';
                    } else {
                        $headerLine = (string) $rows[0];
                        if (trim($headerLine) === '') {
                            $errors["source_references.{$index}.path"][] = 'CSV file must contain a header row.';
                        } else {
                            $header = str_getcsv($headerLine, ',', '"', '\\');
                            $keyColumn = $reference['key_column'] ?? null;
                            if (is_string($keyColumn) && $keyColumn !== '' && ! in_array($keyColumn, $header, true)) {
                                $errors["source_references.{$index}.key_column"][] = "CSV key_column [{$keyColumn}] not found in header.";
                            }
                        }
                    }
                }
            }

            if ($sourceType === 'api') {
                $url = $reference['url'] ?? null;
                if (! is_string($url) || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
                    $errors["source_references.{$index}.url"][] = 'A valid url is required for api source.';
                }
            }

            if ($sourceType === 'api_import_record') {
                $recordId = $reference['record_id'] ?? null;
                if (! is_string($recordId) || trim($recordId) === '') {
                    $errors["source_references.{$index}.record_id"][] = 'record_id is required for api_import_record source.';
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $destinationMatch
     * @param  array<string, mixed>  $fieldMap
     * @return array<string, array<int, string>>
     */
    public static function validateDestinationMatch(array $destinationMatch, array $fieldMap = []): array
    {
        $errors = [];

        foreach ($destinationMatch as $destinationField => $sourcePath) {
            if (! is_string($destinationField) || $destinationField === '') {
                $errors['destination_match'][] = 'Destination match contains invalid destination field keys.';

                continue;
            }

            if (! is_string($sourcePath) || $sourcePath === '') {
                $errors["destination_match.{$destinationField}"][] = 'Destination match source path must be a non-empty string.';
            }
        }

        if ($destinationMatch !== [] && $fieldMap !== []) {
            foreach (array_keys($destinationMatch) as $destinationField) {
                if (! array_key_exists((string) $destinationField, $fieldMap)) {
                    $errors["destination_match.{$destinationField}"][] = 'Destination match fields should also exist in field_map.';
                }
            }
        }

        return $errors;
    }

    /**
     * @return HasMany<TransformRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(TransformRecord::class, 'transform_definition_id');
    }

    /**
     * @return array<string, string>
     */
    public static function discoverConnectionOptions(): array
    {
        $connections = array_keys((array) Config::get('database.connections', []));

        return array_combine($connections, $connections) ?: [];
    }

    /**
     * @return array<string, string>
     */
    public static function discoverTableOptions(string $connection): array
    {
        $resolvedConnection = self::resolveConnectionName($connection);
        if ($resolvedConnection === null || $resolvedConnection === '') {
            return [];
        }

        try {
            $builder = Schema::connection($resolvedConnection);
            $tables = [];

            if (method_exists($builder, 'getTableListing')) {
                /** @var array<int, string> $listing */
                $listing = $builder->getTableListing();
                $tables = $listing;
            } elseif (method_exists($builder, 'getTables')) {
                /** @var array<int, mixed> $rawTables */
                $rawTables = $builder->getTables();
                foreach ($rawTables as $rawTable) {
                    if (is_array($rawTable) && isset($rawTable['name']) && is_string($rawTable['name'])) {
                        $tables[] = $rawTable['name'];
                    }
                }
            }

            $activeDatabase = DB::connection($resolvedConnection)->getDatabaseName();
            $driver = (string) DB::connection($resolvedConnection)->getDriverName();
            $normalizedTables = [];
            foreach ($tables as $table) {
                if (! is_string($table) || $table === '') {
                    continue;
                }

                if (str_contains($table, '.')) {
                    $segments = explode('.', $table);
                    $tableName = (string) end($segments);

                    if ($driver === 'sqlsrv' && $tableName !== '') {
                        $normalizedTables[] = $tableName;

                        continue;
                    }

                    [$schema, $qualifiedTableName] = explode('.', $table, 2);
                    $isCurrentDatabase = $activeDatabase !== null && $schema === $activeDatabase;
                    $isSqliteMain = $driver === 'sqlite' && $schema === 'main';

                    if (($isCurrentDatabase || $isSqliteMain) && $qualifiedTableName !== '') {
                        $normalizedTables[] = $qualifiedTableName;
                    }

                    continue;
                }

                $normalizedTables[] = $table;
            }

            $normalizedTables = array_values(array_unique($normalizedTables));
            sort($normalizedTables);

            return array_combine($normalizedTables, $normalizedTables) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    public static function discoverColumnOptions(string $connection, string $table): array
    {
        $resolvedConnection = self::resolveConnectionName($connection);
        if ($resolvedConnection === null || $resolvedConnection === '' || $table === '' || str_contains($table, '.')) {
            return [];
        }

        try {
            if (! Schema::connection($resolvedConnection)->hasTable($table)) {
                return [];
            }

            $columns = Schema::connection($resolvedConnection)->getColumnListing($table);
            sort($columns);

            return array_combine($columns, $columns) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    public static function discoverDestinationFieldOptions(string $destinationModel): array
    {
        if ($destinationModel === '' || ! class_exists($destinationModel) || ! is_subclass_of($destinationModel, Model::class)) {
            return [];
        }

        /** @var Model $model */
        $model = new $destinationModel;
        $fillable = $model->getFillable();
        $translated = [];

        if (method_exists($model, 'getTranslatedAttributes')) {
            $attributes = $model->getTranslatedAttributes();
            if (is_array($attributes)) {
                $translated = array_values(array_filter($attributes, fn (mixed $attribute): bool => is_string($attribute) && $attribute !== ''));
            }
        } elseif (property_exists($model, 'translatedAttributes') && is_array($model->translatedAttributes ?? null)) {
            $translated = array_values(array_filter($model->translatedAttributes, fn (mixed $attribute): bool => is_string($attribute) && $attribute !== ''));
        }

        $fields = array_values(array_unique(array_merge($fillable, $translated)));
        sort($fields);

        return array_combine($fields, $fields) ?: [];
    }

    /**
     * @param  array<int, mixed>  $sourceReferences
     * @return array<string, string>
     */
    public static function discoverSourcePathOptions(array $sourceReferences): array
    {
        $paths = [];

        foreach ($sourceReferences as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $aliasRaw = $reference['alias'] ?? null;
            $alias = is_string($aliasRaw) && $aliasRaw !== '' ? $aliasRaw : null;
            $sourceType = $reference['source_type'] ?? null;
            if (! is_string($sourceType) || $sourceType === '') {
                continue;
            }

            if ($sourceType === 'db_table') {
                $table = $reference['table'] ?? null;
                $connection = self::resolveConnectionName($reference['connection'] ?? null);
                $columns = [];

                if (is_array($reference['columns']) && $reference['columns'] !== []) {
                    foreach ($reference['columns'] as $column) {
                        if (is_string($column) && $column !== '') {
                            $columns[] = $column;
                        }
                    }
                } elseif (is_string($table) && $table !== '' && ! str_contains($table, '.') && is_string($connection) && $connection !== '') {
                    try {
                        if (Schema::connection($connection)->hasTable($table)) {
                            $columns = Schema::connection($connection)->getColumnListing($table);
                        }
                    } catch (\Throwable) {
                        $columns = [];
                    }
                }

                foreach ($columns as $column) {
                    if (! is_string($column) || $column === '') {
                        continue;
                    }

                    $path = self::prefixPath($alias, $column);
                    $paths[$path] = $path;
                }
            }

            if ($sourceType === 'file_json') {
                $filePath = $reference['path'] ?? null;
                if (! is_string($filePath) || $filePath === '' || ! File::exists($filePath)) {
                    continue;
                }

                $decoded = json_decode((string) File::get($filePath), true);
                if (! is_array($decoded)) {
                    continue;
                }

                foreach (self::flattenArrayPaths($decoded) as $path) {
                    $prefixedPath = self::prefixPath($alias, $path);
                    $paths[$prefixedPath] = $prefixedPath;
                }
            }

            if ($sourceType === 'file_csv') {
                $filePath = $reference['path'] ?? null;
                if (! is_string($filePath) || $filePath === '' || ! File::exists($filePath)) {
                    continue;
                }

                $rows = @file($filePath);
                if (! is_array($rows) || $rows === []) {
                    continue;
                }

                $header = str_getcsv((string) $rows[0], ',', '"', '\\');
                foreach ($header as $column) {
                    if (! is_string($column) || $column === '') {
                        continue;
                    }

                    $path = self::prefixPath($alias, $column);
                    $paths[$path] = $path;
                }
            }

            if ($sourceType === 'static') {
                $data = $reference['data'] ?? null;
                if (! is_array($data)) {
                    continue;
                }

                foreach (self::flattenArrayPaths($data) as $path) {
                    $prefixedPath = self::prefixPath($alias, $path);
                    $paths[$prefixedPath] = $prefixedPath;
                }
            }

            if ($sourceType === 'api_import_record' && $alias !== null) {
                $paths[$alias] = $alias;
            }
        }

        ksort($paths);

        return $paths;
    }

    public static function defaultConnectionName(): string
    {
        $default = (string) Config::get('database.default', '');

        if ($default !== '') {
            return $default;
        }

        return (string) DB::getDefaultConnection();
    }

    public static function resolveConnectionName(mixed $connection): ?string
    {
        if (! is_string($connection) || $connection === '' || $connection === 'db_default') {
            return self::defaultConnectionName();
        }

        return $connection;
    }

    private static function prefixPath(?string $alias, string $path): string
    {
        if ($alias === null || $alias === '') {
            return $path;
        }

        return "{$alias}.{$path}";
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private static function flattenArrayPaths(array $data, string $prefix = ''): array
    {
        $paths = [];

        foreach ($data as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $path = $prefix === '' ? $key : "{$prefix}.{$key}";
            $paths[] = $path;

            if (is_array($value)) {
                $paths = array_merge($paths, self::flattenArrayPaths($value, $path));
            }
        }

        return array_values(array_unique($paths));
    }
}
