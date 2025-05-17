<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingRoleModelStub extends EloquentRoleModelStub
{
    use SoftDeletes;
}
