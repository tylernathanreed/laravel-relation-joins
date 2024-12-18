<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @template TIntermediateModel of Model
 * @template TResult
 *
 * @phpstan-type TRelation Relation<TRelatedModel,TDeclaringModel,TResult>
 * @phpstan-type TBelongsTo BelongsTo<TRelatedModel,TDeclaringModel>
 * @phpstan-type TBelongsToMany BelongsToMany<TRelatedModel,TDeclaringModel>
 * @phpstan-type THasOne HasOne<TRelatedModel,TDeclaringModel>
 * @phpstan-type THasMany HasMany<TRelatedModel,TDeclaringModel>
 * @phpstan-type TMorphOne MorphOne<TRelatedModel,TDeclaringModel>
 * @phpstan-type TMorphMany MorphMany<TRelatedModel,TDeclaringModel>
 * @phpstan-type THasOneThrough HasOneThrough<TRelatedModel,TIntermediateModel,TDeclaringModel>
 * @phpstan-type THasManyThrough HasManyThrough<TRelatedModel,TIntermediateModel,TDeclaringModel>
 * @phpstan-type TMorphToMany MorphToMany<TRelatedModel,TDeclaringModel>
 */
class RelationJoinQuery
{
    /**
     * Adds the constraints for a relationship join.
     *
     * @param  TRelation  $relation
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @return Builder<TRelatedModel>
     */
    public static function get(
        Relation $relation,
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        ?string $alias = null
    ): Builder {
        if ($relation instanceof BelongsTo) {
            return static::belongsTo($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof MorphToMany) {
            return static::morphToMany($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof BelongsToMany) {
            return static::belongsToMany($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof HasMany) {
            return static::hasOneOrMany($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof HasOneThrough) {
            return static::hasOneOrManyThrough($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof HasManyThrough) {
            return static::hasOneOrManyThrough($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof HasOne) {
            return static::hasOneOrMany($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof MorphMany) {
            return static::morphOneOrMany($relation, $query, $parentQuery, $type, $alias);
        } elseif ($relation instanceof MorphOne) {
            return static::morphOneOrMany($relation, $query, $parentQuery, $type, $alias);
        }

        throw new InvalidArgumentException('Unsupported relation type ['.get_class($relation).'].');
    }

    /**
     * Adds the constraints for a belongs to relationship join.
     *
     * @param  TBelongsTo  $relation
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @return Builder<TRelatedModel>
     */
    protected static function belongsTo(
        BelongsTo $relation,
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        ?string $alias = null
    ): Builder {
        if (is_null($alias) && $query->getQuery()->from == $parentQuery->getQuery()->from) {
            $alias = $relation->getRelationCountHash();
        }

        if (! is_null($alias) && $alias != $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$alias);

            $query->getModel()->setTable($alias);
        }

        $query->whereColumn(
            $relation->getQualifiedOwnerKeyName(), '=', $relation->getQualifiedForeignKeyName()
        );

        return $query;
    }

    /**
     * Adds the constraints for a belongs to many relationship join.
     *
     * @param  TBelongsToMany  $relation
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @return Builder<TRelatedModel>
     */
    protected static function belongsToMany(
        BelongsToMany $relation,
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        ?string $alias = null
    ): Builder {
        if (! is_null($alias) && strpos($alias, ',') !== false) {
            [$pivotAlias, $farAlias] = explode(',', $alias);
        } else {
            [$pivotAlias, $farAlias] = [null, $alias];
        }

        if (is_null($farAlias) && $parentQuery->getQuery()->from === $query->getQuery()->from) {
            $farAlias = $relation->getRelationCountHash();
        }

        if (! is_null($farAlias) && $farAlias != $relation->getRelated()->getTable()) {
            $query->from($relation->getRelated()->getTable().' as '.$farAlias);

            $relation->getRelated()->setTable($farAlias);
        }

        if (! is_null($pivotAlias) && $pivotAlias != $relation->getTable()) {
            $table = $relation->getTable().' as '.$pivotAlias;

            $on = $pivotAlias;
        } else {
            $table = $on = $relation->getTable();
        }

        $query->join($table, function ($join) use ($relation, $on) {
            $join->on($on.'.'.$relation->getForeignPivotKeyName(), '=', $relation->getQualifiedParentKeyName());
        }, null, null, $type);

        // When a belongs to many relation uses an eloquent model to define the pivot
        // in between the two models, we should elevate the join through eloquent
        // so that query scopes can be leveraged. This is opt-in functionality.

        if (($using = $relation->getPivotClass()) != Pivot::class) {
            $query->getQuery()->joins[0] = new EloquentJoinClause(
                $query->getQuery()->joins[0], // @phpstan-ignore offsetAccess.notFound (Join is added above)
                (new $using)->setTable($on)
            );
        }

        $query->whereColumn(
            $relation->getRelated()->qualifyColumn($relation->getRelatedKeyName()),
            '=',
            $on.'.'.$relation->getRelatedPivotKeyName()
        );

        return $query;
    }

    /**
     * Adds the constraints for a has one or has many relationship join.
     *
     * @param  THasOne|THasMany|TMorphOne|TMorphMany  $relation
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @return Builder<TRelatedModel>
     */
    protected static function hasOneOrMany(
        HasOne|HasMany|MorphOne|MorphMany $relation,
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        ?string $alias = null
    ): Builder {
        if (is_null($alias) && $query->getQuery()->from == $parentQuery->getQuery()->from) {
            $alias = $relation->getRelationCountHash();
        }

        if (! is_null($alias) && $alias != $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$alias);

            $query->getModel()->setTable($alias);
        }

        $query->whereColumn(
            $query->qualifyColumn($relation->getForeignKeyName()), '=', $relation->getQualifiedParentKeyName()
        );

        return $query;
    }

    /**
     * Adds the constraints for a has one through or has many through relationship join.
     *
     * @param  THasOneThrough|THasManyThrough  $relation
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @return Builder<TRelatedModel>
     */
    protected static function hasOneOrManyThrough(
        HasOneThrough|HasManyThrough $relation,
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        ?string $alias = null
    ): Builder {
        if (! is_null($alias) && strpos($alias, ',') !== false) {
            [$throughAlias, $farAlias] = explode(',', $alias);
        } else {
            [$throughAlias, $farAlias] = [null, $alias];
        }

        if (is_null($farAlias) && $parentQuery->getQuery()->from === $query->getQuery()->from) {
            $farAlias = $relation->getRelationCountHash();
        }

        if (is_null($throughAlias) && $parentQuery->getQuery()->from === $relation->getParent()->getTable()) {
            $throughAlias = $relation->getRelationCountHash();
        }

        if (! is_null($farAlias) && $farAlias != $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$farAlias);

            $query->getModel()->setTable($farAlias);
        }

        if (! is_null($throughAlias) && $throughAlias != $relation->getParent()->getTable()) {
            $table = $relation->getParent()->getTable().' as '.$throughAlias;

            $on = $throughAlias;
        } else {
            $table = $on = $relation->getParent()->getTable();
        }

        $query->join($table, function ($join) use ($relation, $parentQuery, $on) {
            $join->on(
                $on.'.'.$relation->getFirstKeyName(),
                '=',
                $parentQuery->qualifyColumn($relation->getLocalKeyName())
            );
        }, null, null, $type);

        // The has one/many through relations use an eloquent model to define the step
        // in between the two models. To allow pivot constraints to leverage query
        // scopes, we are going to define the query through eloquent instead.

        $query->getQuery()->joins[0] = new EloquentJoinClause(
            $query->getQuery()->joins[0], // @phpstan-ignore offsetAccess.notFound (Join added above)
            $relation->getParent()->newInstance()->setTable($on)
        );

        $query->whereColumn(
            $relation->getQualifiedForeignKeyName(), '=', $on.'.'.$relation->getSecondLocalKeyName()
        );

        return $query;
    }

    /**
     * Adds the constraints for a morph one or morph many relationship join.
     *
     * @param  TMorphOne|TMorphMany  $relation
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @return Builder<TRelatedModel>
     */
    protected static function morphOneOrMany(
        MorphOne|MorphMany $relation,
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        ?string $alias = null
    ): Builder {
        if (! is_null($alias) && $alias != $relation->getRelated()->getTable()) {
            $query->from($relation->getRelated()->getTable().' as '.$alias);

            $relation->getRelated()->setTable($alias);
        }

        return static::hasOneOrMany($relation, $query, $parentQuery, $type, $alias)->where(
            $relation->getRelated()->qualifyColumn($relation->getMorphType()), '=', $relation->getMorphClass()
        );
    }

    /**
     * Adds the constraints for a morph to many relationship join.
     *
     * @param  TMorphToMany  $relation
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @return Builder<TRelatedModel>
     */
    protected static function morphToMany(
        MorphToMany $relation,
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        ?string $alias = null
    ): Builder {
        if (! is_null($alias) && strpos($alias, ',') !== false) {
            [$pivotAlias, $farAlias] = explode(',', $alias);
        } else {
            [$pivotAlias, $farAlias] = [null, $alias];
        }

        if (is_null($farAlias) && $parentQuery->getQuery()->from === $query->getQuery()->from) {
            $farAlias = $relation->getRelationCountHash();
        }

        if (! is_null($farAlias) && $farAlias != $relation->getRelated()->getTable()) {
            $query->from($relation->getRelated()->getTable().' as '.$farAlias);

            $relation->getRelated()->setTable($farAlias);
        }

        if (! is_null($pivotAlias) && $pivotAlias != $relation->getTable()) {
            $table = $relation->getTable().' as '.$pivotAlias;

            $on = $pivotAlias;
        } else {
            $table = $on = $relation->getTable();
        }

        $query->join($table, function ($join) use ($relation, $on) {
            $join->on($on.'.'.$relation->getForeignPivotKeyName(), '=', $relation->getQualifiedParentKeyName());

            $join->where($on.'.'.$relation->getMorphType(), '=', $relation->getMorphClass());
        }, null, null, $type);

        // When a belongs to many relation uses an eloquent model to define the pivot
        // in between the two models, we should elevate the join through eloquent
        // so that query scopes can be leveraged. This is opt-in functionality.

        if (($using = $relation->getPivotClass()) != Pivot::class) {
            $query->getQuery()->joins[0] = new EloquentJoinClause(
                $query->getQuery()->joins[0], // @phpstan-ignore offsetAccess.notFound (Join added above)
                (new $using)->setTable($on)
            );
        }

        $query->whereColumn(
            $relation->getRelated()->qualifyColumn($relation->getRelatedKeyName()),
            '=',
            $on.'.'.$relation->getRelatedPivotKeyName()
        );

        return $query;
    }
}
