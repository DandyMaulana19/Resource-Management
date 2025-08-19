<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ActivityLogService
{
    private const LOG_KEY = 'activity_log';
    private const MAX_LOGS = 1000;

    /**
     * Log an activity
     */
    public function log(string $action, ?array $data = null, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        
        $logEntry = [
            'id' => uniqid(),
            'user_id' => $userId,
            'action' => $action,
            'data' => $data ? json_encode($data) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->timestamp,
            'created_at' => now()->toISOString(),
        ];

        // Add to Redis list (prepend to keep newest first)
        Redis::lpush(self::LOG_KEY, json_encode($logEntry));
        
        // Trim the list to keep only the latest entries
        Redis::ltrim(self::LOG_KEY, 0, self::MAX_LOGS - 1);
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(int $limit = 50, int $offset = 0): array
    {
        $activities = Redis::lrange(self::LOG_KEY, $offset, $offset + $limit - 1);
        
        return array_map(function ($activity) {
            $decoded = json_decode($activity, true);
            $decoded['created_at'] = Carbon::parse($decoded['created_at']);
            return $decoded;
        }, $activities);
    }

    /**
     * Get activities for a specific user
     */
    public function getUserActivities(int $userId, int $limit = 50): array
    {
        $allActivities = $this->getRecentActivities(self::MAX_LOGS);
        
        $userActivities = array_filter($allActivities, function ($activity) use ($userId) {
            return $activity['user_id'] === $userId;
        });

        return array_slice($userActivities, 0, $limit);
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(): array
    {
        $activities = $this->getRecentActivities(self::MAX_LOGS);
        
        $stats = [
            'total_activities' => count($activities),
            'today_activities' => 0,
            'unique_users' => [],
            'top_actions' => [],
        ];

        $actionCounts = [];
        $today = now()->startOfDay();

        foreach ($activities as $activity) {
            // Count today's activities
            if ($activity['created_at']->gte($today)) {
                $stats['today_activities']++;
            }

            // Track unique users
            if ($activity['user_id']) {
                $stats['unique_users'][$activity['user_id']] = true;
            }

            // Count actions
            $action = $activity['action'];
            $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;
        }

        $stats['unique_users_count'] = count($stats['unique_users']);
        unset($stats['unique_users']);

        // Get top 5 actions
        arsort($actionCounts);
        $stats['top_actions'] = array_slice($actionCounts, 0, 5, true);

        return $stats;
    }

    /**
     * Clear all activity logs
     */
    public function clearLogs(): void
    {
        Redis::del(self::LOG_KEY);
    }

    /**
     * Search activities by action or user
     */
    public function searchActivities(string $query, int $limit = 50): array
    {
        $allActivities = $this->getRecentActivities(self::MAX_LOGS);
        
        $filteredActivities = array_filter($allActivities, function ($activity) use ($query) {
            return stripos($activity['action'], $query) !== false ||
                   stripos($activity['ip_address'], $query) !== false ||
                   ($activity['data'] && stripos($activity['data'], $query) !== false);
        });

        return array_slice($filteredActivities, 0, $limit);
    }
}
