<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

trait ForwardsRelationCalls
{
    use ForwardsParentCalls {
        ForwardsParentCalls::inheritProperties as __inheritProperties;
        ForwardsParentCalls::getParentPropertyValue as __getParentPropertyValue;
        ForwardsParentCalls::assignAcquiredProperties as __assignAcquiredProperties;
        ForwardsParentCalls::callFromParent as __callFromParent;
    }

    use ForwardsBelongsToCalls {
        ForwardsBelongsToCalls::getRelationJoinQuery as getBelongsToRelationJoinQuery;
        ForwardsBelongsToCalls::newFromParent as newBelongsToFromParent;
    }

    use ForwardsHasOneCalls {
        ForwardsHasOneCalls::getRelationJoinQuery as getHasOneRelationJoinQuery;
        ForwardsHasOneCalls::newFromParent as newHasOneFromParent;
    }

    use ForwardsHasManyCalls {
        ForwardsHasManyCalls::getRelationJoinQuery as getHasManyRelationJoinQuery;
        ForwardsHasManyCalls::newFromParent as newHasManyFromParent;
    }

    use ForwardsBelongsToManyCalls {
        ForwardsBelongsToManyCalls::getRelationJoinQuery as getBelongsToManyRelationJoinQuery;
        ForwardsBelongsToManyCalls::newFromParent as newBelongsToManyFromParent;
    }

    use ForwardsHasOneThroughCalls {
        ForwardsHasOneThroughCalls::getRelationJoinQuery as getHasOneThroughRelationJoinQuery;
        ForwardsHasOneThroughCalls::newFromParent as newHasOneThroughFromParent;
    }

    use ForwardsHasManyThroughCalls {
        ForwardsHasManyThroughCalls::getRelationJoinQuery as getHasManyThroughRelationJoinQuery;
        ForwardsHasManyThroughCalls::newFromParent as newHasManyThroughFromParent;

        ForwardsHasManyThroughCalls::getRelationCountHash insteadof ForwardsHasOneThroughCalls;
    }

    use ForwardsMorphOneCalls {
        ForwardsMorphOneCalls::getRelationJoinQuery as getMorphOneRelationJoinQuery;
        ForwardsMorphOneCalls::newFromParent as newMorphOneFromParent;
    }

    use ForwardsMorphManyCalls {
        ForwardsMorphManyCalls::getRelationJoinQuery as getMorphManyRelationJoinQuery;
        ForwardsMorphManyCalls::newFromParent as newMorphManyFromParent;

        ForwardsMorphManyCalls::getRelationJoinQueryForSelfRelation insteadof ForwardsMorphOneCalls;
        ForwardsMorphManyCalls::getMorphOneOrManyParentRelationJoinQuery insteadof ForwardsMorphOneCalls;
    }

    use ForwardsMorphToManyCalls {
        ForwardsMorphToManyCalls::getRelationJoinQuery as getMorphToManyRelationJoinQuery;
        ForwardsMorphToManyCalls::newFromParent as newMorphToManyFromParent;
    }

    /**
     * Adds the constraints for a relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  string  $type
     * @param  string|null  $alias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationJoinQuery(Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if($this instanceof BelongsTo) {
            return $this->getBelongsToRelationJoinQuery();
        }

        else if($this instanceof HasOne) {
            return $this->getHasOneRelationJoinQuery();
        }

        else if($this instanceof HasMany) {
            return $this->getHasManyRelationJoinQuery();
        }

        else if($this instanceof MorphToMany) {
            return $this->getMorphToManyRelationJoinQuery();
        }

        else if($this instanceof BelongsToMany) {
            return $this->getBelongsToManyRelationJoinQuery();
        }

        else if($this instanceof HasOneThrough) {
            return $this->getHasOneThroughRelationJoinQuery();
        }

        else if($this instanceof HasManyThrough) {
            return $this->getHasManyThroughRelationJoinQuery();
        }

        else if($this instanceof MorphOne) {
            return $this->getMorphOneRelationJoinQuery();
        }

        else if($this instanceof MorphMany) {
            return $this->getMorphManyRelationJoinQuery();
        }

        return $query->whereColumn(
            $this->getExistenceCompareKey(), '=', $this->getQualifiedParentKeyName()
        );
    }

    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function newFromParent($parent)
    {
        if($parent instanceof BelongsTo) {
            return static::newBelongsToFromParent($parent);
        }

        else if($parent instanceof HasOne) {
            return static::newHasOneFromParent($parent);
        }

        else if($parent instanceof HasMany) {
            return static::newHasManyFromParent($parent);
        }

        else if($parent instanceof MorphToMany) {
            return static::newMorphToManyFromParent($parent);
        }

        else if($parent instanceof BelongsToMany) {
            return static::newBelongsToManyFromParent($parent);
        }

        else if($parent instanceof HasOneThrough) {
            return static::newHasOneThroughFromParent($parent);
        }

        else if($parent instanceof HasManyThrough) {
            return static::newHasManyThroughFromParent($parent);
        }

        else if($parent instanceof MorphOne) {
            return static::newMorphOneFromParent($parent);
        }

        else if($parent instanceof MorphMany) {
            return static::newMorphManyFromParent($parent);
        }

        throw new InvalidArgumentException('Unable to construct joinable relation instance from [' . get_class($parent) . '].');
    }

    /**
     * Inherits the properties from the specified parent class.
     *
     * @param  mixed  $parent
     * @return void
     */
    public function inheritProperties($parent)
    {
        return $this->__inheritProperties($parent);
    }

    /**
     * Returns the value of the specified property from the given parent.
     *
     * @param  mixed                       $parent
     * @param  \ReflectionProperty|string  $property
     * @return mixed
     */
    public static function getParentPropertyValue($parent, $property)
    {
        return static::__getParentPropertyValue($parent, $property);
    }

    /**
     * Assigns the acquired properties from this class back onto the parent.
     *
     * @param  mixed  $parent
     * @return void
     */
    public function assignAcquiredProperties($parent)
    {
        return $this->__assignAcquiredProperties($parent);
    }

    /**
     * Handles an incoming call from the specified parent instance.
     *
     * @param  mixed   $parent
     * @param  string  $method
     * @param  array   $arguments
     * @return mixed
     */
    public static function callFromParent($parent, $method, $arguments = [])
    {
        return static::__callFromParent($parent, $method, $arguments);
    }
}