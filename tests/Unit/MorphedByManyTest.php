<?php

namespace Tests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Models\EloquentPostModelStub;
use Tests\Models\EloquentTagModelStub;

class MorphedByManyTest extends TestCase
{
    #[Test]
    #[DataProvider('queryDataProvider')]
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentTagModelStub)
            ->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentTagModelStub)
            ->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function circular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentTagModelStub)
            ->joinRelation('tags');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "tags" as "self_alias_hash" on "self_alias_hash"."id" = "taggables"."taggable_id"', preg_replace('/\b(laravel_reserved_\d+)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals([0 => EloquentTagModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function left_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentTagModelStub)
            ->leftJoinRelation('posts');

        $this->assertEquals('select * from "tags" left join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? left join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
