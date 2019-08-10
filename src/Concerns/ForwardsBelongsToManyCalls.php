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
            $using = static::getParentPropertyValue($parent, 'using');

            $relation = new BelongsToMany(
                $parent->getQuery(),
                $parent->getParent(),
                $parent->getTable(),
                static::getParentPropertyValue($parent, 'foreignPivotKey'),
                static::getParentPropertyValue($parent, 'relatedPivotKey'),
                static::getParentPropertyValue($parent, 'parentKey'),
                static::getParentPropertyValue($parent, 'relatedKey'),
                $parent->getRelationName()
            );

            $relation->using($using);

            return $relation;
        });
    }
}