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
        $builder = $query(new EloquentPostModelStub)->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->leftJoinRelation('tags');

        $this->assertEquals('select * from "posts" left join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? left join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}