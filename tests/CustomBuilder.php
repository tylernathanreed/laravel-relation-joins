<?php

namespace Reedware\LaravelRelationJoins\Tests;

use Illuminate\Database\Eloquent\Builder;

class CustomBuilder extends Builder
{
    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = [
        'insert', 'insertGetId', 'getBindings', 'toSql',
        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'sum', 'getConnection',
        'myCustomOverrideforTesting'
    ];
}
