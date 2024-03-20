<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;

class MorphOneTest extends TestCase
{
    #[Test]
    #[DataProvider('queryDataProvider')]
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias_not_nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('image as photos');

        $this->assertEquals('select * from "posts" inner join "images" as "photos" on "photos"."imageable_id" = "posts"."id" and "photos"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias_nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('image as photos.uploadedBy');

        $this->assertEquals('select * from "posts" inner join "images" as "photos" on "photos"."imageable_id" = "posts"."id" and "photos"."imageable_type" = ? inner join "users" on "users"."id" = "photos"."uploaded_by_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->leftJoinRelation('image');

        $this->assertEquals('select * from "posts" left join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
