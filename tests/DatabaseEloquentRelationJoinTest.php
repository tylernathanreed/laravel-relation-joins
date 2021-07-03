<?php

namespace Reedware\LaravelRelationJoins\Tests;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Reedware\LaravelRelationJoins\LaravelRelationJoinServiceProvider;
use RuntimeException;

class DatabaseEloquentRelationJoinTest extends TestCase
{
    protected $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignLaravelVersion();
        $this->setUpConnectionResolver();
        $this->registerServiceProvider();
    }

    protected function assignLaravelVersion()
    {
        $this->version = static::getLaravelVersion();
    }

    public static function getLaravelVersion()
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../composer.lock'));

        if(is_null($composer)) {
            throw new RuntimeException('Unable to determine Laravel Version.');
        }

        return substr(Arr::first($composer->packages, function($package) {
            return $package->name == 'illuminate/support';
        })->version, 1);
    }

    protected function setUpConnectionResolver()
    {
        EloquentRelationJoinModelStub::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));

        $resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock(Connection::class));

        $mockConnection->shouldReceive('getQueryGrammar')->andReturn($grammar = new Grammar);
        $mockConnection->shouldReceive('getPostProcessor')->andReturn($mockProcessor = m::mock(Processor::class));
        $mockConnection->shouldReceive('query')->andReturnUsing(function () use ($mockConnection, $grammar, $mockProcessor) {
            return new BaseBuilder($mockConnection, $grammar, $mockProcessor);
        });        
    }

    protected function registerServiceProvider()
    {
        $container = Container::getInstance();

        $provider = $container->make(LaravelRelationJoinServiceProvider::class, ['app' => $container]);

        $provider->boot();
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function isVersionAfter($version)
    {
        return version_compare($this->version, $version) >= 0;
    }

    public function testCustomBuilder()
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

    public function testCustomBuilderResetsAfterTest()
    {
        $builder = (new EloquentUserModelStub)->newQuery();

        $this->assertEquals(EloquentBuilder::class, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasOneInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('comments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasManyInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCommentModelStub)->joinRelation('post');

        $this->assertEquals('select * from "comments" inner join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleBelongsToManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleBelongsToManyInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentRoleModelStub)->joinRelation('users');

        $this->assertEquals('select * from "roles" inner join "role_user" on "role_user"."role_id" = "roles"."id" inner join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasOneThroughRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentSupplierModelStub)->joinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasOneThroughInverseRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentUserHistoryModelStub)->joinRelation('user.supplier');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasManyThroughRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleHasManyThroughInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('user.country');

        $this->assertEquals('select * from "posts" inner join "users" on "users"."id" = "posts"."user_id" inner join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleMorphOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleMorphManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleMorphToManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testSimpleMorphedByManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentTagModelStub)->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testOnMacro(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('phone', function($join) {
            $join->on('phones.extra', '=', 'users.extra');
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."extra" = "users"."extra"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphToRelationJoinThrowsException(Closure $query, string $builderClass)
    {
        $this->expectException(RuntimeException::class);

        $builder = $query(new EloquentImageModelStub)->joinRelation('imageable');
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphOneAsHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('postImage');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphToAsBelongsToRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentImageModelStub)->joinRelation('postImageable');

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphManyAsHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentVideoModelStub)->joinRelation('videoComments');

        $this->assertEquals('select * from "videos" inner join "comments" on "comments"."commentable_id" = "videos"."id" and "commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphToManyAsBelongsToManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentVideoModelStub)->joinRelation('videoTags');

        $this->assertEquals('select * from "videos" inner join "taggables" on "taggables"."taggable_id" = "videos"."id" inner join "tags" on "tags"."id" = "taggables"."tag_id" and "taggables"."taggable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('phone as telephones');

        $this->assertEquals('select * from "users" inner join "phones" as "telephones" on "telephones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneInverseUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)->joinRelation('user as contacts');

        $this->assertEquals('select * from "phones" inner join "users" as "contacts" on "contacts"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('comments as feedback');

        $this->assertEquals('select * from "posts" inner join "comments" as "feedback" on "feedback"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyInverseUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCommentModelStub)->joinRelation('post as article');

        $this->assertEquals('select * from "comments" inner join "posts" as "article" on "article"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToManyUsingFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('roles as positions');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToManyUsingPivotAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('roles as users_roles,roles');

        $this->assertEquals('select * from "users" inner join "role_user" as "users_roles" on "users_roles"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "users_roles"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToManyUsingPivotAndFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('roles as position_user,positions');

        $this->assertEquals('select * from "users" inner join "role_user" as "position_user" on "position_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "position_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneThroughUsingFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentSupplierModelStub)->joinRelation('userHistory as revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneThroughUsingThroughAliasRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentSupplierModelStub)->joinRelation('userHistory as workers,history');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "workers"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneThroughUsingThroughAndFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentSupplierModelStub)->joinRelation('userHistory as workers,revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "workers"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneThroughInverseUsingFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentUserHistoryModelStub)->joinRelation('user.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneThroughInverseUsingThroughAliasRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentUserHistoryModelStub)->joinRelation('user as workers.supplier');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "workers"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasOneThroughInverseUsingThroughAndFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentUserHistoryModelStub)->joinRelation('user as workers.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "workers"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyThroughUsingFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('posts as articles');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyThroughUsingThroughAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('posts as citizens,posts');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyThroughUsingThroughAndFarAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('posts as citizens,articles');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphOneUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphManyUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphToManyUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMorphedByManyUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentTagModelStub)->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentSoftDeletesHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentSoftDeletesHasOneWithTrashedRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->joinRelation('phone')->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testChildSoftDeletesHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testChildSoftDeletesHasOneWithTrashedRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentAndChildSoftDeletesHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
    /**
     * @dataProvider queryDataProvider
     */
    public function testParentAndChildSoftDeletesHasOneWithTrashedParentRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->joinRelation('softDeletingPhone')->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentAndChildSoftDeletesHasOneWithTrashedChildRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentAndChildSoftDeletesHasOneWithTrashedRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->withTrashed()->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentSoftSimpleHasOneInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)->joinRelation('softDeletingUser');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" and "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testChildSoftSimpleHasOneInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingPhoneModelStub)->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" where "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentSoftDeletesHasManyThroughRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingCountryModelStub)->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" where "countries"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testThroughSoftDeletesHasManyThroughRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('postsThroughSoftDeletingUser');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id" and "users"."deleted_at" is null', $builder->toSql());
        }
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testChildSoftDeletesHasManyThroughRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('softDeletingPosts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testParentSoftDeletesBelongsToManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testChildSoftDeletesBelongsToManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('softDeletingRoles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" and "roles"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToSelfRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('manager');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "users"."manager_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToSelfUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('manager as managers');

        $this->assertEquals('select * from "users" inner join "users" as "managers" on "managers"."id" = "users"."manager_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManySelfRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('employees');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }
    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManySelfUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('employees as employees');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyThroughSelfRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('employeePosts');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "self_alias_hash"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyThroughSelfUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('employeePosts as employees,posts');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "employees"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyThroughSoftDeletingSelfUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)->joinRelation('employeePosts as employees,posts');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" where "users"."deleted_at" is null', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" and "users"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        }
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManySelfThroughRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('departmentEmployees');

        $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."department_id" = "departments"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManySelfThroughSoftDeletingUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('employeesThroughSoftDeletingDepartment as employees');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id" and "departments"."deleted_at" is null', $builder->toSql());
        }
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToManySelfRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('messagedUsers');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "messages"."to_user_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToManySelfUsingAliasRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('messagedUsers as recipients');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "recipients" on "recipients"."id" = "messages"."to_user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testThroughJoinForHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->joinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->leftJoinRelation('phone');

        $this->assertEquals('select * from "users" left join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasOneInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)->leftJoinRelation('user');

        $this->assertEquals('select * from "phones" left join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->leftJoinRelation('comments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasManyInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCommentModelStub)->leftJoinRelation('post');

        $this->assertEquals('select * from "comments" left join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftBelongsToManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->leftJoinRelation('roles');

        $this->assertEquals('select * from "users" left join "role_user" on "role_user"."user_id" = "users"."id" left join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftBelongsToManyInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentRoleModelStub)->leftJoinRelation('users');

        $this->assertEquals('select * from "roles" left join "role_user" on "role_user"."role_id" = "roles"."id" left join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasOneThroughRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentSupplierModelStub)->leftJoinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" left join "users" on "users"."supplier_id" = "suppliers"."id" left join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasOneThroughInverseRelationJoin(Closure $query, string $builderClass)
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = $query(new EloquentUserHistoryModelStub)->leftJoinRelation('user.supplier');

        $this->assertEquals('select * from "history" left join "users" on "users"."id" = "history"."user_id" left join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasManyThroughRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->leftJoinRelation('posts');

        $this->assertEquals('select * from "countries" left join "users" on "users"."country_id" = "countries"."id" left join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftHasManyThroughInverseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->leftJoinRelation('user.country');

        $this->assertEquals('select * from "posts" left join "users" on "users"."id" = "posts"."user_id" left join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftMorphOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->leftJoinRelation('image');

        $this->assertEquals('select * from "posts" left join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftMorphManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->leftJoinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftMorphToManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->leftJoinRelation('tags');

        $this->assertEquals('select * from "posts" left join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? left join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }
    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftMorphedByManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentTagModelStub)->leftJoinRelation('posts');

        $this->assertEquals('select * from "tags" left join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? left join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testRightHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->rightJoinRelation('phone');

        $this->assertEquals('select * from "users" right join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testCrossHasOneRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->crossJoinRelation('phone');

        $this->assertEquals('select * from "users" cross join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testLeftThroughJoinForHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->leftJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? left join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testRightThroughJoinForHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->rightJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? right join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testCrossThroughJoinForHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->crossJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? cross join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMultipleAliasesForBelongsToRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)->joinRelation('user as authors.country as nations');

        $this->assertEquals('select * from "posts" inner join "users" as "authors" on "authors"."id" = "posts"."user_id" inner join "countries" as "nations" on "nations"."id" = "authors"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMultipleAliasesForHasManyRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('posts as articles.comments as reviews');

        $this->assertEquals('select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id" inner join "comments" as "reviews" on "reviews"."post_id" = "articles"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testMultipleAliasesForHasManyThroughRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('posts as citizens,articles.likes as feedback,favorites');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id" inner join "comments" as "feedback" on "feedback"."post_id" = "articles"."id" inner join "likes" as "favorites" on "favorites"."comment_id" = "feedback"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasManyUsingLocalScopeRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)->joinRelation('users', function ($join) {
            $join->active();
        });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "active" = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToWithNestedClauseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('supplier', function($join) {
            $join->where(function($join) {
                $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                $join->orWhere('supplier.has_international_restrictions', 1);
            });
        });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or "supplier"."has_international_restrictions" = ?)', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testBelongsToWithRecursiveNestedClauseRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('supplier', function($join) {
            $join->where(function($join) {
                $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                $join->orWhere(function($join) {
                    $join->where('supplier.has_international_restrictions', 1);
                    $join->where('supplier.country', '!=', 'US');
                });
            });
        });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or ("supplier"."has_international_restrictions" = ? and "supplier"."country" != ?))', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testHasRelationWithinRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('posts', function($join) {
            $join->has('comments');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testDoesntHaveRelationWithinRelationJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('posts', function($join) {
            $join->doesntHave('comments');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and not exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
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

class EloquentRelationJoinModelStub extends Model
{
    public static $useCustomBuilder = false;

    public function useCustomBuilder($enabled = true)
    {
        static::$useCustomBuilder = $enabled;

        return $this;
    }

    public function newEloquentBuilder($query)
    {
        return static::$useCustomBuilder ? new CustomBuilder($query) : new EloquentBuilder($query);
    }
}

class EloquentRelationJoinPivotStub extends Pivot
{
}

class EloquentUserModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'users';

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function phone()
    {
        return $this->hasOne(EloquentPhoneModelStub::class, 'user_id', 'id');
    }

    public function softDeletingPhone()
    {
        return $this->hasOne(EloquentSoftDeletingPhoneModelStub::class, 'user_id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(EloquentRoleModelStub::class, 'role_user', 'user_id', 'role_id');
    }

    public function softDeletingRoles()
    {
        return $this->belongsToMany(EloquentSoftDeletingRoleModelStub::class, 'role_user', 'user_id', 'role_id');
    }

    public function supplier()
    {
        return $this->belongsTo(EloquentSupplierModelStub::class, 'supplier_id', 'id');
    }

    public function posts()
    {
        return $this->hasMany(EloquentPostModelStub::class, 'user_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(EloquentCountryModelStub::class, 'country_id', 'id');
    }

    public function image()
    {
        return $this->morphOne(EloquentImageModelStub::class, 'imageable');
    }

    public function manager()
    {
        return $this->belongsTo(static::class, 'manager_id');
    }

    public function employees()
    {
        return $this->hasMany(static::class, 'manager_id');
    }

    public function employeePosts()
    {
        return $this->hasManyThrough(EloquentPostModelStub::class, static::class, 'manager_id', 'user_id', 'id', 'id');
    }

    public function departmentEmployees()
    {
        return $this->hasManyThrough(static::class, EloquentDepartmentModelStub::class, 'supervisor_id', 'department_id', 'id', 'id');
    }

    public function employeesThroughSoftDeletingDepartment()
    {
        return $this->hasManyThrough(static::class, EloquentSoftDeletingDepartmentModelStub::class, 'supervisor_id', 'department_id', 'id', 'id');
    }

    public function messagedUsers()
    {
        return $this->belongsToMany(static::class, 'messages', 'from_user_id', 'to_user_id');
    }
}

class EloquentPhoneModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'phones';

    public function user()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'user_id', 'id');
    }

    public function softDeletingUser()
    {
        return $this->belongsTo(EloquentSoftDeletingUserModelStub::class, 'user_id', 'id');
    }
}

class EloquentPostModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany(EloquentCommentModelStub::class, 'post_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'user_id', 'id');
    }

    public function image()
    {
        return $this->morphOne(EloquentImageModelStub::class, 'imageable');
    }

    public function postImage()
    {
        return $this->hasOne(EloquentImageModelStub::class, 'imageable_id')->where('imageable_type', '=', static::class);
    }

    public function polymorphicComments()
    {
        return $this->morphMany(EloquentPolymorphicCommentModelStub::class, 'commentable');
    }

    public function tags()
    {
        return $this->morphToMany(EloquentTagModelStub::class, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id');
    }

    public function likes()
    {
        return $this->hasManyThrough(EloquentLikeModelStub::class, EloquentCommentModelStub::class, 'post_id', 'comment_id', 'id', 'id');
    }
}

class EloquentCommentModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'comments';

    public function post()
    {
        return $this->belongsTo(EloquentPostModelStub::class, 'post_id', 'id');
    }

    public function likes()
    {
        return $this->hasMany(EloquentLikeModelStub::class, 'comment_id', 'id');
    }
}

class EloquentRoleModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'roles';

    public function users()
    {
        return $this->belongsToMany(EloquentUserModelStub::class, 'role_user', 'role_id', 'user_id');
    }
}

class EloquentSupplierModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'suppliers';

    public function userHistory()
    {
        return $this->hasOneThrough(EloquentUserHistoryModelStub::class, EloquentUserModelStub::class, 'supplier_id', 'user_id', 'id', 'id');
    }
}

class EloquentUserHistoryModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'history';

    public function user()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'user_id', 'id');
    }
}

class EloquentCountryModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'countries';

    public function users()
    {
        return $this->hasMany(EloquentUserModelStub::class, 'country_id', 'id');
    }

    public function posts()
    {
        return $this->hasManyThrough(EloquentPostModelStub::class, EloquentUserModelStub::class, 'country_id', 'user_id', 'id', 'id');
    }

    public function postsThroughSoftDeletingUser()
    {
        return $this->hasManyThrough(EloquentPostModelStub::class, EloquentSoftDeletingUserModelStub::class, 'country_id', 'user_id', 'id', 'id');
    }

    public function softDeletingPosts()
    {
        return $this->hasManyThrough(EloquentSoftDeletingPostModelStub::class, EloquentUserModelStub::class, 'country_id', 'user_id', 'id', 'id');
    }
}

class EloquentImageModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'images';

    public function imageable()
    {
        return $this->morphTo('imageable');
    }

    public function postImageable()
    {
        return $this->belongsTo(EloquentPostModelStub::class, 'imageable_id')->where('imageable_type', '=', EloquentPostModelStub::class);
    }
}

class EloquentPolymorphicCommentModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'comments';

    public function commentable()
    {
        return $this->morphTo('commentable');
    }
}

class EloquentVideoModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'videos';

    public function polymorphicComments()
    {
        return $this->morphMany(EloquentPolymorphicCommentModelStub::class, 'commentable');
    }

    public function videoComments()
    {
        return $this->hasMany(EloquentPolymorphicCommentModelStub::class, 'commentable_id')->where('commentable_type', '=', static::class);
    }

    public function tags()
    {
        return $this->morphToMany(EloquentTagModelStub::class, 'taggable');
    }

    public function videoTags()
    {
        return $this->belongsToMany(EloquentTagModelStub::class, 'taggables', 'taggable_id', 'tag_id')->wherePivot('taggable_type', '=', static::class);
    }
}

class EloquentTagModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'tags';

    public function posts()
    {
        return $this->morphedByMany(EloquentPostModelStub::class, 'taggable', 'taggables', 'tag_id');
    }

    public function videos()
    {
        return $this->morphedByMany(EloquentVideoModelStub::class, 'taggable');
    }
}

class EloquentDepartmentModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'departments';

    public function supervisor()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'supervisor_id');
    }

    public function employees()
    {
        return $this->hasMany(EloquentUserModelStub::class, 'department_id');
    }
}

class EloquentLikeModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'likes';
}

class EloquentSoftDeletingUserModelStub extends EloquentUserModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingPhoneModelStub extends EloquentPhoneModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingPostModelStub extends EloquentPostModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingCommentModelStub extends EloquentCommentModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingRoleModelStub extends EloquentRoleModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingSupplierModelStub extends EloquentSupplierModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingUserHistoryModelStub extends EloquentUserHistoryModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingCountryModelStub extends EloquentCountryModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingImageModelStub extends EloquentImageModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingPolymorphicCommentModelStub extends EloquentPolymorphicCommentModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingVideoModelStub extends EloquentVideoModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingTagModelStub extends EloquentTagModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingDepartmentModelStub extends EloquentDepartmentModelStub
{
    use SoftDeletes;
}

class EloquentSoftDeletingUserRolePivotStub extends EloquentRelationJoinPivotStub
{
    use SoftDeletes;

    protected $table = 'role_user';
}

class CustomBuilder extends EloquentBuilder
{
    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = [
        'insert', 'insertGetId', 'getBindings', 'toSql',
        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'sum', 'getConnection',
        'myCustomOverrideforTesting'
    ];
}
