<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;

class MorphManyTest extends TestCase
{
    #[Test]
    #[DataProvider('queryDataProvider')]
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->leftJoinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
