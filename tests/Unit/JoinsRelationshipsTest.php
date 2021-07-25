<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentCountryModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;

class JoinsRelationshipsTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function anonymousRelation(Closure $query, string $builderClass)
    {
        $relation = Relation::noConstraints(function () {
            return (new EloquentUserModelStub)
                ->belongsTo(EloquentCountryModelStub::class, 'country_name', 'name');
        });

        $builder = $query(new EloquentUserModelStub)
            ->joinRelation($relation);

        $this->assertEquals('select * from "users" inner join "countries" on "countries"."name" = "users"."country_name"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function anonymousRelation_alias(Closure $query, string $builderClass)
    {
        $relation = Relation::noConstraints(function () {
            return (new EloquentUserModelStub)
                ->belongsTo(EloquentCountryModelStub::class, 'kingdom_name', 'name');
        });

        $builder = $query(new EloquentUserModelStub)
            ->joinRelation([$relation, 'kingdoms']);

        $this->assertEquals('select * from "users" inner join "countries" as "kingdoms" on "kingdoms"."name" = "users"."kingdom_name"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function singleconstraint_array(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts', [
                function ($join) { $join->where('posts.active', '=', true); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."active" = ?', $builder->toSql());
        $this->assertEquals([true], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_sequential(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments', [
                function ($join) { $join->where('posts.active', '=', true); },
                function ($join) { $join->where('comments.likes', '>=', 10); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."likes" >= ?', $builder->toSql());
        $this->assertEquals([true, 10], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_associative(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments', [
                'comments' => function ($join) { $join->where('comments.likes', '>=', 10); },
                'posts' => function ($join) { $join->where('posts.active', '=', true); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."likes" >= ?', $builder->toSql());
        $this->assertEquals([true, 10], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts as articles.comments as threads', [
                'comments as threads' => function ($join) { $join->where('threads.likes', '>=', 10); },
                'posts as articles' => function ($join) { $join->where('articles.active', '=', true); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id" and "articles"."active" = ? inner join "comments" as "threads" on "threads"."post_id" = "articles"."id" and "threads"."likes" >= ?', $builder->toSql());
        $this->assertEquals([true, 10], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_single_first(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments', [
                'posts' => function ($join) { $join->where('posts.active', '=', true); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."active" = ? inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals([true], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_single_last(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments', [
                'comments' => function ($join) { $join->where('comments.likes', '>=', 10); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."likes" >= ?', $builder->toSql());
        $this->assertEquals([10], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_single_middle(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments.likes', [
                'comments' => function ($join) { $join->where('comments.likes', '>=', 10); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."likes" >= ? inner join "likes" on "likes"."comment_id" = "comments"."id"', $builder->toSql());
        $this->assertEquals([10], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_skip_middle_sequential(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments.likes', [
                function ($join) { $join->where('posts.active', '=', true); },
                null,
                function ($join) { $join->where('likes.emoji', '=', 'thumbs-up'); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" inner join "likes" on "likes"."comment_id" = "comments"."id" and "likes"."emoji" = ?', $builder->toSql());
        $this->assertEquals([true, 'thumbs-up'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_skip_middle_associative(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments.likes', [
                'posts' => function ($join) { $join->where('posts.active', '=', true); },
                'likes' => function ($join) { $join->where('likes.emoji', '=', 'thumbs-up'); }
            ]);

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" inner join "likes" on "likes"."comment_id" = "comments"."id" and "likes"."emoji" = ?', $builder->toSql());
        $this->assertEquals([true, 'thumbs-up'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multiconstraint_mix_type(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments.likes', [
                'posts' => function ($join) { $join->type = 'left'; },
                'likes' => function ($join) { $join->type = 'right'; }
            ]);

        $this->assertEquals('select * from "users" left join "posts" on "posts"."user_id" = "users"."id" inner join "comments" on "comments"."post_id" = "posts"."id" right join "likes" on "likes"."comment_id" = "comments"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
