<?php

declare(strict_types=1);

namespace Moox\EBilling\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Support\StoredRelativePath;

class UploadedPdfSource extends Model
{
    use HasUuids;

    protected $table = 'ebilling_uploaded_pdf_sources';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_pdf_disk',
        'source_pdf_path',
        'original_filename',
        'scope',
        'requires_letterhead_overlay',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_letterhead_overlay' => 'boolean',
        ];
    }

    public function sourceFullPath(): string
    {
        $path = StoredRelativePath::assertSafe($this->source_pdf_path);

        return Storage::disk($this->source_pdf_disk)->path($path);
    }
}
