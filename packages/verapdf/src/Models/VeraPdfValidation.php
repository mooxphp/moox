<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Moox\Core\Entities\Items\Item\BaseItemModel;

/**
 * @property string|null $input_path
 * @property string|null $report_xml_path
 * @property string|null $report_html_path
 * @property bool $passed
 * @property array<int|string, mixed>|null $errors
 * @property Carbon|null $validated_at
 * @property-read string $filename
 * @property-read string $result
 */
class VeraPdfValidation extends BaseItemModel
{
    protected $table = 'verapdf_validations';

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
        // Must match config/verapdf.php (HasResourceRelations → RelationService key).
        return 'verapdf';
    }

    /**
     * @return HasMany<VeraPdfValidatable, $this>
     */
    public function veraPdfValidatables(): HasMany
    {
        // Column is verapdf_validation_id (package naming); Eloquent would guess vera_pdf_validation_id.
        return $this->hasMany(VeraPdfValidatable::class, 'verapdf_validation_id');
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
            : __('verapdf::fields.filename_empty');
    }

    protected function filename(): Attribute
    {
        return Attribute::get(fn (): string => $this->filenameLabel());
    }

    protected function result(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->passed
                ? __('verapdf::fields.result_passed')
                : __('verapdf::fields.result_failed'),
        );
    }

    public function reportHtmlPath(): ?string
    {
        return $this->report_html_path;
    }
}
