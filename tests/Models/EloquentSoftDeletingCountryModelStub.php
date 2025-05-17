<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingCountryModelStub extends EloquentCountryModelStub
{
    use SoftDeletes;
}
