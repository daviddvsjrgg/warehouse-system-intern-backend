<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Relation to roles
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // Check if the user has a specific role
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    // Check if the user has any of the provided roles
    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    // Check if the user has a specific permission
    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true; // Return true if any role has the permission
            }
        }
        return false; // Return false if no role has the permission
    }

    // Check if the user has any permission from a list
    public function hasAnyPermission($permissions)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions()->whereIn('name', $permissions)->exists()) {
                return true; // Return true if any role has any of the permissions
            }
        }
        return false; // Return false if no role has the permissions
    }
}
