<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Reedware\LaravelRelationJoins\HasOne;

trait ForwardsHasOneCalls
{
	use ForwardsParentCalls;
    use JoinsHasOneOrManyRelations;

    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     * @return static
     */
    public static function newFromParent($parent)
    {
        return static::noConstraints(function() use ($parent) {
            return new HasOne(
                $parent->getQuery(),
                $parent->getParent(),
                $parent->getQualifiedForeignKeyName(),
                static::getParentPropertyValue($parent, 'localKey')
            );
        });
    }
}