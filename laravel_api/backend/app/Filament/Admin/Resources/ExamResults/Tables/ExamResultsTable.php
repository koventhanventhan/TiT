<?php

namespace App\Filament\Admin\Resources\ExamResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_name')
                    ->searchable(),
                TextColumn::make('index_no')
                    ->searchable(),
                TextColumn::make('term')
                    ->searchable(),
                TextColumn::make('grade')
                    ->searchable(),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('marks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('result_grade')
                    ->searchable(),
                TextColumn::make('rank')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('year')
                    ->searchable(),
                TextColumn::make('institute_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
