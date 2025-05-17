<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingUserModelStub extends EloquentUserModelStub
{
    use SoftDeletes;
}
