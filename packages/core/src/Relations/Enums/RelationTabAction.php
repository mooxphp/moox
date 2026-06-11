<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Enums;

enum RelationTabAction: string
{
    case Associate = 'associate';
    case Attach = 'attach';
    case Create = 'create';
    case View = 'view';
    case Edit = 'edit';
    case EditRelated = 'edit_related';
    case EditPivot = 'edit_pivot';
    case Dissociate = 'dissociate';
    case Detach = 'detach';
    case DetachBulk = 'detach_bulk';
    case Delete = 'delete';
}
