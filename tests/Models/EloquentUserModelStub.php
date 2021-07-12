<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

use Reedware\LaravelRelationJoins\Tests\CustomRelation;

class EloquentUserModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'users';

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function customRelation()
    {
        return new CustomRelation($this->newQuery(), $this);
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

    public function rolesUsingPivotModel()
    {
        return $this->belongsToMany(EloquentRoleModelStub::class, 'role_user', 'user_id', 'role_id')
            ->using(EloquentRoleUserPivotStub::class);
    }

    public function rolesUsingSoftDeletingPivotModel()
    {
        return $this->belongsToMany(EloquentRoleModelStub::class, 'role_user', 'user_id', 'role_id')
            ->using(EloquentSoftDeletingRoleUserPivotStub::class);
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

    public function uploadedFiles()
    {
        return $this->hasMany(EloquentFileModelStub::class, 'uploaded_by_id');
    }

    public function uploadedImages()
    {
        return $this->hasMany(EloquentImageModelStub::class, 'uploaded_by_id');
    }
}
