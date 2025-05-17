<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingCommentModelStub extends EloquentCommentModelStub
{
    use SoftDeletes;
}
