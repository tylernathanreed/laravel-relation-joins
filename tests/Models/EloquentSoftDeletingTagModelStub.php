<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingTagModelStub extends EloquentTagModelStub
{
    use SoftDeletes;
}
