<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relation to users
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // Relation to permissions
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    // Helper method to check if a role has a permission
    public function hasPermission($permission)
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
}
