<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingTaggableModelStub extends EloquentTaggableModelStub
{
    use SoftDeletes;
}
