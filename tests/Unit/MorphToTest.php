<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentImageModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;
use RuntimeException;

class MorphToTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic_throws_exception(Closure $query, string $builderClass)
    {
        $this->expectException(RuntimeException::class);

        $builder = $query(new EloquentImageModelStub)
            ->joinRelation('imageable');

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
}