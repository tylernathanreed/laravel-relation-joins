<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase as TestBase;
use Reedware\LaravelRelationJoins\LaravelRelationJoinServiceProvider;
use Reedware\LaravelRelationJoins\Tests\CustomBuilder;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentRelationJoinModelStub;

class TestCase extends TestBase
{
    /**
     * The mocked database connection.
     *
     * @var \Mockery\Mock
     */
    protected $connection;

    /**
     * Prepares the test for execution.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpConnectionResolver();
        $this->registerServiceProvider();
    }

    /**
     * Mocks the connection resolver for testing.
     *
     * @return void
     */
    protected function setUpConnectionResolver(): void
    {
        EloquentRelationJoinModelStub::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));

        $this->connection = m::mock(Connection::class);

        $processor = m::mock(Processor::class)
            ->makePartial();

        $processor->shouldNotReceive('processInsertGetId');

        $resolver
            ->shouldReceive('connection')
            ->andReturn($this->connection);

        $this->connection
            ->shouldReceive('getQueryGrammar')
            ->andReturn($grammar = new Grammar);

        $this->connection
            ->shouldReceive('getPostProcessor')
            ->andReturn($processor);

        $this->connection
            ->shouldReceive('query')
            ->andReturnUsing(function () use ($grammar, $processor) {
                return new BaseBuilder($this->connection, $grammar, $processor);
            });
    }

    /**
     * Registers the package service provider.
     *
     * @return void
     */
    protected function registerServiceProvider(): void
    {
        $container = Container::getInstance();

        $provider = $container->make(LaravelRelationJoinServiceProvider::class, ['app' => $container]);

        $provider->boot();
    }

    /**
     * Cleans up after the test has been exected.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Returns the query resolvers for each test.
     *
     * @return array
     */
    public function queryDataProvider()
    {
        $newQuery = function ($model) {
            return $model->useCustomBuilder(false)->newQuery();
        };

        $customQuery = function ($model) {
            return $model->useCustomBuilder()->newQuery();
        };

        return [
            'Eloquent Builder' => [$newQuery, EloquentBuilder::class],
            'Custom Builder' => [$customQuery, CustomBuilder::class]
        ];
    }
}
