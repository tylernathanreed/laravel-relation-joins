<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase as TestBase;
use Reedware\LaravelRelationJoins\LaravelRelationJoinServiceProvider;
use Reedware\LaravelRelationJoins\Tests\CustomBuilder;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentRelationJoinModelStub;

class TestCase extends TestBase
{
    /**
     * The mocked database connection.
     */
    protected Connection&MockInterface $connection;

    /**
     * The mocked query results processor.
     */
    protected Processor&MockInterface $processor;

    /**
     * Prepares the test for execution.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpConnectionResolver();
        $this->registerServiceProvider();
    }

    /**
     * Mocks the connection resolver for testing.
     */
    protected function setUpConnectionResolver(): void
    {
        EloquentRelationJoinModelStub::setConnectionResolver(
            $resolver = Mockery::mock(ConnectionResolverInterface::class)
        );

        $this->connection = Mockery::mock(Connection::class);

        $this->processor = Mockery::mock(Processor::class)
            ->makePartial();

        $this->processor->shouldNotReceive('processInsertGetId');

        $resolver
            ->shouldReceive('connection')
            ->andReturn($this->connection);

        $this->connection
            ->shouldReceive('getQueryGrammar')
            ->andReturn($grammar = new Grammar);

        $this->connection
            ->shouldReceive('getPostProcessor')
            ->andReturn($this->processor);

        $this->connection
            ->shouldReceive('query')
            ->andReturnUsing(function () use ($grammar) {
                return new BaseBuilder($this->connection, $grammar, $this->processor);
            });
    }

    /**
     * Registers the package service provider.
     */
    protected function registerServiceProvider(): void
    {
        $container = Container::getInstance();

        $provider = $container->make(LaravelRelationJoinServiceProvider::class, ['app' => $container]);

        $provider->boot();
    }

    /**
     * Cleans up after the test has been exected.
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Returns the query resolvers for each test.
     *
     * @return array<string,array<mixed>>
     */
    public static function queryDataProvider(): array
    {
        $newQuery = function ($model) {
            return $model->useCustomBuilder(false)->newQuery();
        };

        $customQuery = function ($model) {
            return $model->useCustomBuilder()->newQuery();
        };

        return [
            'Eloquent Builder' => [$newQuery, EloquentBuilder::class],
            'Custom Builder' => [$customQuery, CustomBuilder::class],
        ];
    }
}
