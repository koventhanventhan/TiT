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
        $totalInstitutes = Institute::count();
        $activeInstitutes = Institute::where('status', 'active')->count();
        $suspendedInstitutes = Institute::where('status', 'suspended')->count();

        $totalStudents = User::withoutGlobalScopes()->where('role', 'user')->count();
        $totalTeachers = User::withoutGlobalScopes()->where('role', 'teacher')->count();
        $totalClasses = ZoomSchedule::withoutGlobalScopes()->count();

        $monthlyRevenue = Payment::withoutGlobalScopes()
            ->where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $expiringInstitutes = Institute::where('status', 'active')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>', now())
            ->count();

        return [
            Stat::make('Total Institutes', $totalInstitutes)
                ->description("{$activeInstitutes} active, {$suspendedInstitutes} suspended")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Total Students', number_format($totalStudents))
                ->description('Across all institutes')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Total Teachers', number_format($totalTeachers))
                ->description('Across all institutes')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Active Classes', number_format($totalClasses))
                ->description('Zoom schedules')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('warning'),

            Stat::make('Monthly Revenue', '$' . number_format($monthlyRevenue, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Expiry Alerts', $expiringInstitutes)
                ->description('Expiring within 7 days')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($expiringInstitutes > 0 ? 'danger' : 'success'),
        ];
    }
}
