<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use Reedware\LaravelRelationJoins\Tests\CustomRelation;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;

class RelationJoinQueryTest extends TestCase
{
    /** @test */
    public function nonRelation()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(EloquentUserModelStub::class . '::active must return a relationship instance.');

        $builder = (new EloquentUserModelStub)
            ->useCustomBuilder(false)
            ->joinRelation('active');
    }

    /** @test */
    public function unsupportedRelation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported relation type [' . CustomRelation::class . '].');

        $builder = (new EloquentUserModelStub)
            ->useCustomBuilder(false)
            ->joinRelation('customRelation');
    }
}
