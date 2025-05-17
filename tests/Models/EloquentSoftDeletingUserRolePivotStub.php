<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingUserRolePivotStub extends EloquentRoleUserPivotStub
{
    use SoftDeletes;
}
