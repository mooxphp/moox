<?php

namespace Moox\Press\QueryBuilder;

use Illuminate\Database\Query\Builder;

class UserQueryBuilder extends Builder
{
    protected $aliasMap = [
        'email' => 'user_email',
        'name' => 'user_login',
        'password' => 'user_pass',
        'id' => 'ID',
    ];

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_string($column) && array_key_exists($column, $this->aliasMap)) {
            $column = $this->aliasMap[$column];
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    public function orderBy($column, $direction = 'asc')
    {
        if (is_string($column) && array_key_exists($column, $this->aliasMap)) {
            $column = $this->aliasMap[$column];
        }

        return parent::orderBy($column, $direction);
    }
}
