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
            $using = static::getParentPropertyValue($parent, 'using');

            $relation = new MorphToMany(
                $parent->getQuery(),
                $parent->getParent(),
                substr($parent->getMorphType(), 0, -5),
                $parent->getTable(),
                static::getParentPropertyValue($parent, 'foreignPivotKey'),
                static::getParentPropertyValue($parent, 'relatedPivotKey'),
                static::getParentPropertyValue($parent, 'parentKey'),
                static::getParentPropertyValue($parent, 'relatedKey'),
                $parent->getRelationName(),
                static::getParentPropertyValue($parent, 'inverse')
            );

            $relation->using($using);

            return $relation;
        });
    }
}