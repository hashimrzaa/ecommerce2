<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone', 'role', 'city', 'address', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isStaff(): bool
    {
        return $this->role !== 'customer' && $this->is_active;
    }

    public function getRoleLabelAttribute(): string
    {
        return config('store.roles')[$this->role] ?? 'Customer';
    }

    /** Admin sections this user may open. Owners always get everything. */
    public function sections(): array
    {
        if (! $this->isStaff()) {
            return [];
        }
        if ($this->role === 'owner') {
            return array_keys(config('store.sections'));
        }

        return once(fn () => RolePermission::where('role', $this->role)->pluck('section')->all());
    }

    public function canAccess(string $section): bool
    {
        return in_array($section, $this->sections(), true);
    }
}
