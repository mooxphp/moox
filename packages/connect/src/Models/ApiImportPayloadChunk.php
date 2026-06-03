<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Item\BaseItemModel;

class ApiImportPayloadChunk extends BaseItemModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'api_import_payload_chunks';

    protected $fillable = [
        'api_import_record_id',
        'chunk_index',
        'payload_chunk',
        'items_count',
        'bytes_size',
    ];

    public function importRecord(): BelongsTo
    {
        return $this->belongsTo(ApiImportRecord::class, 'api_import_record_id');
    }
}
