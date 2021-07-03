<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentRoleModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'roles';

    public function users()
    {
        return $this->belongsToMany(EloquentUserModelStub::class, 'role_user', 'role_id', 'user_id');
    }
}
