<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;

class MorphOneTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
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
            ->leftJoinRelation('image');

        $this->assertEquals('select * from "posts" left join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
