<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestActivityWidget extends TableWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityLog::withoutGlobalScopes()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('log')
                    ->label('Activity')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->default('System'),
                TextColumn::make('institute.name')
                    ->label('Institute')
                    ->default('Master'),
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
