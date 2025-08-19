<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AdminCacheService
{
    private const CACHE_PREFIX = 'admin:';
    private const DEFAULT_TTL = 3600;

    /**
     * Cache user statistics
     */
    public function cacheUserStats(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'user_stats', self::DEFAULT_TTL, function () {
            return [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'inactive_users' => User::where('status', 'inactive')->count(),
                'today_registrations' => User::whereDate('created_at', today())->count(),
                'this_month_registrations' => User::whereMonth('created_at', now()->month)->count(),
            ];
        });
    }

    /**
     * Cache and retrieve recent user activities
     */
    public function cacheRecentActivities(int $limit = 50): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'recent_activities', 300, function () use ($limit) {
            $userActivityService = app(UserActivityService::class);
            $onlineUsers = $userActivityService->getOnlineUsersWithDetails();
            
            // Get user details
            $userIds = array_keys($onlineUsers);
            $users = User::whereIn('id', $userIds)
                ->select('id', 'name', 'email', 'created_at')
                ->get()
                ->keyBy('id');

            $activities = [];
            foreach ($onlineUsers as $userId => $activity) {
                if (isset($users[$userId])) {
                    $activities[] = [
                        'user' => $users[$userId],
                        'activity' => $activity,
                    ];
                }
            }

            return array_slice($activities, 0, $limit);
        });
    }

    /**
     * Store admin configuration in Redis
     */
    public function setAdminConfig(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        Cache::put(self::CACHE_PREFIX . 'config:' . $key, $value, $ttl);
    }

    /**
     * Get admin configuration from Redis
     */
    public function getAdminConfig(string $key, mixed $default = null): mixed
    {
        return Cache::get(self::CACHE_PREFIX . 'config:' . $key, $default);
    }

    /**
     * Clear specific cache
     */
    public function clearCache(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Clear all admin caches
     */
    public function clearAllAdminCache(): void
    {
        $pattern = config('cache.prefix') . ':' . self::CACHE_PREFIX . '*';
        
        // Get all Redis connections
        $redis = Redis::connection();
        $keys = $redis->keys($pattern);
        
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }

    /**
     * Store frequently accessed data with automatic refresh
     */
    public function rememberId($key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        return Cache::remember(self::CACHE_PREFIX . $key, $ttl, $callback);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $redis = Redis::connection();
            $pattern = config('cache.prefix') . ':' . self::CACHE_PREFIX . '*';
            $keys = $redis->keys($pattern);
            
            $memoryInfo = $redis->info('memory');
            $statsInfo = $redis->info('stats');
            
            $stats = [
                'total_keys' => count($keys),
                'memory_usage' => $memoryInfo['used_memory_human'] ?? 'N/A',
                'hit_ratio' => $statsInfo['keyspace_hit_ratio'] ?? 'N/A',
            ];
        } catch (\Exception $e) {
            $stats = [
                'total_keys' => 0,
                'memory_usage' => 'N/A',
                'hit_ratio' => 'N/A',
            ];
        }

        return $stats;
    }
}
