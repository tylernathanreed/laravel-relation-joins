<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Qualify
{
    /**
     * Qualified the specified column using the given instance.
     *
     * @param  mixed  $instance
     *
     * @return $column
     */
    public static function column($instance, $column)
    {
        if($instance instanceof Builder) {
            return static::query($instance, $column);
        }

        if($instance instanceof Model) {
            return static::model($instance, $column);
        }
    }

    /**
     * Qualified the specified column using the given eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return $column
     */
    public static function query(Builder $query, $column)
    {
        if (method_exists($query, 'qualifyColumn')) {
            return $query->qualifyColumn($column);
        }

        return static::model($query->getModel(), $column);
    }

    /**
     * Qualified the specified column using the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return $column
     */
    public static function model(Model $model, $column)
    {
        if (method_exists($model, 'qualifyColumn')) {
            return $model->qualifyColumn($column);
        }

        if (Str::contains($column, '.')) {
            return $column;
        }

        return $model->getTable().'.'.$column;
    }
}