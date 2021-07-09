<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;

class MorphToManyTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_far(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tags as labels');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" as "labels" on "labels"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_pivot(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tags as labelables,labels');

        $this->assertEquals('select * from "posts" inner join "taggables" as "labelables" on "labelables"."taggable_id" = "posts"."id" and "labelables"."taggable_type" = ? inner join "tags" as "labels" on "labels"."id" = "labelables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->leftJoinRelation('tags');

        $this->assertEquals('select * from "posts" left join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? left join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tags', function ($join) {
                $join->where('tags.name', '=', 'video');
            });

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id" and "tags"."name" = ?', $builder->toSql());
        $this->assertEquals([EloquentPostModelStub::class, 'video'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tags', function ($join, $pivot) {
                $pivot->where('taggables.scope', '=', 'global');
            });

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? and "taggables"."scope" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([EloquentPostModelStub::class, 'global'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tagsUsingPivotModel', function ($join, $pivot) {
                $pivot->where('taggables.scope', '=', 'global');
            });

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? and "taggables"."scope" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([EloquentPostModelStub::class, 'global'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model_scope(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tagsUsingPivotModel', function ($join, $pivot) {
                $pivot->global();
            });

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? and "scope" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([EloquentPostModelStub::class, 'global'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model_softDeletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tagsUsingSoftDeletingPivotModel');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? and "taggables"."deleted_at" is null inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model_softDeletes_withTrashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('tagsUsingSoftDeletingPivotModel', function ($join, $pivot) {
                $pivot->withTrashed();
            });

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
