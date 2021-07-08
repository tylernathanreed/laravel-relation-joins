<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingRoleUserPivotStub extends EloquentRoleUserPivotStub
{
    use SoftDeletes;
}
