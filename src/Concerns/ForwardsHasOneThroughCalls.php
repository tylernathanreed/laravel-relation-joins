<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Reedware\LaravelRelationJoins\HasOneThrough;

trait ForwardsHasOneThroughCalls
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
            return new HasOneThrough(
                $parent->getQuery(),
                static::getParentPropertyValue($parent, 'farParent'),
                $parent->getParent(),
                $parent->getFirstKeyName(),
                $parent->getForeignKeyName(),
                $parent->getLocalKeyName(),
                $parent->getSecondLocalKeyName()
            );
        });
    }
}