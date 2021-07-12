<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentFileModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentImageModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;
use RuntimeException;

class MorphToTest extends TestCase
{
    /**
     * Mocks the specified select query.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @param  array   $results
     *
     * @return void
     */
    protected function mockSelect(string $sql, array $bindings, array $results)
    {
        $this->connection
            ->shouldReceive('select')
            ->with($sql, $bindings, true)
            ->andReturn(json_decode(json_encode($results), false));
    }

    /**
     * Mocks the morph selection used by {@see $query->joinMorphRelation()}.
     *
     * @param  string       $model
     * @param  string       $relation
     * @param  array        $results
     * @param  string|null  $where
     * @param  array        $bindings
     *
     * @return void
     */
    protected function mockMorphSelect(string $model, string $relation, array $results, string $where = null, array $bindings = [])
    {
        $model = new $model;
        $table = $model->getTable();
        $column = $model->{$relation}()->getMorphType();

        $this->mockSelect(
            "select distinct \"{$column}\" from \"{$table}\"" . ($where ? ' where ' . $where : ''),
            $bindings,
            array_map(function ($result) use ($column) {
                return [$column => $result];
            }, $results)
        );
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $this->mockMorphSelect(EloquentImageModelStub::class, 'imageable', [
            EloquentPostModelStub::class
        ]);

        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('imageable');

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic_alias(Closure $query, string $builderClass)
    {
        $this->mockMorphSelect(EloquentImageModelStub::class, 'imageable', [
            EloquentPostModelStub::class
        ]);

        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('imageable as imageable');

        $this->assertEquals('select * from "images" inner join "posts" as "imageable" on "imageable"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function withMorphType(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" inner join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function withMorphType_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable as imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" inner join "users" as "imageable" on "imageable"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function withMorphTypes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentFileModelStub)
            ->joinMorphRelation('link.imageable', [
                EloquentImageModelStub::class,
                EloquentUserModelStub::class
            ]);

        $this->assertEquals('select * from "files" inner join "images" on "images"."id" = "files"."link_id" and "files"."link_type" = ? inner join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentImageModelStub::class, 1 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function withMorphTypes_normal_in(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinMorphRelation('uploadedFiles.link.imageable', [
                EloquentImageModelStub::class,
                EloquentPostModelStub::class
            ]);

        $this->assertEquals('select * from "users" inner join "files" on "files"."uploaded_by_id" = "users"."id" inner join "images" on "images"."id" = "files"."link_id" and "files"."link_type" = ? inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentImageModelStub::class, 1 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function withMorphTypes_normal_between(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentFileModelStub)
            ->joinMorphRelation('link.uploadedImages.imageable', [
                EloquentUserModelStub::class,
                EloquentPostModelStub::class
            ]);

        $this->assertEquals('select * from "files" inner join "users" on "users"."id" = "files"."link_id" and "files"."link_type" = ? inner join "images" on "images"."uploaded_by_id" = "users"."id" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class, 1 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function asBelongsTo(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('postImageable');

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function multitype(Closure $query, string $builderClass)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('joinMorphRelation() does not support multiple morph types.');

        $this->mockMorphSelect(EloquentImageModelStub::class, 'imageable', [
            EloquentUserModelStub::class,
            EloquentPostModelStub::class,
        ]);

        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('imageable');
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_in(Closure $query, string $builderClass)
    {
        $this->mockMorphSelect(EloquentImageModelStub::class, 'imageable', [
            EloquentPostModelStub::class
        ]);

        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages.imageable');

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_out(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable.comments', [
                EloquentPostModelStub::class
            ]);

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ? inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->leftJoinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" left join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function rightJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->rightJoinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" right join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function crossJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->crossJoinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" cross join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function joinThrough_in(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->joinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function joinThrough_out(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable', [EloquentPostModelStub::class])
            ->joinThroughMorphRelation('imageable.comments', [EloquentPostModelStub::class]);

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ? inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoinThrough(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->leftJoinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" left join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function rightJoinThrough(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->rightJoinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" right join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function crossJoinThrough(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->crossJoinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" cross join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
