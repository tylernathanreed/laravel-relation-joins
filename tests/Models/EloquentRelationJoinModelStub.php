<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Reedware\LaravelRelationJoins\Tests\CustomBuilder;

class EloquentRelationJoinModelStub extends Model
{
    public static $useCustomBuilder = false;

    public function useCustomBuilder($enabled = true)
    {
        static::$useCustomBuilder = $enabled;

        return $this;
    }

    public function newEloquentBuilder($query)
    {
        return static::$useCustomBuilder
            ? new CustomBuilder($query)
            : new Builder($query);
    }
}
