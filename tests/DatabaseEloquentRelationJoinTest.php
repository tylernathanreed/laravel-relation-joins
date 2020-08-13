<?php

namespace Reedware\LaravelRelationJoins\Tests;

use Mockery as m;
use RuntimeException;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Reedware\LaravelRelationJoins\LaravelRelationJoinServiceProvider;

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

    public function testSimpleHasOneRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testSimpleHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleHasOneInverseRelationJoin()
    {
        $builder = (new EloquentPhoneModelStub)->newQuery()->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
    }

    public function testSimpleHasOneInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPhoneModelStub)->useCustomBuilder()->newQuery()->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleHasManyRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('comments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
    }

    public function testSimpleHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('comments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleHasManyInverseRelationJoin()
    {
        $builder = (new EloquentCommentModelStub)->newQuery()->joinRelation('post');

        $this->assertEquals('select * from "comments" inner join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
    }

    public function testSimpleHasManyInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCommentModelStub)->useCustomBuilder()->newQuery()->joinRelation('post');

        $this->assertEquals('select * from "comments" inner join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleBelongsToManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
    }

    public function testSimpleBelongsToManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleBelongsToManyInverseRelationJoin()
    {
        $builder = (new EloquentRoleModelStub)->newQuery()->joinRelation('users');

        $this->assertEquals('select * from "roles" inner join "role_user" on "role_user"."role_id" = "roles"."id" inner join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
    }

    public function testSimpleBelongsToManyInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentRoleModelStub)->useCustomBuilder()->newQuery()->joinRelation('users');

        $this->assertEquals('select * from "roles" inner join "role_user" on "role_user"."role_id" = "roles"."id" inner join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleHasOneThroughRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->newQuery()->joinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testSimpleHasOneThroughRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->useCustomBuilder()->newQuery()->joinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleHasOneThroughInverseRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->newQuery()->joinRelation('user.supplier');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
    }

    public function testSimpleHasOneThroughInverseRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->useCustomBuilder()->newQuery()->joinRelation('user.supplier');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleHasManyThroughRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testSimpleHasManyThroughRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleHasManyThroughInverseRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('user.country');

        $this->assertEquals('select * from "posts" inner join "users" on "users"."id" = "posts"."user_id" inner join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
    }

    public function testSimpleHasManyThroughInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('user.country');

        $this->assertEquals('select * from "posts" inner join "users" on "users"."id" = "posts"."user_id" inner join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleMorphOneRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testSimpleMorphOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleMorphManyRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testSimpleMorphManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleMorphToManyRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testSimpleMorphToManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testSimpleMorphedByManyRelationJoin()
    {
        $builder = (new EloquentTagModelStub)->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testSimpleMorphedByManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentTagModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testOnMacro()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('phone', function($join) {
            $join->on('phones.extra', '=', 'users.extra');
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."extra" = "users"."extra"', $builder->toSql());
    }

    public function testOnMacroWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('phone', function($join) {
            $join->on('phones.extra', '=', 'users.extra');
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."extra" = "users"."extra"', $builder->toSql());
    }

    public function testMorphToRelationJoinThrowsException()
    {
        $this->expectException(RuntimeException::class);

        $builder = (new EloquentImageModelStub)->newQuery()->joinRelation('imageable');
    }

    public function testMorphToRelationJoinThrowsExceptionWithCustomBuilder()
    {
        $this->expectException(RuntimeException::class);

        $builder = (new EloquentImageModelStub)->useCustomBuilder()->newQuery()->joinRelation('imageable');
    }

    public function testMorphOneAsHasOneRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('postImage');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testMorphOneAsHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('postImage');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMorphToAsBelongsToRelationJoin()
    {
        $builder = (new EloquentImageModelStub)->newQuery()->joinRelation('postImageable');

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testMorphToAsBelongsToRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentImageModelStub)->useCustomBuilder()->newQuery()->joinRelation('postImageable');

        $this->assertEquals('select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMorphManyAsHasManyRelationJoin()
    {
        $builder = (new EloquentVideoModelStub)->newQuery()->joinRelation('videoComments');

        $this->assertEquals('select * from "videos" inner join "comments" on "comments"."commentable_id" = "videos"."id" and "commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
    }

    public function testMorphManyAsHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentVideoModelStub)->useCustomBuilder()->newQuery()->joinRelation('videoComments');

        $this->assertEquals('select * from "videos" inner join "comments" on "comments"."commentable_id" = "videos"."id" and "commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMorphToManyAsBelongsToManyRelationJoin()
    {
        $builder = (new EloquentVideoModelStub)->newQuery()->joinRelation('videoTags');

        $this->assertEquals('select * from "videos" inner join "taggables" on "taggables"."taggable_id" = "videos"."id" inner join "tags" on "tags"."id" = "taggables"."tag_id" and "taggables"."taggable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
    }

    public function testMorphToManyAsBelongsToManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentVideoModelStub)->useCustomBuilder()->newQuery()->joinRelation('videoTags');

        $this->assertEquals('select * from "videos" inner join "taggables" on "taggables"."taggable_id" = "videos"."id" inner join "tags" on "tags"."id" = "taggables"."tag_id" and "taggables"."taggable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneUsingAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('phone as telephones');

        $this->assertEquals('select * from "users" inner join "phones" as "telephones" on "telephones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testHasOneUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('phone as telephones');

        $this->assertEquals('select * from "users" inner join "phones" as "telephones" on "telephones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneInverseUsingAliasRelationJoin()
    {
        $builder = (new EloquentPhoneModelStub)->newQuery()->joinRelation('user as contacts');

        $this->assertEquals('select * from "phones" inner join "users" as "contacts" on "contacts"."id" = "phones"."user_id"', $builder->toSql());
    }

    public function testHasOneInverseUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPhoneModelStub)->useCustomBuilder()->newQuery()->joinRelation('user as contacts');

        $this->assertEquals('select * from "phones" inner join "users" as "contacts" on "contacts"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyUsingAliasRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('comments as feedback');

        $this->assertEquals('select * from "posts" inner join "comments" as "feedback" on "feedback"."post_id" = "posts"."id"', $builder->toSql());
    }

    public function testHasManyUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('comments as feedback');

        $this->assertEquals('select * from "posts" inner join "comments" as "feedback" on "feedback"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyInverseUsingAliasRelationJoin()
    {
        $builder = (new EloquentCommentModelStub)->newQuery()->joinRelation('post as article');

        $this->assertEquals('select * from "comments" inner join "posts" as "article" on "article"."id" = "comments"."post_id"', $builder->toSql());
    }

    public function testHasManyInverseUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCommentModelStub)->useCustomBuilder()->newQuery()->joinRelation('post as article');

        $this->assertEquals('select * from "comments" inner join "posts" as "article" on "article"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToManyUsingFarAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('roles as positions');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "role_user"."role_id"', $builder->toSql());
    }

    public function testBelongsToManyUsingFarAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('roles as positions');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToManyUsingPivotAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('roles as users_roles,roles');

        $this->assertEquals('select * from "users" inner join "role_user" as "users_roles" on "users_roles"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "users_roles"."role_id"', $builder->toSql());
    }

    public function testBelongsToManyUsingPivotAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('roles as users_roles,roles');

        $this->assertEquals('select * from "users" inner join "role_user" as "users_roles" on "users_roles"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "users_roles"."role_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToManyUsingPivotAndFarAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('roles as position_user,positions');

        $this->assertEquals('select * from "users" inner join "role_user" as "position_user" on "position_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "position_user"."role_id"', $builder->toSql());
    }

    public function testBelongsToManyUsingPivotAndFarAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('roles as position_user,positions');

        $this->assertEquals('select * from "users" inner join "role_user" as "position_user" on "position_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "position_user"."role_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneThroughUsingFarAliasRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->newQuery()->joinRelation('userHistory as revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testHasOneThroughUsingFarAliasRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->useCustomBuilder()->newQuery()->joinRelation('userHistory as revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneThroughUsingThroughAliasRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->newQuery()->joinRelation('userHistory as workers,history');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "workers"."id"', $builder->toSql());
    }

    public function testHasOneThroughUsingThroughAliasRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->useCustomBuilder()->newQuery()->joinRelation('userHistory as workers,history');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "workers"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneThroughUsingThroughAndFarAliasRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->newQuery()->joinRelation('userHistory as workers,revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "workers"."id"', $builder->toSql());
    }

    public function testHasOneThroughUsingThroughAndFarAliasRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->useCustomBuilder()->newQuery()->joinRelation('userHistory as workers,revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "workers"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneThroughInverseUsingFarAliasRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->newQuery()->joinRelation('user.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "users"."supplier_id"', $builder->toSql());
    }

    public function testHasOneThroughInverseUsingFarAliasRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->useCustomBuilder()->newQuery()->joinRelation('user.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneThroughInverseUsingThroughAliasRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->newQuery()->joinRelation('user as workers.supplier');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "workers"."supplier_id"', $builder->toSql());
    }

    public function testHasOneThroughInverseUsingThroughAliasRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->useCustomBuilder()->newQuery()->joinRelation('user as workers.supplier');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "workers"."supplier_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasOneThroughInverseUsingThroughAndFarAliasRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->newQuery()->joinRelation('user as workers.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "workers"."supplier_id"', $builder->toSql());
    }

    public function testHasOneThroughInverseUsingThroughAndFarAliasRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->useCustomBuilder()->newQuery()->joinRelation('user as workers.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "workers"."supplier_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyThroughUsingFarAliasRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('posts as articles');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testHasManyThroughUsingFarAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts as articles');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyThroughUsingThroughAliasRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('posts as citizens,posts');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "citizens"."id"', $builder->toSql());
    }

    public function testHasManyThroughUsingThroughAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts as citizens,posts');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyThroughUsingThroughAndFarAliasRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('posts as citizens,articles');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id"', $builder->toSql());
    }

    public function testHasManyThroughUsingThroughAndFarAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts as citizens,articles');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMorphOneUsingAliasRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testMorphOneUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('image');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMorphManyUsingAliasRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testMorphManyUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMorphToManyUsingAliasRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testMorphToManyUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('tags');

        $this->assertEquals('select * from "posts" inner join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? inner join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMorphedByManyUsingAliasRelationJoin()
    {
        $builder = (new EloquentTagModelStub)->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testMorphedByManyUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentTagModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "tags" inner join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? inner join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentSoftDeletesHasOneRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
    }

    public function testParentSoftDeletesHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentSoftDeletesHasOneWithTrashedRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->joinRelation('phone')->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testParentSoftDeletesHasOneWithTrashedRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('phone')->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testChildSoftDeletesHasOneRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
    }

    public function testChildSoftDeletesHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testChildSoftDeletesHasOneWithTrashedRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testChildSoftDeletesHasOneWithTrashedRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentAndChildSoftDeletesHasOneRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
    }

    public function testParentAndChildSoftDeletesHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentAndChildSoftDeletesHasOneWithTrashedParentRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->joinRelation('softDeletingPhone')->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
    }

    public function testParentAndChildSoftDeletesHasOneWithTrashedParentRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingPhone')->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentAndChildSoftDeletesHasOneWithTrashedChildRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
    }

    public function testParentAndChildSoftDeletesHasOneWithTrashedChildRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentAndChildSoftDeletesHasOneWithTrashedRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->withTrashed()->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testParentAndChildSoftDeletesHasOneWithTrashedRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->withTrashed()->joinRelation('softDeletingPhone', function ($join) {
            $join->withTrashed();
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentSoftSimpleHasOneInverseRelationJoin()
    {
        $builder = (new EloquentPhoneModelStub)->newQuery()->joinRelation('softDeletingUser');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" and "users"."deleted_at" is null', $builder->toSql());
    }

    public function testParentSoftSimpleHasOneInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPhoneModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingUser');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" and "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testChildSoftSimpleHasOneInverseRelationJoin()
    {
        $builder = (new EloquentSoftDeletingPhoneModelStub)->newQuery()->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" where "phones"."deleted_at" is null', $builder->toSql());
    }

    public function testChildSoftSimpleHasOneInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingPhoneModelStub)->useCustomBuilder()->newQuery()->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" where "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentSoftDeletesHasManyThroughRelationJoin()
    {
        $builder = (new EloquentSoftDeletingCountryModelStub)->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" where "countries"."deleted_at" is null', $builder->toSql());
    }

    public function testParentSoftDeletesHasManyThroughRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" where "countries"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testThroughSoftDeletesHasManyThroughRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('postsThroughSoftDeletingUser');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id" and "users"."deleted_at" is null', $builder->toSql());
        }
    }

    public function testThroughSoftDeletesHasManyThroughRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('postsThroughSoftDeletingUser');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id" and "users"."deleted_at" is null', $builder->toSql());
        }

        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testChildSoftDeletesHasManyThroughRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('softDeletingPosts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null', $builder->toSql());
    }

    public function testChildSoftDeletesHasManyThroughRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingPosts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testParentSoftDeletesBelongsToManyRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" where "users"."deleted_at" is null', $builder->toSql());
    }

    public function testParentSoftDeletesBelongsToManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testChildSoftDeletesBelongsToManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('softDeletingRoles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" and "roles"."deleted_at" is null', $builder->toSql());
    }

    public function testChildSoftDeletesBelongsToManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('softDeletingRoles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" and "roles"."deleted_at" is null', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToSelfRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('manager');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "users"."manager_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
    }

    public function testBelongsToSelfRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('manager');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "users"."manager_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToSelfUsingAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('manager as managers');

        $this->assertEquals('select * from "users" inner join "users" as "managers" on "managers"."id" = "users"."manager_id"', $builder->toSql());
    }

    public function testBelongsToSelfUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('manager as managers');

        $this->assertEquals('select * from "users" inner join "users" as "managers" on "managers"."id" = "users"."manager_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManySelfRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('employees');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
    }

    public function testHasManySelfRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('employees');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManySelfUsingAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('employees as employees');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id"', $builder->toSql());
    }

    public function testHasManySelfUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('employees as employees');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyThroughSelfRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('employeePosts');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "self_alias_hash"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
    }

    public function testHasManyThroughSelfRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('employeePosts');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "self_alias_hash"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyThroughSelfUsingAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('employeePosts as employees,posts');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "employees"."id"', $builder->toSql());
    }

    public function testHasManyThroughSelfUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('employeePosts as employees,posts');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "employees"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyThroughSoftDeletingSelfUsingAliasRelationJoin()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->newQuery()->joinRelation('employeePosts as employees,posts');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" where "users"."deleted_at" is null', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" and "users"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        }
    }

    public function testHasManyThroughSoftDeletingSelfUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentSoftDeletingUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('employeePosts as employees,posts');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" where "users"."deleted_at" is null', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" and "users"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        }

        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManySelfThroughRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('departmentEmployees');

        $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."department_id" = "departments"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
    }

    public function testHasManySelfThroughRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('departmentEmployees');

        $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."department_id" = "departments"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManySelfThroughSoftDeletingUsingAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('employeesThroughSoftDeletingDepartment as employees');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id" and "departments"."deleted_at" is null', $builder->toSql());
        }
    }

    public function testHasManySelfThroughSoftDeletingUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('employeesThroughSoftDeletingDepartment as employees');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id" and "departments"."deleted_at" is null', $builder->toSql());
        }

        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToManySelfRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('messagedUsers');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "messages"."to_user_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
    }

    public function testBelongsToManySelfRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('messagedUsers');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "messages"."to_user_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToManySelfUsingAliasRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('messagedUsers as recipients');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "recipients" on "recipients"."id" = "messages"."to_user_id"', $builder->toSql());
    }

    public function testBelongsToManySelfUsingAliasRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('messagedUsers as recipients');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "recipients" on "recipients"."id" = "messages"."to_user_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testThroughJoinForHasManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->joinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testThroughJoinForHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->joinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasOneRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->leftJoinRelation('phone');

        $this->assertEquals('select * from "users" left join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testLeftHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('phone');

        $this->assertEquals('select * from "users" left join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasOneInverseRelationJoin()
    {
        $builder = (new EloquentPhoneModelStub)->newQuery()->leftJoinRelation('user');

        $this->assertEquals('select * from "phones" left join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
    }

    public function testLeftHasOneInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPhoneModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('user');

        $this->assertEquals('select * from "phones" left join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasManyRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->leftJoinRelation('comments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
    }

    public function testLeftHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('comments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasManyInverseRelationJoin()
    {
        $builder = (new EloquentCommentModelStub)->newQuery()->leftJoinRelation('post');

        $this->assertEquals('select * from "comments" left join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
    }

    public function testLeftHasManyInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCommentModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('post');

        $this->assertEquals('select * from "comments" left join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftBelongsToManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->leftJoinRelation('roles');

        $this->assertEquals('select * from "users" left join "role_user" on "role_user"."user_id" = "users"."id" left join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
    }

    public function testLeftBelongsToManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('roles');

        $this->assertEquals('select * from "users" left join "role_user" on "role_user"."user_id" = "users"."id" left join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftBelongsToManyInverseRelationJoin()
    {
        $builder = (new EloquentRoleModelStub)->newQuery()->leftJoinRelation('users');

        $this->assertEquals('select * from "roles" left join "role_user" on "role_user"."role_id" = "roles"."id" left join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
    }

    public function testLeftBelongsToManyInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentRoleModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('users');

        $this->assertEquals('select * from "roles" left join "role_user" on "role_user"."role_id" = "roles"."id" left join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasOneThroughRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->newQuery()->leftJoinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" left join "users" on "users"."supplier_id" = "suppliers"."id" left join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testLeftHasOneThroughRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentSupplierModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" left join "users" on "users"."supplier_id" = "suppliers"."id" left join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasOneThroughInverseRelationJoin()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->newQuery()->leftJoinRelation('user.supplier');

        $this->assertEquals('select * from "history" left join "users" on "users"."id" = "history"."user_id" left join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
    }

    public function testLeftHasOneThroughInverseRelationJoinWithCustomBuilder()
    {
        if(!class_exists(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class)) {
            return;
        }

        $builder = (new EloquentUserHistoryModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('user.supplier');

        $this->assertEquals('select * from "history" left join "users" on "users"."id" = "history"."user_id" left join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasManyThroughRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->leftJoinRelation('posts');

        $this->assertEquals('select * from "countries" left join "users" on "users"."country_id" = "countries"."id" left join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testLeftHasManyThroughRelationJoinWithCustmBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('posts');

        $this->assertEquals('select * from "countries" left join "users" on "users"."country_id" = "countries"."id" left join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftHasManyThroughInverseRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->leftJoinRelation('user.country');

        $this->assertEquals('select * from "posts" left join "users" on "users"."id" = "posts"."user_id" left join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
    }

    public function testLeftHasManyThroughInverseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('user.country');

        $this->assertEquals('select * from "posts" left join "users" on "users"."id" = "posts"."user_id" left join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftMorphOneRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->leftJoinRelation('image');

        $this->assertEquals('select * from "posts" left join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testLeftMorphOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('image');

        $this->assertEquals('select * from "posts" left join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftMorphManyRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->leftJoinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testLeftMorphManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('polymorphicComments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."commentable_id" = "posts"."id" and "comments"."commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftMorphToManyRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->leftJoinRelation('tags');

        $this->assertEquals('select * from "posts" left join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? left join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testLeftMorphToManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('tags');

        $this->assertEquals('select * from "posts" left join "taggables" on "taggables"."taggable_id" = "posts"."id" and "taggables"."taggable_type" = ? left join "tags" on "tags"."id" = "taggables"."tag_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftMorphedByManyRelationJoin()
    {
        $builder = (new EloquentTagModelStub)->newQuery()->leftJoinRelation('posts');

        $this->assertEquals('select * from "tags" left join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? left join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
    }

    public function testLeftMorphedByManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentTagModelStub)->useCustomBuilder()->newQuery()->leftJoinRelation('posts');

        $this->assertEquals('select * from "tags" left join "taggables" on "taggables"."tag_id" = "tags"."id" and "taggables"."taggable_type" = ? left join "posts" on "posts"."id" = "taggables"."taggable_id"', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testRightHasOneRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->rightJoinRelation('phone');

        $this->assertEquals('select * from "users" right join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testRightHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->rightJoinRelation('phone');

        $this->assertEquals('select * from "users" right join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testCrossHasOneRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->crossJoinRelation('phone');

        $this->assertEquals('select * from "users" cross join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
    }

    public function testCrossHasOneRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->crossJoinRelation('phone');

        $this->assertEquals('select * from "users" cross join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testLeftThroughJoinForHasManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->leftJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? left join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testLeftThroughJoinForHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->leftJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? left join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testRightThroughJoinForHasManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->rightJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? right join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testRightThroughJoinForHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->rightJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? right join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testCrossThroughJoinForHasManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->crossJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? cross join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testCrossThroughJoinForHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts', function ($join) {
            $join->where('posts.is_active', '=', 1);
        })->crossJoinThroughRelation('posts.comments', function ($join) {
            $join->whereColumn('comments.created_by_id', '=', 'users.id');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? cross join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMultipleAliasesForBelongsToRelationJoin()
    {
        $builder = (new EloquentPostModelStub)->newQuery()->joinRelation('user as authors.country as nations');

        $this->assertEquals('select * from "posts" inner join "users" as "authors" on "authors"."id" = "posts"."user_id" inner join "countries" as "nations" on "nations"."id" = "authors"."country_id"', $builder->toSql());
    }

    public function testMultipleAliasesForBelongsToRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentPostModelStub)->useCustomBuilder()->newQuery()->joinRelation('user as authors.country as nations');

        $this->assertEquals('select * from "posts" inner join "users" as "authors" on "authors"."id" = "posts"."user_id" inner join "countries" as "nations" on "nations"."id" = "authors"."country_id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMultipleAliasesForHasManyRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('posts as articles.comments as reviews');

        $this->assertEquals('select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id" inner join "comments" as "reviews" on "reviews"."post_id" = "articles"."id"', $builder->toSql());
    }

    public function testMultipleAliasesForHasManyRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts as articles.comments as reviews');

        $this->assertEquals('select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id" inner join "comments" as "reviews" on "reviews"."post_id" = "articles"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testMultipleAliasesForHasManyThroughRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('posts as citizens,articles.likes as feedback,favorites');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id" inner join "comments" as "feedback" on "feedback"."post_id" = "articles"."id" inner join "likes" as "favorites" on "favorites"."comment_id" = "feedback"."id"', $builder->toSql());
    }

    public function testMultipleAliasesForHasManyThroughRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts as citizens,articles.likes as feedback,favorites');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id" inner join "comments" as "feedback" on "feedback"."post_id" = "articles"."id" inner join "likes" as "favorites" on "favorites"."comment_id" = "feedback"."id"', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasManyUsingLocalScopeRelationJoin()
    {
        $builder = (new EloquentCountryModelStub)->newQuery()->joinRelation('users', function ($join) {
            $join->active();
        });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "active" = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testHasManyUsingLocalScopeRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentCountryModelStub)->useCustomBuilder()->newQuery()->joinRelation('users', function ($join) {
            $join->active();
        });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "active" = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToWithNestedClauseRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('supplier', function($join) {
            $join->where(function($join) {
                $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                $join->orWhere('supplier.has_international_restrictions', 1);
            });
        });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or "supplier"."has_international_restrictions" = ?)', $builder->toSql());
    }

    public function testBelongsToWithNestedClauseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('supplier', function($join) {
            $join->where(function($join) {
                $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                $join->orWhere('supplier.has_international_restrictions', 1);
            });
        });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or "supplier"."has_international_restrictions" = ?)', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testBelongsToWithRecursiveNestedClauseRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('supplier', function($join) {
            $join->where(function($join) {
                $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                $join->orWhere(function($join) {
                    $join->where('supplier.has_international_restrictions', 1);
                    $join->where('supplier.country', '!=', 'US');
                });
            });
        });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or ("supplier"."has_international_restrictions" = ? and "supplier"."country" != ?))', $builder->toSql());
    }

    public function testBelongsToWithRecursiveNestedClauseRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('supplier', function($join) {
            $join->where(function($join) {
                $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                $join->orWhere(function($join) {
                    $join->where('supplier.has_international_restrictions', 1);
                    $join->where('supplier.country', '!=', 'US');
                });
            });
        });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or ("supplier"."has_international_restrictions" = ? and "supplier"."country" != ?))', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testHasRelationWithinRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('posts', function($join) {
            $join->has('comments');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
    }

    public function testHasRelationWithinRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts', function($join) {
            $join->has('comments');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
    }

    public function testDoesntHaveRelationWithinRelationJoin()
    {
        $builder = (new EloquentUserModelStub)->newQuery()->joinRelation('posts', function($join) {
            $join->doesntHave('comments');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and not exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
    }

    public function testDoesntHaveRelationWithinRelationJoinWithCustomBuilder()
    {
        $builder = (new EloquentUserModelStub)->useCustomBuilder()->newQuery()->joinRelation('posts', function($join) {
            $join->doesntHave('comments');
        });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and not exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
        $this->assertEquals(CustomBuilder::class, get_class($builder));
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
