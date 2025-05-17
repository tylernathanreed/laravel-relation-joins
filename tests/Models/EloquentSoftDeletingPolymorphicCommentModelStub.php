<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingPolymorphicCommentModelStub extends EloquentPolymorphicCommentModelStub
{
    use SoftDeletes;
}
