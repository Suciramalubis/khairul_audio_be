<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',      // <-- Tambahan
        'name',
        'email',
        'password',
        'phone', 
        'gender',        // <-- Tambahan
        'birth_date',    // <-- Tambahan
        'bio',
        'role',
        'otp',               
        'otp_expires_at',    
        'email_verified_at', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp', 
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at'    => 'datetime', 
            'birth_date'        => 'date', // Cast ke tipe date
            'password'          => 'hashed',
        ];
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}