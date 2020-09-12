# Laravel Relation Joins

[![Latest Stable Version](https://poser.pugx.org/reedware/laravel-relation-joins/v/stable)](https://packagist.org/packages/reedware/laravel-relation-joins)
[![Total Downloads](https://poser.pugx.org/reedware/laravel-relation-joins/downloads)](https://packagist.org/packages/reedware/laravel-relation-joins)
[![Laravel Version](https://img.shields.io/badge/Laravel-5.5%2B-blue)](https://laravel.com/)
[![Laravel Version](https://img.shields.io/badge/Laravel-6.x%2B-blue)](https://laravel.com/)
[![Build Status](https://travis-ci.com/tylernathanreed/laravel-relation-joins.svg?branch=master)](https://travis-ci.com/tylernathanreed/laravel-relation-joins)

This package adds the ability to join on a relationship by name.

## Introduction

Eloquent doesn't offer any tools for joining, so we've been stuck with the base query builder joins. While Eloquent does have the "has" concept for existence, there are still times where you want to return information about the related entities, or aggregate information together.

I've seen other packages out there that try to accompish a goal similar to this one. I tried to get on board with at least one of them, but they all fell short for a number of reasons. Let me first explain the features of this package, and you might see why this one is better (at least what for what I intend to use it for).

## Installation

#### Using Composer

```
composer require reedware/laravel-relation-joins
```

#### Versioning

This package was built with the latest version of Laravel in mind, but support goes back to Laravel 5.5.

## Usage

### 1. Performing a join via relationship

This is the entire point of this package, so here's a basic example:

```php
User::query()->joinRelation('posts');
```

This will apply a join from the `User` model through the `posts` relation, leveraging any query scopes (such as soft deletes) automatically.

You can perform joins over all relationship types (except MorphTo, which "has" doesn't support either), including the new "HasOneThrough" relationship. Additionally, you can perform the other types of joins, using a syntax similar to the base query builder:

```php
User::query()->leftJoinRelation('posts');
User::query()->rightJoinRelation('posts');
User::query()->crossJoinRelation('posts');
```

### 2. Joining to nested relationships

One of the shining abilities of being able to join through relationships shows up when you have to navigate through a nested web of relationships. When trying to join on a relation through another relation, you can use the "dot" syntax, similar to how the "has" and "with" concepts work:

```php
User::query()->joinRelation('posts.comments');
```

### 3. Adding join constraints

This is honestly where I felt a lot of the existing solutions were lacking. They either created custom "where" clauses, or limited the query to only supporting certain types of "where" clauses. With this package, there are no known restrictions, and the means of adding the constraints is very intuitive:

```php
User::query()->joinRelation('posts', function ($join) {
    $join->where('posts.created_at', '>=', '2019-01-01');
});
```

This will tack on the specific constraints to the already provided relationship constraints, making this really easy to use. Here are a few more examples:

```php
// Disabling soft deletes for only the "Post" model
User::query()->joinRelation('posts', function ($join) {
    $join->withTrashed();
});
```

```php
// Using a query scope on the "Post" model
User::query()->joinRelation('posts', function ($join) {
    $join->active();
});
```

### 4. Joining through relationships

There are times where you want to tack on clauses for intermediate joins. This can get a bit tricky in some other packages (by trying to automatically deduce whether or not to apply a join, or by not handling this situation at all).

This package introduces something I'm calling a "through" join. Essentially, a "through" join indicates "I want to apply only the final relation in the 'dot' notation to my query".

Here's an example:

```php
// Using a query scope on the "Post" model
User::query()->joinRelation('posts', function ($join) {
    $join->where('is_active', '=', 1);
})->joinThroughRelation('posts.comments', function ($join) {
    $join->where('comments.title', 'like', '%looking for something%');
});
```

The second part, `joinThroughRelation`, will only apply the `comments` relation join, but it will do so as if it came from the `Post` model.

### 5. Joining on circular relationships

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

### 6. Aliasing joins

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

### 7. Everything else

Everything else you would need for joins: aggregates, grouping, ordering, selecting, etc. all go through the already established query builder, where none of that was changed. Meaning I can easily do something like this:

```php
User::query()->joinRelation('licenses')->groupBy('users.id')->orderBy('users.id')->select('users.id')->selectRaw('sum(licenses.price) as revenue');
```

Personally, I see myself using this a ton in Laravel Nova (specifically lenses), but I've been needing queries like this for years in countless scenarios.

Joins are something that nearly every developer will eventually use, so having Eloquent natively support joining over relations would be fantastic. However, since that doesn't come out of the box, you'll have to install this package instead. My goal with this package is to mirror the Laravel "feel" of coding, where complex implementations (such as joining over named relations) is simple to use and easy to understand.
