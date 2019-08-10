<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Reedware\LaravelRelationJoins\MorphToMany;

trait ForwardsMorphToManyCalls
{
	use ForwardsParentCalls;
    use JoinsMorphToManyRelations;

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

            $relation = new MorphToMany(
                $parent->getQuery(),
                $parent->getParent(),
                substr($parent->getMorphType(), 0, -5),
                $parent->getTable(),
                $parent->getForeignPivotKeyName(),
                $parent->getRelatedPivotKeyName(),
                $parent->getParentKeyName(),
                $parent->getRelatedKeyName(),
                $parent->getRelationName(),
                $parent->getInverse()
            );

            $relation->using($using);

            return $relation;
        });
    }
}