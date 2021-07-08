<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentDepartmentModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'departments';

    public function supervisor()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'supervisor_id');
    }

    public function employees()
    {
        return $this->hasMany(EloquentUserModelStub::class, 'department_id');
    }
}
