<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserActivityService
{
    private const ONLINE_USERS_KEY = 'online_users';
    private const USER_ACTIVITY_PREFIX = 'user_activity:';
    private const ONLINE_THRESHOLD = 300; // 5 minutes

    /**
     * Mark a user as online
     */
    public function markUserOnline(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return;
        }

        $timestamp = now()->timestamp;
        
        // Add user to online users set with score as timestamp
        Redis::zadd(self::ONLINE_USERS_KEY, $timestamp, $userId);
        
        // Store user activity details
        Redis::hmset(self::USER_ACTIVITY_PREFIX . $userId, [
            'last_seen' => $timestamp,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        // Set expiration for user activity data
        Redis::expire(self::USER_ACTIVITY_PREFIX . $userId, self::ONLINE_THRESHOLD * 2);
        
        // Clean up offline users
        $this->cleanupOfflineUsers();
    }

    /**
     * Get all online users
     */
    public function getOnlineUsers(): array
    {
        $cutoff = now()->subSeconds(self::ONLINE_THRESHOLD)->timestamp;
        
        // Remove users who haven't been active recently
        Redis::zremrangebyscore(self::ONLINE_USERS_KEY, 0, $cutoff);
        
        // Get all online user IDs
        $userIds = Redis::zrevrange(self::ONLINE_USERS_KEY, 0, -1);
        
        return array_map('intval', $userIds);
    }

    /**
     * Get online users count
     */
    public function getOnlineUsersCount(): int
    {
        return count($this->getOnlineUsers());
    }

    /**
     * Check if a specific user is online
     */
    public function isUserOnline(int $userId): bool
    {
        $cutoff = now()->subSeconds(self::ONLINE_THRESHOLD)->timestamp;
        $lastSeen = Redis::zscore(self::ONLINE_USERS_KEY, $userId);
        
        return $lastSeen && $lastSeen >= $cutoff;
    }

    /**
     * Get user's last activity
     */
    public function getUserActivity(int $userId): ?array
    {
        $activity = Redis::hmget(self::USER_ACTIVITY_PREFIX . $userId, [
            'last_seen', 'ip_address', 'user_agent'
        ]);
        
        // Redis hmget returns array with numeric indices, so we need to map them
        $activityData = [
            'last_seen' => $activity[0] ?? null,
            'ip_address' => $activity[1] ?? null,
            'user_agent' => $activity[2] ?? null,
        ];
        
        if (!$activityData['last_seen']) {
            return null;
        }
        
        return [
            'last_seen' => Carbon::createFromTimestamp($activityData['last_seen']),
            'ip_address' => $activityData['ip_address'],
            'user_agent' => $activityData['user_agent'],
            'is_online' => $this->isUserOnline($userId),
        ];
    }

    /**
     * Clean up offline users
     */
    private function cleanupOfflineUsers(): void
    {
        $cutoff = now()->subSeconds(self::ONLINE_THRESHOLD)->timestamp;
        Redis::zremrangebyscore(self::ONLINE_USERS_KEY, 0, $cutoff);
    }

    /**
     * Get online users with details
     */
    public function getOnlineUsersWithDetails(): array
    {
        $userIds = $this->getOnlineUsers();
        $users = [];
        
        foreach ($userIds as $userId) {
            $activity = $this->getUserActivity($userId);
            if ($activity) {
                $users[$userId] = $activity;
            }
        }
        
        return $users;
    }
}
