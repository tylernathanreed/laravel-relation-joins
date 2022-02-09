# Laravel Relation Joins

[![Laravel Version](https://img.shields.io/badge/Laravel-7.x%2F8.x%2F9.x-blue)](https://laravel.com/)
[![Build Status](https://github.com/tylernathanreed/laravel-relation-joins/workflows/tests/badge.svg)](https://github.com/tylernathanreed/laravel-relation-joins/actions)
[![Style Status](https://github.com/tylernathanreed/laravel-relation-joins/workflows/style/badge.svg)](https://github.com/tylernathanreed/laravel-relation-joins/actions)
[![Coverage Status](https://coveralls.io/repos/github/tylernathanreed/laravel-relation-joins/badge.svg?branch=master)](https://coveralls.io/github/tylernathanreed/laravel-relation-joins?branch=master)
[![Latest Stable Version](https://poser.pugx.org/reedware/laravel-relation-joins/v/stable)](https://packagist.org/packages/reedware/laravel-relation-joins)
[![Total Downloads](https://poser.pugx.org/reedware/laravel-relation-joins/downloads)](https://packagist.org/packages/reedware/laravel-relation-joins)

This package adds the ability to join on a relationship by name.

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
    - [Versioning](#versioning)
- [Usage](#usage)
    - [1. Performing a join via relationship](#joining-basic)
    - [2. Joining to nested relationships](#joining-nested)
    - [3. Adding join constraints](#joining-constraints)
        - [Query Scopes](#joining-constraints-scopes)
        - [Soft Deletes](#joining-constraints-soft-deletes)
    - [4. Adding pivot constraints](#joining-constraints-pivot)
        - [Query Scopes](#joining-constraints-pivot-scopes)
        - [Soft Deletes](#joining-constraints-pivot-soft-deletes)
    - [5. Adding multiple constraints](#multiple-constraints)
        - [Array-Syntax](#multiple-constraints-array)
        - [Through-Syntax](#multiple-constraints-through)
    - [6. Joining on circular relationships](#joining-circular)
    - [7. Aliasing joins](#joining-aliasing)
        - [Aliasing Pivot Tables](#joining-aliasing-pivot)
    - [8. Morph To Relations](#joining-morph-to)
        - [Nested Relationships](#joining-morph-to-nested)
    - [9. Anonymous Relations](#joining-anonymous)
    - [10. Everything else](#joining-miscellaneous)

<a name="introduction"></a>
## Introduction

This package makes joining a breeze by leveraging the relationships you have already defined.

Eloquent doesn't offer any tools for joining, so we've been stuck with the base query builder joins. While Eloquent does have the "has" concept for existence, there are still times where you want to return information about the related entities, or aggregate information together.

Aside from relationships themselves, Eloquent's omission of relationship joins means that you can't leverage several powerful features of Eloquent, such as model scopes and soft deletes. This package aims to correct all of that.

I've seen other packages out there that try to accompish a goal similar to this one. I tried to get on board with at least one of them, but they all fell short for a number of reasons. Let me first explain the features of this package, and you might see why this one is better (at least what for what I intend to use it for).

<a name="installation"></a>
## Installation

Install this package using Composer:

```
composer require reedware/laravel-relation-joins
```

This package leverages auto-discovery for its service provider. If you have auto discovery disabled for this package, you'll need to manually register the service provider:
```
Reedware\LaravelRelationJoins\LaravelRelationJoinServiceProvider::class
```

<a name="versioning"></a>
### Versioning

This package was built with the latest version of Laravel in mind, but support goes back to Laravel 7.x.

For Laravel 6.x, use version 2.x of this package.  
For Laravel 5.5, use version 1.x of this package.

<a name="usage"></a>
## Usage

<a name="joining-basic"></a>
### 1. Performing a join via relationship

This is the entire point of this package, so here's a basic example:

```php
User::query()->joinRelation('posts');
```

This will apply a join from the `User` model through the `posts` relation, leveraging any query scopes (such as soft deletes) automatically.

You can perform joins over all relationship types, including polymorphic relationships. Additionally, you can perform the other types of joins, using a syntax similar to the base query builder:

```php
User::query()->leftJoinRelation('posts');
User::query()->rightJoinRelation('posts');
User::query()->crossJoinRelation('posts');
```

<a name="joining-nested"></a>
### 2. Joining to nested relationships

One of the shining abilities of being able to join through relationships shows up when you have to navigate through a nested web of relationships. When trying to join on a relation through another relation, you can use the "dot" syntax, similar to how the "has" and "with" concepts work:

```php
User::query()->joinRelation('posts.comments');
```

<a name="joining-constraints"></a>
### 3. Adding join constraints

This is honestly where I felt a lot of the existing solutions were lacking. They either created custom "where" clauses, or limited the query to only supporting certain types of "where" clauses. With this package, there are no known restrictions, and the means of adding the constraints is very intuitive:

```php
User::query()->joinRelation('posts', function ($join) {
    $join->where('posts.created_at', '>=', '2019-01-01');
});
```

This will tack on the specific constraints to the already provided relationship constraints, making this really easy to use.

<a name="joining-constraints-scopes"></a>
#### Query Scopes

One of the most powerful features offered by this package is the ability to leverage query scopes within joins. Calling a query scope on the `$join` parameter is essentially the same as calling it on the related model.

```php
// Using the "active" query scope on the "Post" model
User::query()->joinRelation('posts', function ($join) {
    $join->active();
});
```

<a name="joining-constraints-soft-deletes"></a>
#### Soft Deletes

It can be frustrating to respecify soft deletes in all of your joins, when the model itself already knows how to do this. When using relation joins, soft deletes are automatically handled! Additionally, you can still leverage the query scopes that ship with soft deletes:

```php
// Disabling soft deletes for only the "Post" model
User::query()->joinRelation('posts', function ($join) {
    $join->withTrashed();
});
```

<a name="joining-constraints-pivot"></a>
### 4. Adding pivot constraints

Constraints aren't limited to just the join table itself. Certain relationships require multiple joins, which introduces additional tables. You can still apply constraints on these joins directly. To be clear, this is intended for "Has One/Many Through" and "Belongs/Morph to Many" relations.

```php
// Adding pivot ("role_user") constraints for a "Belongs to Many" relation
User::query()->joinRelation('roles', function ($join, $pivot) {
    $pivot->where('domain', '=', 'web');
});
```

```php
// Adding pivot ("users") constraints for a "Has Many Through" relation
Country::query()->joinRelation('posts', function ($join, $through) {
    $through->where('is_admin', '=', true);
});
```

This will tack on the specific constraints to the intermediate table, as well as any constraints you provide to the far (`$join`) table.

<a name="joining-constraints-pivot-scopes"></a>
#### Query Scopes

When the intermediate table is represented by a model, you can leverage query scopes for that model as well. This is default behavior for the "Has One/Many Through" relations. For the "Belongs/Morph To Many" relations, you'll need to leverage the `->using(Model::class)` method to obtain this benefit.

```php
// Using a query scope for the intermediate "RoleUser" pivot in a "Belongs to Many" relation
User::query()->joinRelation('roles', function ($join, $pivot) {
    $pivot->web();
});
```

<a name="joining-constraints-pivot-soft-deletes"></a>
#### Soft Deletes

Similar to regular join constraints, soft deletes on the pivot are automatically accounted for. Additionally, you can still leverage the query scopes that ship with soft deletes:

```php
// Disabling soft deletes for the intermediate "User" model
Country::query()->joinRelation('posts', function ($join, $through) {
    $through->withTrashed();
});
```

When using a "Belongs/Morph to Many" relationship, a pivot model must be specified for soft deletes to be considered.

<a name="multiple-constraints"></a>
### 5. Adding multiple constraints

There are times where you want to tack on clauses for intermediate joins. This can get a bit tricky in some other packages (by trying to automatically deduce whether or not to apply a join, or by not handling this situation at all). This package introduces two solutions, where both have value in different situations.

<a name="multiple-constraints-array"></a>
#### Array-Syntax

The first approach to handling multiple constraints is using an array syntax. This approach allows you to define all of your nested joins and constraints together:

```php
User::query()->joinRelation('posts.comments', [
    function ($join) { $join->where('is_active', '=', 1); },
    function ($join) { $join->where('comments.title', 'like', '%looking for something%'); }
});
```

The array syntax supports both sequential and associative variants:

```php
// Sequential
User::query()->joinRelation('posts.comments', [
    null,
    function ($join) { $join->where('comments.title', 'like', '%looking for something%'); }
});

// Associative
User::query()->joinRelation('posts.comments', [
    'comments' => function ($join) { $join->where('comments.title', 'like', '%looking for something%'); }
});
```

If you're using aliases, the associate array syntax refers to the fully qualified relation:
```php
User::query()->joinRelation('posts as articles.comments as threads', [
    'posts as articles' => function ($join) { $join->where('is_active', '=', 1); },
    'comments as threads' => function ($join) { $join->where('threads.title', 'like', '%looking for something%'); }
});
```

The join type can also be mixed:
```php
User::query()->joinRelation('posts.comments', [
    'comments' => function ($join) { $join->type = 'left'; }
});
```

<a name="multiple-constraints-through"></a>
#### Through-Syntax

The second approach to handling multiple constraints is using a through syntax. This approach allows us to define your joins and constraints individually:

```php
User::query()->joinRelation('posts', function ($join) {
    $join->where('is_active', '=', 1);
})->joinThroughRelation('posts.comments', function ($join) {
    $join->where('comments.title', 'like', '%looking for something%');
});
```

The "through" concept here allows you to define a nested join using "dot" syntax, where only the final relation is actually constrained, and the prior relations are assumed to already be handled. So in this case, the `joinThroughRelation` method will only apply the `comments` relation join, but it will do so as if it came from the `Post` model.

<a name="joining-circular"></a>
### 6. Joining on circular relationships

This package also supports joining on circular relations, and handles it the same way the "has" concept does:

```php
public function employees()
{
    return $this->hasMany(static::class, 'manager_id', 'id');
}

User::query()->joinRelation('employees');

// SQL: select * from "users" inner join "users" as "laravel_reserved_0" on "laravel_reserved_0"."manager_id" = "users"."id"
```

Now clearly, if you're wanting to apply constraints on the `employees` relation, having this sort of naming convention isn't desirable. This brings me to the next feature:

<a name="joining-aliasing"></a>
### 7. Aliasing joins

You could alias the above example like so:

```php
User::query()->joinRelation('employees as employees');

// SQL: select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id"
```

The join doesn't have to be circular to support aliasing. Here's an example:

```php
User::query()->joinRelation('posts as articles');

// SQL: select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id"
```

This also works for nested relations:

```php
User::query()->joinRelation('posts as articles.comments as feedback');

// SQL: select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id" inner join "comments" as "feedback" on "feedback"."post_id" = "articles"."id"
```

<a name="joining-aliasing-pivot"></a>
#### Aliasing Pivot Tables
For relations that require multiple tables (i.e. BelongsToMany, HasManyThrough, etc.), the alias will apply to the far/non-pivot table. If you need to alias the pivot/through table, you can use a double-alias:

```php
public function roles()
{
    return $this->belongsToMany(EloquentRoleModelStub::class, 'role_user', 'user_id', 'role_id');
}

User::query()->joinRelation('roles as users_roles,roles');
// SQL: select * from "users" inner join "role_user" as "users_roles" on "users_roles"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "users_roles"."role_id"

User::query()->joinRelation('roles as users_roles,positions');
// SQL: select * from "users" inner join "role_user" as "position_user" on "position_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "position_user"."role_id"
```

<a name="joining-morph-to"></a>
### 8. Morph To Relations

The `MorphTo` relation has the quirk of not knowing which table that needs to be joined into, as there could be several. Since only one table is supported, you'll have to provide which morph type you want to use:

```php
Image::query()->joinMorphRelation('imageable', Post::class);
// SQL: select * from "images" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?
```

As before, other join types are also supported:

```php
Image::query()->leftJoinMorphRelation('imageable', Post::class);
Image::query()->rightJoinMorphRelation('imageable', Post::class);
Image::query()->crossJoinMorphRelation('imageable', Post::class);
```

After the morph type has been specified, the traditional parameters follow:

```php
// Constraints
Image::query()->joinMorphRelation('imageable', Post::class, function ($join) {
    $join->where('posts.created_at', '>=', '2019-01-01');
});

// Query Scopes
Image::query()->joinMorphRelation('imageable', Post::class, function ($join) {
    $join->active();
});

// Disabling soft deletes
Image::query()->joinMorphRelation('imageable', Post::class, function ($join) {
    $join->withTrashed();
});
```

<a name="joining-morph-to-nested"></a>
#### Nested Relationships

When previously covering the `MorphTo` relation, the relation itself was singularly called out. However, in a nested scenario, the `MorphTo` relation could be anywhere. Fortunately, this still doesn't change the syntax:

```php
User::query()->joinMorphRelation('uploadedImages.imageable', Post::class);
// SQL: select * from "users" inner join "images" on "images.uploaded_by_id" = "users.id" inner join "posts" on "posts"."id" = "images"."imageable_id" and "images"."imageable_type" = ?
```

Since multiple relationships could be specified, multiple `MorphTo` relations could be in play. When this happens, you'll need to provide the morph type for each relation:

```php
User::query()->joinMorphRelation('uploadedFiles.link.imageable', [Image::class, Post::class]);
// SQL: select * from "users" inner join "files" on "files"."uploaded_by_id" = "users"."id" inner join "images" on "images"."id" = "files"."link_id" and "files"."link_type" = ? inner join "users" on "users"."id" = "images"."imageable_id" and "images"."imageable_type" = ?
```

In the scenario above, the morph types are used for the `MorphTo` relations in the order they appear.

<a name="joining-anonymous"></a>
### 9. Anonymous Joins

In rare circumstances, you may find yourself in a situation where you don't want to define a relationship on a model, but you still want to join on it as if it existed. You can do this by passing in the relationship itself:

```php
$relation = Relation::noConstraints(function () {
    return (new User)
        ->belongsTo(Country::class, 'country_name', 'name');
});

User::query()->joinRelation($relation);
// SQL: select * from "users" inner join "countries" on "countries"."name" = "users"."country_name"
```

#### Aliasing Anonymous Joins

Since the relation is no longer a string, you instead have to provide your alias as an array:

```php
$relation = Relation::noConstraints(function () {
    return (new User)
        ->belongsTo(Country::class, 'kingdom_name', 'name');
});

User::query()->joinRelation([$relation, 'kingdoms');
// SQL: select * from "users" inner join "countries" as "kingdoms" on "kingdoms"."name" = "users"."kingdom_name"
```

<a name="joining-miscellaneous"></a>
### 10. Everything else

Everything else you would need for joins: aggregates, grouping, ordering, selecting, etc. all go through the already established query builder, where none of that was changed. Meaning you could easily do something like this:

```php
User::query()->joinRelation('licenses')->groupBy('users.id')->orderBy('users.id')->select('users.id')->selectRaw('sum(licenses.price) as revenue');
```

Personally, I see myself using this a ton in Laravel Nova (specifically lenses), but I've been needing queries like this for years in countless scenarios.

Joins are something that nearly every developer will eventually use, so having Eloquent natively support joining over relations would be fantastic. However, since that doesn't come out of the box, you'll have to install this package instead. My goal with this package is to mirror the Laravel "feel" of coding, where complex implementations (such as joining over named relations) is simple to use and easy to understand.
