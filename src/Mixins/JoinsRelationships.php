<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use LogicException;
use Reedware\LaravelRelationJoins\EloquentJoinClause;
use Reedware\LaravelRelationJoins\MorphTypes;
use RuntimeException;

/** @mixin Builder */
class JoinsRelationships
{
    /**
     * Defines the mixin for {@see $query->joinRelation()}.
     */
    public function joinRelation(): Closure
    {
        /**
         * Add a relationship join condition to the query.
         *
         * @param  Relation|string|array<Relation|string>  $relation
         * @param  Closure|array<string,Closure>|null  $callback
         * @param  MorphTypes|array<class-string<Model>>|class-string<Model>|true  $morphTypes
         */
        return function (
            Relation|string|array $relation,
            Closure|array|null $callback = null,
            string $type = 'inner',
            bool $through = false,
            ?Builder $relatedQuery = null,
            MorphTypes|array|string|bool $morphTypes = true
        ): Builder {
            /** @var Builder $this */
            if (! $morphTypes instanceof MorphTypes) {
                $morphTypes = new MorphTypes($morphTypes); // @phpstan-ignore-line
            }

            if (is_string($relation)) {
                if (strpos($relation, '.') !== false) {
                    return $this->joinNestedRelation($relation, $callback, $type, $through, $morphTypes);
                }

                if (($parts = preg_split('/\s+as\s+/i', $relation)) && count($parts) >= 2) {
                    [$relationName, $alias] = $parts;
                } else {
                    $relationName = $relation;
                }

                $relation = ($relatedQuery ?: $this)->getRelationWithoutConstraints($relationName);

                if (! $relation instanceof Relation) {
                    throw new LogicException(sprintf(
                        '%s::%s must return a relationship instance.',
                        get_class($this->getModel()),
                        $relationName)
                    );
                }
            } elseif (is_array($relation)) {
                [$relation, $alias] = $relation;
            }

            if ($relation instanceof MorphTo) {
                $relation = $this->getBelongsToJoinRelation($relation, $morphTypes, $relatedQuery ?: $this);
            }

            $joinQuery = $relation->getRelationJoinQuery(
                $relation->getRelated()->newQuery(), $relatedQuery ?: $this, $type, $alias ?? null
            );

            // If we're simply passing through a relation, then we want to advance the relation
            // without actually applying any joins. Presumably the developer has already used
            // a modified version of this join, and they don't want to do it all over again.
            if ($through) {
                return $this->applyJoinScopes($joinQuery);
            }

            // Next we will call any given callback as an "anonymous" scope so they can get the
            // proper logical grouping of the where clauses if needed by this Eloquent query
            // builder. Then, we will be ready to finalize and return this query instance.

            if (is_array($callback)) {
                $callback = reset($callback);
            }

            if ($callback) {
                $this->callJoinScope($joinQuery, $callback);
            } else {
                $this->applyJoinScopes($joinQuery);
            }

            $joinType = $joinQuery->getJoinType();

            $this->addJoinRelationWhere(
                $joinQuery, $relation, $joinType ?: $type
            );

            return ! is_null($relatedQuery) ? $joinQuery : $this;

        };
    }

    /**
     * Defines the mixin for {@see $query->joinNestedRelation()}.
     */
    public function joinNestedRelation(): Closure
    {
        /**
         * Add nested relationship join conditions to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callbacks
         */
        return function (
            string $relations,
            Closure|array|null $callbacks,
            string $type,
            bool $through,
            MorphTypes $morphTypes
        ): Builder {
            /** @var Builder $this */
            $relations = explode('.', $relations);

            $relatedQuery = $this;

            $callbacks = is_array($callbacks)
                ? (
                    Arr::isAssoc($callbacks)
                        ? $callbacks
                        : array_combine($relations, $callbacks)
                )
                : [end($relations) => $callbacks];

            while (count($relations) > 0) {
                $relation = array_shift($relations);
                $callback = $callbacks[$relation] ?? null;
                $useThrough = count($relations) > 0 && $through;

                $relatedQuery = $this->joinRelation(
                    $relation,
                    $callback,
                    $type,
                    $useThrough,
                    $relatedQuery,
                    $morphTypes
                );
            }

            return $this;
        };
    }

    /**
     * Defines the mixin for {@see $query->applyJoinScopes()}.
     */
    public function applyJoinScopes(): Closure
    {
        /**
         * Applies the eloquent scopes to the specified query.
         */
        return function (Builder $joinQuery): Builder {
            /** @var Builder $this */
            $joins = $joinQuery->getQuery()->joins ?: [];

            foreach ($joins as $join) {
                if ($join instanceof EloquentJoinClause) {
                    $join->applyScopes();
                }
            }

            return $joinQuery;
        };
    }

    /**
     * Defines the mixin for {@see $query->callJoinScope()}.
     */
    public function callJoinScope(): Closure
    {
        /**
         * Calls the provided callback on the join query.
         */
        return function (Builder $joinQuery, Closure $callback): void {
            /** @var Builder $this */
            $joins = $joinQuery->getQuery()->joins ?: [];

            array_unshift($joins, $joinQuery);

            $queries = array_map(function ($join) {
                return $join instanceof JoinClause ? $join : $join->getQuery();
            }, $joins);

            // We will keep track of how many wheres are on each query before running the
            // scope so that we can properly group the added scope constraints in each
            // query as their own isolated nested where statement and avoid issues.

            $originalWhereCounts = array_map(function ($query) {
                return count($query->wheres ?: []);
            }, $queries);

            $callback(...$joins);

            $this->applyJoinScopes($joinQuery);

            foreach ($originalWhereCounts as $index => $count) {
                if (count($queries[$index]->wheres ?: []) > $count) {
                    $joinQuery->addNewWheresWithinGroup($queries[$index], $count);
                }
            }

            // Once the constraints have been applied, we'll need to shuffle arounds the
            // bindings so that the base query receives everything. We will apply all
            // of the bindings from the subsequent joins onto the first query.

            $joinQuery->getQuery()->bindings['join'] = [];

            array_shift($queries);

            foreach ($queries as $query) {
                $joinQuery->addBinding($query->getBindings(), 'join');
            }
        };
    }

    /**
     * Defines the mixin for {@see $query->getJoinType()}.
     */
    public function getJoinType(): Closure
    {
        /**
         * Returns the custom provided join type.
         */
        return function (): ?string {
            /** @var Builder $this */
            if (! property_exists($this, 'type')) {
                return null;
            }

            // There's a weird quirk in PHP where if a dynamic property was added
            // to a class, and the class has the magic "__get" method defined,
            // accessing the property yields a value, but also throws too.

            try {
                $type = $this->type;
            }

            // There's a bug with code coverage that for whatever reason does not
            // consider the "finally" line to be covered, but its contents are.
            // While it looks weird, we are going to ignore coverage there.

            // @codeCoverageIgnoreStart
            finally {
                // @codeCoverageIgnoreEnd
                return $type;
            }
        };
    }

    /**
     * Defines the mixin for {@see $query->addJoinRelationWhere()}.
     */
    public function addJoinRelationWhere(): Closure
    {
        /**
         * Add the "join relation" condition where clause to the query.
         */
        return function (Builder $joinQuery, Relation $relation, string $type): Builder {
            /** @var Builder $this */
            $joinQuery->mergeConstraintsFrom($relation->getQuery());

            $baseJoinQuery = $joinQuery->toBase();

            if (! empty($baseJoinQuery->joins)) {
                $this->mergeJoins($baseJoinQuery->joins, $baseJoinQuery->bindings['join']);
            }

            $this->join($baseJoinQuery->from, function ($join) use ($baseJoinQuery) {

                // There's an issue with mixing query builder where clauses
                // with join builder where clauses. To solve for this, we
                // have to recursively replace the nested where queries.

                $this->replaceWhereNestedQueryBuildersWithJoinBuilders($baseJoinQuery);

                $join->mergeWheres($baseJoinQuery->wheres, $baseJoinQuery->bindings['where']);

            }, null, null, $type);

            return $this;
        };
    }

    /**
     * Defines the mixin for {@see $query->getBelongsToJoinRelation()}.
     */
    public function getBelongsToJoinRelation(): Closure
    {
        /**
         * Returns the belongs to relation for the next morph.
         */
        return function (MorphTo $relation, MorphTypes $morphTypes, Builder $relatedQuery): BelongsTo {
            /** @var Builder $this */

            // When it comes to joining across morph types, we can really only support
            // a single type. However, when we're provided multiple types, we will
            // instead use these one at a time and pass the information along.

            if ($morphTypes->all) {
                $types = $relatedQuery->model
                    ->newQuery()
                    ->distinct()
                    ->pluck($relation->getMorphType())
                    ->filter()
                    ->all();

                $types = array_unique(array_map(function ($morphType) {
                    return Relation::getMorphedModel($morphType) ?? $morphType;
                }, $types));

                if (count($types) > 1) {
                    throw new RuntimeException('joinMorphRelation() does not support multiple morph types.');
                }

                $morphTypes->items = $types;
            }

            // We're going to handle the morph type join as a belongs to relationship
            // that has the type itself constrained. This allows us to join into a
            // singular table, which bypasses the typical headache of morphs.

            if (count($morphTypes->items) == 0) {
                throw new RuntimeException('joinMorphRelation() requires at least one morph type.');
            }

            $morphType = array_shift($morphTypes->items);

            $belongsTo = $relatedQuery->getBelongsToRelation($relation, $morphType);

            $belongsTo->where(
                $relatedQuery->qualifyColumn($relation->getMorphType()),
                '=',
                (new $morphType)->getMorphClass()
            );

            return $belongsTo;
        };
    }

    /**
     * Defines the mixin for {@see $query->leftJoinRelation()}.
     */
    public function leftJoinRelation(): Closure
    {
        /**
         * Add a relationship left join condition to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callback
         */
        return function (string $relation, Closure|array|null $callback = null, bool $through = false): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'left', $through);
        };
    }

    /**
     * Defines the mixin for {@see $query->rightJoinRelation()}.
     */
    public function rightJoinRelation(): Closure
    {
        /**
         * Add a relationship right join condition to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callback
         */
        return function (string $relation, Closure|array|null $callback = null, bool $through = false): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'right', $through);
        };
    }

    /**
     * Defines the mixin for {@see $query->crossJoinRelation()}.
     */
    public function crossJoinRelation(): Closure
    {
        /**
         * Add a relationship cross join condition to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callback
         */
        return function (string $relation, Closure|array|null $callback = null, bool $through = false): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'cross', $through);
        };
    }

    /**
     * Defines the mixin for {@see $query->joinThroughRelation()}.
     */
    public function joinThroughRelation(): Closure
    {
        /**
         * Add a relationship join condition through a related model to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callback
         */
        return function (string $relation, Closure|array|null $callback = null, string $type = 'inner'): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, $type, true);
        };
    }

    /**
     * Defines the mixin for {@see $query->leftJoinThroughRelation()}.
     */
    public function leftJoinThroughRelation(): Closure
    {
        /**
         * Add a relationship left join condition through a related model to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callback
         */
        return function (string $relation, Closure|array|null $callback = null): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'left', true);
        };
    }

    /**
     * Defines the mixin for {@see $query->rightJoinThroughRelation()}.
     */
    public function rightJoinThroughRelation(): Closure
    {
        /**
         * Add a relationship right join condition through a related model to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callback
         */
        return function (string $relation, Closure|array|null $callback = null): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'right', true);
        };
    }

    /**
     * Defines the mixin for {@see $query->crossJoinThroughRelation()}.
     */
    public function crossJoinThroughRelation(): Closure
    {
        /**
         * Add a relationship cross join condition through a related model to the query.
         *
         * @param  Closure|array<string,Closure>|null  $callback
         */
        return function (string $relation, Closure|array|null $callback = null): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'cross', true);
        };
    }

    /**
     * Defines the mixin for {@see $query->joinMorphRelation()}.
     */
    public function joinMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship join condition to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<class-string<Model>>|class-string<Model>|true  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string|bool $morphTypes = true,
            Closure|array|null $callback = null,
            string $type = 'inner',
            bool $through = false,
            ?Builder $relatedQuery = null
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, $type, $through, $relatedQuery, $morphTypes);
        };
    }

    /**
     * Defines the mixin for {@see $query->leftJoinMorphRelation()}.
     */
    public function leftJoinMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship left join condition to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<string>|string  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string $morphTypes = ['*'],
            Closure|array|null $callback = null,
            bool $through = false
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'left', $through, null, $morphTypes);
        };
    }

    /**
     * Defines the mixin for {@see $query->rightJoinMorphRelation()}.
     */
    public function rightJoinMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship right join condition to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<string>|string  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string $morphTypes = ['*'],
            Closure|array|null $callback = null,
            bool $through = false
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'right', $through, null, $morphTypes);
        };
    }

    /**
     * Defines the mixin for {@see $query->crossJoinMorphRelation()}.
     */
    public function crossJoinMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship cross join condition to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<string>|string  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string $morphTypes = ['*'],
            Closure|array|null $callback = null,
            bool $through = false
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'cross', $through, null, $morphTypes);
        };
    }

    /**
     * Defines the mixin for {@see $query->joinThroughMorphRelation()}.
     */
    public function joinThroughMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship join condition through a related model to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<string>|string  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string $morphTypes = ['*'],
            Closure|array|null $callback = null,
            string $type = 'inner'
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, $type, true, null, $morphTypes);
        };
    }

    /**
     * Defines the mixin for {@see $query->leftJoinThroughMorphRelation()}.
     */
    public function leftJoinThroughMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship left join condition through a related model to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<string>|string  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string $morphTypes = ['*'],
            Closure|array|null $callback = null
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'left', true, null, $morphTypes);
        };
    }

    /**
     * Defines the mixin for {@see $query->rightJoinThroughMorphRelation()}.
     */
    public function rightJoinThroughMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship right join condition through a related model to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<string>|string  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string $morphTypes = ['*'],
            Closure|array|null $callback = null
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'right', true, null, $morphTypes);
        };
    }

    /**
     * Defines the mixin for {@see $query->crossJoinThroughMorphRelation()}.
     */
    public function crossJoinThroughMorphRelation(): Closure
    {
        /**
         * Add a morph to relationship cross join condition through a related model to the query.
         *
         * @param  string|array<Relation|string>  $relation
         * @param  array<string>|string  $morphTypes
         * @param  Closure|array<Closure>|null  $callback
         */
        return function (
            string|array $relation,
            array|string $morphTypes = ['*'],
            Closure|array|null $callback = null
        ): Builder {
            /** @var Builder $this */
            return $this->joinRelation($relation, $callback, 'cross', true, null, $morphTypes);
        };
    }
}
