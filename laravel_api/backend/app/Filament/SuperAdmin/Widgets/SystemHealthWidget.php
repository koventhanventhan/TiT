<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SystemHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0; // Show first

    protected function getStats(): array
    {
        // 1. DB Health
        $dbStatus = 'Healthy';
        $dbColor = 'success';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'Error';
            $dbColor = 'danger';
        }

        // 2. Disk Space
        $freeSpace = disk_free_space(base_path());
        $totalSpace = disk_total_space(base_path());
        $freePercent = ($freeSpace / $totalSpace) * 100;
        $diskColor = $freePercent < 10 ? 'danger' : ($freePercent < 20 ? 'warning' : 'success');
        $diskFormat = number_format($freeSpace / (1024 * 1024 * 1024), 2) . ' GB Free';

        // 3. Cache Health
        $cacheStatus = 'Healthy';
        $cacheColor = 'success';
        try {
            Cache::put('health_check', true, 10);
            if (!Cache::get('health_check')) throw new \Exception('Cache error');
        } catch (\Exception $e) {
            $cacheStatus = 'Error';
            $cacheColor = 'danger';
        }

        return [
            Stat::make('Database Connection', $dbStatus)
                ->description('MySQL Connection')
                ->descriptionIcon($dbStatus === 'Healthy' ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($dbColor),

            Stat::make('Disk Usage', $diskFormat)
                ->description(number_format($freePercent, 1) . '% available')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($diskColor),

            Stat::make('System Cache', $cacheStatus)
                ->description('File/Redis Driver')
                ->descriptionIcon($cacheStatus === 'Healthy' ? 'heroicon-m-sparkles' : 'heroicon-m-bolt-slash')
                ->color($cacheColor),
        ];
    }
}
