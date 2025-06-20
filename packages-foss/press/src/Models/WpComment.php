<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_post_ID',
        'comment_author',
        'comment_author_email',
        'comment_author_url',
        'comment_author_IP',
        'comment_date',
        'comment_date_gmt',
        'comment_content',
        'comment_karma',
        'comment_approved',
        'comment_agent',
        'comment_type',
        'comment_parent',
        'user_id',
    ];

    public function commentMeta()
    {
        return $this->hasMany(WpCommentMeta::class, 'comment_id');
    }

    protected $searchableFields = ['*'];

    protected $casts = [
        'comment_date' => 'datetime',
        'comment_date_gmt' => 'datetime',
    ];

    protected $wpPrefix;

    protected $table;

    protected $primaryKey = 'comment_ID';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'comments';
    }
}
