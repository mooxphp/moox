<?php

namespace Moox\Press\Traits;

trait UserMetaAttributes
{
    public function getEmailAttribute()
    {
        return $this->attributes['user_email'] ?? null;
    }

    public function setEmailAttribute($value)
    {
        $this->addOrUpdateMeta('user_email', $value);
    }

    public function getNameAttribute()
    {
        return $this->attributes['user_login'] ?? null;
    }

    public function setNameAttribute($value)
    {
        $this->addOrUpdateMeta('user_login', $value);
    }

    public function getPasswordAttribute()
    {
        return $this->attributes['user_pass'] ?? null;
    }

    public function setPasswordAttribute($value)
    {
        $this->addOrUpdateMeta('user_pass', $value);
    }

    public function getDisplayNameAttribute()
    {
        return $this->attributes['display_name'] ?? null;
    }

    public function setDisplayNameAttribute($value)
    {
        $this->addOrUpdateMeta('display_name', $value);
    }

    public function getNicknameAttribute()
    {
        return $this->getMeta('nickname') ?? null;
    }

    public function setNicknameAttribute($value)
    {
        $this->addOrUpdateMeta('nickname', $value);
    }

    public function getFirstNameAttribute()
    {
        return $this->getMeta('first_name') ?? null;
    }

    public function setFirstNameAttribute($value)
    {
        $this->addOrUpdateMeta('first_name', $value);
    }

    public function getLastNameAttribute()
    {
        return $this->getMeta('last_name') ?? null;
    }

    public function setLastNameAttribute($value)
    {
        $this->addOrUpdateMeta('last_name', $value);
    }

    public function getDescriptionAttribute()
    {
        return $this->getMeta('description') ?? null;
    }

    public function setDescriptionAttribute($value)
    {
        $this->addOrUpdateMeta('description', $value);
    }

    public function getSessionTokensAttribute()
    {
        return $this->getMeta('session_tokens') ?? null;
    }

    public function setSessionTokenAttribute($value)
    {
        $this->addOrUpdateMeta('session_tokens', $value);
    }

    public function getRememberTokenAttribute()
    {
        return $this->getMeta('remember_token') ?? null;
    }

    public function setRememberTokenAttribute($value)
    {
        $this->addOrUpdateMeta('remember_token', $value);
    }

    public function getEmailVerifiedAtAttribute()
    {
        return $this->getMeta('email_verified_at') ?? null;
    }

    public function setEmailVerifiedAtAttribute($value)
    {
        $this->addOrUpdateMeta('email_verified_at', $value);
    }

    public function getCreatedAtAttribute()
    {
        return $this->getMeta('created_at') ?? null;
    }

    public function setCreatedAtAttribute($value)
    {
        $this->addOrUpdateMeta('created_at', $value);
    }

    public function getUpdatedAtAttribute()
    {
        return $this->getMeta('updated_at') ?? null;
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->addOrUpdateMeta('updated_at', $value);
    }

    public function getMmSuaAttachmentIdAttribute()
    {
        return $this->getMeta('mm_sua_attachment_id') ?? null;
    }

    public function setMmSuaAttachmentIdAttribute($value)
    {
        $this->addOrUpdateMeta('mm_sua_attachment_id', $value);
    }

    public function getMooxUserAttachmentIdAttribute()
    {
        return $this->getMeta('moox_user_attachment_id') ?? null;
    }

    public function setMooxUserAttachmentIdAttribute($value)
    {
        $this->addOrUpdateMeta('moox_user_attachment_id', $value);
    }

    protected function getMeta($key)
    {
        $meta = $this->userMeta()->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : null;
    }
}
