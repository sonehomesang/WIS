<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

// WH role model — adds scope_rules JSON (transactionScope/inventoryScope/catalogScope).
// Spatie has no native scope support; see RBAC_MATRIX.md.
class Role extends SpatieRole
{
    protected $casts = [
        'scope_rules' => 'array',
    ];

    protected $fillable = [
        'name',
        'guard_name',
        'scope_rules',
    ];
}
