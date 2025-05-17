<?php

namespace Tests\Models;

class EloquentRoleUserPivotStub extends EloquentRelationJoinPivotStub
{
    protected $table = 'role_user';

    public function scopeWeb($query)
    {
        $query->where('domain', '=', 'web');
    }
}
