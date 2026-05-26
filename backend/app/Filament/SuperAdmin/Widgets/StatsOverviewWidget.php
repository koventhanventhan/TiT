<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use App\Models\ZoomSchedule;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalStudents = User::withoutGlobalScopes()->where('role', 'user')->count();
        $totalTeachers = User::withoutGlobalScopes()->where('role', 'teacher')->count();
        $totalAdmins = User::withoutGlobalScopes()->where('role', 'admin')->count();

        $monthlyRevenue = Payment::withoutGlobalScopes()
            ->where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $allTimeRevenue = Payment::withoutGlobalScopes()
            ->where('status', 'paid')
            ->sum('amount');

        $activeSubscriptions = Institute::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        return [
            Stat::make('Total Users', number_format($totalStudents + $totalTeachers + $totalAdmins))
                ->description("{$totalStudents} Students, {$totalTeachers} Teachers, {$totalAdmins} Admins")
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Monthly Revenue', 'LKR ' . number_format($monthlyRevenue, 2))
                ->description('Current Month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('All Time Revenue', 'LKR ' . number_format($allTimeRevenue, 2))
                ->description('Total platform earnings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Active Subscriptions', $activeSubscriptions)
                ->description('Institutes with active plans')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
