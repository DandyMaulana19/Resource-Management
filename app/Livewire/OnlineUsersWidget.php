<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\UserActivityService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class OnlineUsersWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $userActivityService = app(UserActivityService::class);
        
        // Cache the results for 30 seconds to avoid hitting Redis too frequently
        $onlineCount = Cache::remember('online_users_count', 30, function () use ($userActivityService) {
            try {
                return $userActivityService->getOnlineUsersCount();
            } catch (\Exception $e) {
                return 0;
            }
        });

        $totalUsers = Cache::remember('total_users_count', 300, function () {
            return User::count();
        });

        $todayRegistrations = Cache::remember('today_registrations', 300, function () {
            return User::whereDate('created_at', today())->count();
        });

        return [
            Stat::make('Online Users', $onlineCount)
                ->description('Currently active users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Total Users', $totalUsers)
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('New Today', $todayRegistrations)
                ->description('Registered today')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('warning'),
        ];
    }

    public function getPollingInterval(): ?string
    {
        return '30s'; // Refresh every 30 seconds
    }
}
