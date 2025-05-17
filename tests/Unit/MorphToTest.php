<?php

namespace Tests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Models\EloquentFileModelStub;
use Tests\Models\EloquentImageModelStub;
use Tests\Models\EloquentPostModelStub;
use Tests\Models\EloquentUserModelStub;
use RuntimeException;

class MorphToTest extends TestCase
{
    /**
     * Mocks the specified select query.
     */
    protected function mockSelect(string $sql, array $bindings, array $results): void
    {
        $this->connection
            ->shouldReceive('select')
            ->with($sql, $bindings, true)
            ->andReturn(json_decode(json_encode($results), false));
    }

    /**
     * Mocks the morph selection used by {@see $query->joinMorphRelation()}.
     */
    protected function mockMorphSelect(
        string $model,
        string $relation,
        array $results,
        ?string $where = null,
        array $bindings = []
    ): void {
        $model = new $model;
        $table = $model->getTable();
        $column = $model->{$relation}()->getMorphType();

        $this->mockSelect(
            "select distinct \"{$column}\" from \"{$table}\"".($where ? ' where '.$where : ''),
            $bindings,
            array_map(function ($result) use ($column) {
                return [$column => $result];
            }, $results)
        );
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function basic(Closure $query, string $builderClass)
    {
        $this->mockMorphSelect(EloquentImageModelStub::class, 'imageable', [
            EloquentPostModelStub::class,
        ]);

        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('imageable');

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function basic_alias(Closure $query, string $builderClass)
    {
        $this->mockMorphSelect(EloquentImageModelStub::class, 'imageable', [
            EloquentPostModelStub::class,
        ]);

        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('imageable as imageable');

        $this->assertEquals('select * from "images" inner join "posts" as "imageable" on "imageable"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function with_morph_type(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" inner join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function with_morph_type_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable as imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" inner join "users" as "imageable" on "imageable"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function with_morph_types(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentFileModelStub)
            ->joinMorphRelation('link.imageable', [
                EloquentImageModelStub::class,
                EloquentUserModelStub::class,
            ]);

        $this->assertEquals('select * from "files" inner join "images" on "images"."id" = "files"."link_id" and "files"."link_type" = ? inner join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentImageModelStub::class, 1 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function with_morph_types_normal_in(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinMorphRelation('uploadedFiles.link.imageable', [
                EloquentImageModelStub::class,
                EloquentPostModelStub::class,
            ]);

        $this->assertEquals('select * from "users" inner join "files" on "files"."uploaded_by_id" = "users"."id" inner join "images" on "images"."id" = "files"."link_id" and "files"."link_type" = ? inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentImageModelStub::class, 1 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function with_morph_types_normal_between(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentFileModelStub)
            ->joinMorphRelation('link.uploadedImages.imageable', [
                EloquentUserModelStub::class,
                EloquentPostModelStub::class,
            ]);

        $this->assertEquals('select * from "files" inner join "users" on "users"."id" = "files"."link_id" and "files"."link_type" = ? inner join "images" on "images"."uploaded_by_id" = "users"."id" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class, 1 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function as_belongs_to(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('postImageable');

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
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

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function nested_in(Closure $query, string $builderClass)
    {
        $this->mockMorphSelect(EloquentImageModelStub::class, 'imageable', [
            EloquentPostModelStub::class,
        ]);

        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages.imageable');

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function nested_out(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable.comments', [
                EloquentPostModelStub::class,
            ]);

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ? inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function left_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->leftJoinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" left join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function right_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->rightJoinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" right join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function cross_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->crossJoinMorphRelation('imageable', EloquentUserModelStub::class);

        $this->assertEquals('select * from "images" cross join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentUserModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function join_through_in(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->joinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class,
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function join_through_out(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)
            ->joinMorphRelation('imageable', [EloquentPostModelStub::class])
            ->joinThroughMorphRelation('imageable.comments', [EloquentPostModelStub::class]);

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ? inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function left_join_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->leftJoinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class,
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" left join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function right_join_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->rightJoinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class,
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" right join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function cross_join_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('uploadedImages')
            ->crossJoinThroughMorphRelation('uploadedImages.imageable', [
                EloquentPostModelStub::class,
            ]);

        $this->assertEquals('select * from "users" inner join "images" on "images"."uploaded_by_id" = "users"."id" cross join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
