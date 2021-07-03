<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingUserRolePivotStub extends EloquentRelationJoinPivotStub
{
    use SoftDeletes;

    protected $table = 'role_user';
}