<?php

namespace Moox\Devops\Models;

use Illuminate\Database\Eloquent\Model;

class GithubRepository extends Model
{
    protected $table = 'github_repositories';

    protected $fillable = [
        'name',
        'repository_url',
        'platform',
        'platform_uid',
        'last_commit',
        'deploys_to_project_id',
    ];

    protected $casts = [
        'last_commit' => 'datetime',
    ];

    public function server()
    {
        return $this->hasMany(GithubCommit::class, 'repository_id', 'id');
    }
}
