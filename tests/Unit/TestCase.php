<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Mockery as m;
use PHPUnit\Framework\TestCase as TestBase;
use Reedware\LaravelRelationJoins\LaravelRelationJoinServiceProvider;
use Reedware\LaravelRelationJoins\Tests\CustomBuilder;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentRelationJoinModelStub;
use RuntimeException;

class TestCase extends TestBase
{
    /**
     * The current laravel version.
     *
     * @var string
     */
    protected $version;

    /**
     * Prepares the test for execution.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->assignLaravelVersion();
        $this->setUpConnectionResolver();
        $this->registerServiceProvider();
    }

    /**
     * Locally stores the current laravel version for later comparison.
     *
     * @return void
     */
    protected function assignLaravelVersion(): void
    {
        $this->version = static::getLaravelVersion();
    }

    /**
     * Returns the current laravel version.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function getLaravelVersion(): string
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.lock'));

        if (is_null($composer)) {
            throw new RuntimeException('Unable to determine Laravel Version.');
        }

        return substr(Arr::first($composer->packages, function ($package) {
            return $package->name == 'illuminate/support';
        })->version, 1);
    }

    /**
     * Mocks the connection resolver for testing.
     *
     * @return void
     */
    protected function setUpConnectionResolver(): void
    {
        EloquentRelationJoinModelStub::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));

        $resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock(Connection::class));

        $mockConnection->shouldReceive('getQueryGrammar')->andReturn($grammar = new Grammar);
        $mockConnection->shouldReceive('getPostProcessor')->andReturn($mockProcessor = m::mock(Processor::class));
        $mockConnection->shouldReceive('query')->andReturnUsing(function () use ($mockConnection, $grammar, $mockProcessor) {
            return new BaseBuilder($mockConnection, $grammar, $mockProcessor);
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
     * Returns whether or not the specified version is after the current version.
     *
     * @param  string  $version
     *
     * @return bool
     */
    public function isVersionAfter(string $version): bool
    {
        return version_compare($this->version, $version) >= 0;
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