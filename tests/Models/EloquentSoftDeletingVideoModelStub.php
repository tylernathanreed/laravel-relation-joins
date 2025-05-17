<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingVideoModelStub extends EloquentVideoModelStub
{
    use SoftDeletes;
}
