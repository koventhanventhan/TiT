<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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

        $expiringSoon = Institute::where('id', '!=', 1)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();

        $expiredInstitutes = Institute::where('id', '!=', 1)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        $overLimitInstitutes = Institute::whereHas('plan', fn ($q) => $q->where('max_students', '>', 0))
            ->whereRaw('(SELECT count(*) FROM users WHERE users.institute_id = institutes.id AND users.role = \'user\' AND users.deleted_at IS NULL) > (SELECT max_students FROM subscription_plans WHERE subscription_plans.id = institutes.subscription_plan_id)')
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

            Stat::make('Expiring in 30 days', $expiringSoon)
                ->description('Institutes nearing expiry')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'gray'),

            Stat::make('Expired', $expiredInstitutes)
                ->description('Institutes past subscription date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($expiredInstitutes > 0 ? 'danger' : 'gray'),

            Stat::make('Over Limit', $overLimitInstitutes)
                ->description('Institutes exceeding plan student capacity')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($overLimitInstitutes > 0 ? 'danger' : 'gray'),
        ];
    }
}
