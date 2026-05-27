<?php

declare(strict_types=1);

namespace Moox\KositValidator\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string|null $input_path
 * @property string|null $report_xml_path
 * @property string|null $report_html_path
 * @property bool $passed
 * @property array<int|string, mixed>|null $errors
 * @property Carbon|null $validated_at
 */
class KositValidation extends Model
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
            : '—';
    }

    public function reportHtmlPath(): ?string
    {
        return $this->report_html_path;
    }
}
