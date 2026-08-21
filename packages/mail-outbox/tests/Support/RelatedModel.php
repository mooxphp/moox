<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class RelatedModel extends Model
{
    protected $table = 'mail_outbox_related_models';

    protected $guarded = [];
}
