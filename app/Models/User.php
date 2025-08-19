<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is currently online
     */
    public function isOnline(): bool
    {
        $userActivityService = app(\App\Services\UserActivityService::class);
        return $userActivityService->isUserOnline($this->id);
    }

    /**
     * Get user's last activity
     */
    public function getLastActivity(): ?array
    {
        $userActivityService = app(\App\Services\UserActivityService::class);
        return $userActivityService->getUserActivity($this->id);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive users
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for online users
     */
    public function scopeOnline($query)
    {
        $userActivityService = app(\App\Services\UserActivityService::class);
        $onlineUserIds = $userActivityService->getOnlineUsers();
        
        return $query->whereIn('id', $onlineUserIds);
    }
}
