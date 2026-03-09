<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Widgets\ChartWidget;

use App\Models\Institute;
use Illuminate\Support\Carbon;

class InstituteGrowthChart extends ChartWidget
{
    protected ?string $heading = 'Institute Registrations (Last 12 Months)';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(function ($i) {
            return Carbon::now()->subMonths($i);
        });

        $counts = $months->map(function ($month) {
            return Institute::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        });

        $labels = $months->map(fn ($month) => $month->format('M Y'));

        return [
            'datasets' => [
                [
                    'label' => 'New Institutes',
                    'data' => $counts->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => '#3b82f6',
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
