<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Builder as Eloquent;

class EloquentBuilder extends Eloquent
{
	use Concerns\ForwardsParentCalls;
	use Concerns\JoinsRelationships;

    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     * @return static
     */
    public static function newFromParent($parent)
    {
        return new static($parent->getQuery());
    }
}