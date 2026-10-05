<?php

namespace App\Filament\SuperAdmin\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'success',
                        'percentage' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($record): string => $record->type === 'fixed' 
                        ? '$' . number_format($record->value, 2) 
                        : number_format($record->value, 0) . '%'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('usage')
                    ->label('Usage')
                    ->state(fn ($record): string => ($record->max_uses ? "{$record->used_count} / {$record->max_uses}" : "{$record->used_count} / ∞"))
                    ->badge()
                    ->color(fn ($record): string => $record->max_uses && $record->used_count >= $record->max_uses ? 'danger' : 'gray'),
                TextColumn::make('starts_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : null),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
