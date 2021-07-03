<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

class RelationJoinQuery
{
    /**
     * Adds the constraints for a relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Builder             $query
     * @param  \Illuminate\Database\Eloquent\Builder             $parentQuery
     * @param  string                                            $type
     * @param  string|null                                       $alias
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function get(Relation $relation, Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if($relation instanceof BelongsTo) {
            return static::belongsTo($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof MorphToMany) {
            return static::morphToMany($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof BelongsToMany) {
            return static::belongsToMany($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof HasMany) {
            return static::hasOneOrMany($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof HasManyThrough) {
            return static::hasOneOrManyThrough($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof HasOne) {
            return static::hasOneOrMany($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof HasOneThrough) {
            return static::hasOneOrManyThrough($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof MorphMany) {
            return static::morphOneOrMany($relation, $query, $parentQuery, $type, $alias);
        }

        else if($relation instanceof MorphOne) {
            return static::morphOneOrMany($relation, $query, $parentQuery, $type, $alias);
        }

        throw new InvalidArgumentException('Unsupported relation type [' . get_class($relation) . '].');
    }

    /**
     * Adds the constraints for a belongs to relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Builder             $query
     * @param  \Illuminate\Database\Eloquent\Builder             $parentQuery
     * @param  string                                            $type
     * @param  string|null                                       $alias
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function belongsTo(Relation $relation, Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if (is_null($alias) && $query->getQuery()->from == $parentQuery->getQuery()->from) {
            $alias = $relation->getRelationCountHash();
        }

        if (! is_null($alias) && $alias != $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$alias);

            $query->getModel()->setTable($alias);
        }

        return $query->whereColumn(
            $relation->getQualifiedOwnerKeyName(), '=', $relation->getQualifiedForeignKeyName()
        );
    }

    /**
     * Adds the constraints for a belongs to many relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Builder             $query
     * @param  \Illuminate\Database\Eloquent\Builder             $parentQuery
     * @param  string                                            $type
     * @param  string|null                                       $alias
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function belongsToMany(Relation $relation, Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if (strpos($alias, ',') !== false) {
            [$pivotAlias, $farAlias] = explode(',', $alias);
        } else {
            [$pivotAlias, $farAlias] = [null, $alias];
        }

        if (is_null($farAlias) && $parentQuery->getQuery()->from === $query->getQuery()->from) {
            $farAlias = $relation->getRelationCountHash();
        }

        if (is_null($pivotAlias) && $parentQuery->getQuery()->from === $relation->getTable()) {
            $pivotAlias = $relation->getRelationCountHash();
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

        $query->join($table, $on.'.'.$relation->getForeignPivotKeyName(), '=', $relation->getQualifiedParentKeyName(), $type);

        return $query->whereColumn(
            $relation->getRelated()->qualifyColumn($relation->getRelatedKeyName()), '=', $on.'.'.$relation->getRelatedPivotKeyName()
        );
    }

    /**
     * Adds the constraints for a has one or has many relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Builder             $query
     * @param  \Illuminate\Database\Eloquent\Builder             $parentQuery
     * @param  string                                            $type
     * @param  string|null                                       $alias
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function hasOneOrMany(Relation $relation, Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if (is_null($alias) && $query->getQuery()->from == $parentQuery->getQuery()->from) {
            $alias = $relation->getRelationCountHash();
        }

        if (! is_null($alias) && $alias != $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$alias);

            $query->getModel()->setTable($alias);
        }

        return $query->whereColumn(
            $query->qualifyColumn($relation->getForeignKeyName()), '=', $relation->getQualifiedParentKeyName()
        );
    }

    /**
     * Adds the constraints for a has one through or has many through relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Builder             $query
     * @param  \Illuminate\Database\Eloquent\Builder             $parentQuery
     * @param  string                                            $type
     * @param  string|null                                       $alias
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function hasOneOrManyThrough(Relation $relation, Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if (strpos($alias, ',') !== false) {
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
            $join->on($on.'.'.$relation->getFirstKeyName(), '=', $parentQuery->qualifyColumn($relation->getLocalKeyName()));

            if ($relation->throughParentSoftDeletes()) {
                $join->whereNull($on.'.'.$relation->getParent()->getDeletedAtColumn());
            }
        }, null, null, $type);

        return $query->whereColumn(
            $relation->getQualifiedForeignKeyName(), '=', $on.'.'.$relation->getSecondLocalKeyName()
        );
    }

    /**
     * Adds the constraints for a morph one or morph many relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Builder             $query
     * @param  \Illuminate\Database\Eloquent\Builder             $parentQuery
     * @param  string                                            $type
     * @param  string|null                                       $alias
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function morphOneOrMany(Relation $relation, Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        return static::hasOneOrMany($relation, $query, $parentQuery, $type, $alias)->where(
            $relation->getQualifiedMorphType(), '=', $relation->getMorphClass()
        );
    }

    /**
     * Adds the constraints for a morph to many relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Builder             $query
     * @param  \Illuminate\Database\Eloquent\Builder             $parentQuery
     * @param  string                                            $type
     * @param  string|null                                       $alias
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function morphToMany(Relation $relation, Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if (strpos($alias, ',') !== false) {
            [$pivotAlias, $farAlias] = explode(',', $alias);
        } else {
            [$pivotAlias, $farAlias] = [null, $alias];
        }

        if (is_null($farAlias) && $parentQuery->getQuery()->from === $query->getQuery()->from) {
            $farAlias = $relation->getRelationCountHash();
        }

        if (is_null($pivotAlias) && $parentQuery->getQuery()->from === $relation->getTable()) {
            $pivotAlias = $relation->getRelationCountHash();
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

        $query = $query ?: $relation->getQuery();

        $query->join($table, function ($join) use ($relation, $on) {
            $join->on($on.'.'.$relation->getForeignPivotKeyName(), '=', $relation->getQualifiedParentKeyName());

            $join->where($on.'.'.$relation->getMorphType(), '=', $relation->getMorphClass());
        }, null, null, $type);

        return $query->whereColumn(
            $relation->getRelated()->qualifyColumn($relation->getRelatedKeyName()), '=', $on.'.'.$relation->getRelatedPivotKeyName()
        );
    }
}