<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Builder as Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use ReflectionClass;

class EloquentJoinClause extends JoinClause
{
    /**
     * The model associated to this join.
     */
    public Model $model;

    /**
     * The eloquent query representing this join.
     */
    public Eloquent $eloquent;

    /**
     * Whether or not a method call is being forwarded through eloquent.
     */
    protected bool $forwardingCall = false;

    /**
     * Create a new join clause instance.
     */
    public function __construct(JoinClause $parentJoin, Model $model)
    {
        parent::__construct(
            $parentJoin->newParentQuery(),
            $parentJoin->type,
            $parentJoin->table
        );

        $this->mergeQuery($parentJoin);

        $this->model = $model;
        $this->eloquent = $this->newEloquentQuery();
    }

    /**
     * Merges the properties of the parent join into this join.
     */
    protected function mergeQuery(Builder $query): void
    {
        $properties = (new ReflectionClass(Builder::class))->getProperties();

        foreach ($properties as $property) {
            if (! $property->isPublic()) {
                continue;
            }

            $name = $property->getName();

            $this->{$name} = $query->{$name};
        }
    }

    /**
     * Apply the scopes to the eloquent builder instance and return it.
     */
    public function applyScopes(): static
    {
        $query = $this->eloquent->applyScopes();

        $this->mergeQuery($query->getQuery());

        return $this;
    }

    /**
     * Returns a new query builder for the model's table.
     */
    public function newEloquentQuery(): Eloquent
    {
        return $this->model->registerGlobalScopes(
            $this->newModelQuery()
        );
    }

    /**
     * Returns a new eloquent builder that doesn't have any global scopes or eager loading.
     */
    public function newModelQuery(): Eloquent
    {
        return $this->newEloquentBuilder()->setModel($this->model);
    }

    /**
     * Returns a new eloquent builder for this join clause.
     */
    public function newEloquentBuilder(): Eloquent
    {
        return new Eloquent($this);
    }

    /**
     * Get a new instance of the join clause builder.
     */
    public function newQuery(): JoinClause
    {
        return new JoinClause($this->newParentQuery(), $this->type, $this->table);
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array<mixed>  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // If we're already forwarding a call, pass off to the parent method
        if ($this->forwardingCall) {
            return parent::__call($method, $parameters);
        }

        // Otherwise, forward the call to eloquent
        $this->forwardingCall = true;
        $this->forwardCallTo($this->eloquent, $method, $parameters);
        $this->forwardingCall = false;

        return $this;
    }
}
