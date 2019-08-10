<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Reedware\LaravelRelationJoins\BelongsToMany;

trait ForwardsBelongsToManyCalls
{
	use ForwardsParentCalls;
    use JoinsBelongsToManyRelations;

    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     * @return static
     */
    public static function newFromParent($parent)
    {
        return static::noConstraints(function() use ($parent) {
            $using = $parent->getPivotClass();

            $relation = new BelongsToMany(
                $parent->getQuery(),
                $parent->getParent(),
                $parent->getTable(),
                $parent->getForeignPivotKeyName(),
                $parent->getRelatedPivotKeyName(),
                $parent->getParentKeyName(),
                $parent->getRelatedKeyName(),
                $parent->getRelationName()
            );

            $relation->using($using);

            return $relation;
        });
    }
}