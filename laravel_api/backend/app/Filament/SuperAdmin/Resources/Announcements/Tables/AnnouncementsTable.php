<?php

namespace App\Filament\SuperAdmin\Resources\Announcements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                        default => 'info',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('target_role')
                    ->label('Audience')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'admin' => 'Institutes',
                        'teacher' => 'Teachers',
                        'user' => 'Students',
                        default => 'Everyone',
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('starts_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'info' => 'Info',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'danger' => 'Danger',
                    ]),
                SelectFilter::make('target_role')
                    ->label('Audience')
                    ->options([
                        'admin' => 'Institutes',
                        'teacher' => 'Teachers',
                        'user' => 'Students',
                    ]),
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
