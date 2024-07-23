<?php

namespace Moox\Security\Models;

use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    protected $primaryKey = 'email';

    public $incrementing = false;

    protected $table = 'password_reset_tokens';

    protected $fillable = ['email', 'token'];
}
