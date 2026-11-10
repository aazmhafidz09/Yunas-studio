<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'is_active',
        'role_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        //
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isKasir()
    {
        return $this->role_id === 2;
    }

    public function isAdmin()
    {
        return $this->role_id === 1;
    }

    public function isSuperAdmin()
    {
        return $this->role_id === 1; // Assuming admin is also super admin, adjust if needed
    }
    
    public function isUser(){
        return $this->role_id === 3;
    }

    /**
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }
}
