<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Reedware\LaravelRelationJoins\RelationJoinQuery;

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @template TResult
 *
 * @mixin Relation<TRelatedModel,TDeclaringModel,TResult>
 */
class RelationJoinQueries
{
    /**
     * Defines the mixin for {@see $relation->getRelationJoinQuery()}.
     */
    public function getRelationJoinQuery(): Closure
    {
        /**
         * Adds the constraints for a relationship join.
         *
         * @param  Builder<TRelatedModel>  $query
         * @param  Builder<TDeclaringModel>  $parentQuery
         * @return Builder<TRelatedModel>
         */
        return function (Builder $query, Builder $parentQuery, string $type = 'inner', ?string $alias = null): Builder {
            /** @var Relation<TRelatedModel,TDeclaringModel,TResult> $this */
            return RelationJoinQuery::get($this, $query, $parentQuery, $type, $alias);
        };
    }
}
