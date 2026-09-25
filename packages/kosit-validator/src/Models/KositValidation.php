<?php

declare(strict_types=1);

namespace Moox\KositValidator\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\KositValidator\Support\KositValidationMessages;

/**
 * @property string|null $input_path
 * @property string|null $report_xml_path
 * @property string|null $report_html_path
 * @property bool $passed
 * @property array<int|string, mixed>|null $errors
 * @property Carbon|null $validated_at
 * @property-read string $filename
 * @property-read string $result
 * @property-read int $errors_count
 */
class KositValidation extends BaseItemModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'input_path',
        'report_xml_path',
        'report_html_path',
        'passed',
        'errors',
        'validated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'passed' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public static function getResourceName(): string
    {
        return 'kosit-validator';
    }

    /**
     * @return HasMany<KositValidatable, $this>
     */
    public function kositValidatables(): HasMany
    {
        return $this->hasMany(KositValidatable::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePassed(Builder $query): Builder
    {
        return $query->where('passed', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('passed', false);
    }

    public function filenameLabel(): string
    {
        return $this->input_path !== null
            ? basename($this->input_path)
            : __('kosit-validator::fields.filename_empty');
    }

    protected function filename(): Attribute
    {
        return Attribute::get(fn (): string => $this->filenameLabel());
    }

    protected function result(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->passed
                ? __('kosit-validator::fields.result_passed')
                : __('kosit-validator::fields.result_failed'),
        );
    }

    protected function errorsCount(): Attribute
    {
        return Attribute::get(
            fn (): int => KositValidationMessages::counts($this->errors)['error'],
        );
    }

    public function reportHtmlPath(): ?string
    {
        return $this->report_html_path;
    }
}
