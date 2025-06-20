<?php

namespace Moox\Devops\Models;

use Illuminate\Database\Eloquent\Model;

class GithubCommit extends Model
{
    protected $table = 'github_commits';

    protected $fillable = [
        'commit_hash',
        'commit_message',
        'commit_author',
        'commit_url',
        'commit_timestamp',
        'repository_id',
        'deployed_to_project_id',
    ];

    protected $casts = [
        'commit_timestamp' => 'datetime',
    ];

    public function server()
    {
        return $this->belongsTo(GithubRepository::class, 'repository_id', 'id');
    }
}
