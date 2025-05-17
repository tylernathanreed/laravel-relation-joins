<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSoftDeletingSupplierModelStub extends EloquentSupplierModelStub
{
    use SoftDeletes;
}
