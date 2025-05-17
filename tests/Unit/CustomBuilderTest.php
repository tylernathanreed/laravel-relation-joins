<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\CustomBuilder;
use Tests\Models\EloquentPostModelStub;
use Tests\Models\EloquentUserModelStub;

class CustomBuilderTest extends TestCase
{
    #[Test]
    public function persists_across_models()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery();

        $this->assertEquals(CustomBuilder::class, get_class($builder));

        $builder = (new EloquentPostModelStub)->newQuery();

        $this->assertEquals(CustomBuilder::class, get_class($builder));

        $builder = (new EloquentUserModelStub)->useCustomBuilder(false)->newQuery();

        $this->assertEquals(EloquentBuilder::class, get_class($builder));

        $builder = (new EloquentPostModelStub)->newQuery();

        $this->assertEquals(EloquentBuilder::class, get_class($builder));
    }

    #[Test]
    public function resets_after_test()
    {
        $builder = (new EloquentUserModelStub)->newQuery();

        $this->assertEquals(EloquentBuilder::class, get_class($builder));
    }
}
