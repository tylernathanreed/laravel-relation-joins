<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingDepartmentModelStub extends EloquentDepartmentModelStub
{
    use SoftDeletes;
}
