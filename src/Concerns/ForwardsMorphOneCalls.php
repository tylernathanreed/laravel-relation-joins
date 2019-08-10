<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Reedware\LaravelRelationJoins\MorphOne;

trait ForwardsMorphOneCalls
{
	use ForwardsParentCalls;
    use JoinsMorphOneOrManyRelations;

    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     * @return static
     */
    public static function newFromParent($parent)
    {
        return static::noConstraints(function() use ($parent) {
            return new MorphOne(
                $parent->getQuery(),
                $parent->getParent(),
                $parent->getQualifiedMorphType(),
                $parent->getQualifiedForeignKeyName(),
                static::getParentPropertyValue($parent, 'localKey')
            );
        });
    }
}