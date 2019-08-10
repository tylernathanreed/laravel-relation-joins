<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Reedware\LaravelRelationJoins\HasManyThrough;

trait ForwardsHasManyThroughCalls
{
    use ForwardsParentCalls;
    use JoinsHasOneOrManyThroughRelations;

    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     * @return static
     */
    public static function newFromParent($parent)
    {
        return static::noConstraints(function() use ($parent) {
            return new HasManyThrough(
                $parent->getQuery(),
                static::getParentPropertyValue($parent, 'farParent'),
                $parent->getParent(),
                static::getParentPropertyValue($parent, 'firstKey'),
                static::getParentPropertyValue($parent, 'secondKey'),
                static::getParentPropertyValue($parent, 'localKey'),
                static::getParentPropertyValue($parent, 'secondLocalKey')
            );
        });
    }
}