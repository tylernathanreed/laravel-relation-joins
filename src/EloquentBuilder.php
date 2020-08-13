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

    /**
     * Add an "on" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Reedware\LaravelRelationJoins\JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }
}
