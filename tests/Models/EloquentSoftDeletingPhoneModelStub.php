<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingPhoneModelStub extends EloquentPhoneModelStub
{
    use SoftDeletes;
}
