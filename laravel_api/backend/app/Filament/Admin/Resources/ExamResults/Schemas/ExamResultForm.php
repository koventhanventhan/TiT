<?php

namespace App\Filament\Admin\Resources\ExamResults\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExamResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_name')
                    ->required(),
                TextInput::make('index_no')
                    ->required(),
                TextInput::make('term')
                    ->required(),
                TextInput::make('grade')
                    ->required(),
                TextInput::make('subject')
                    ->required(),
                TextInput::make('marks')
                    ->numeric()
                    ->default(null),
                TextInput::make('result_grade')
                    ->default(null),
                TextInput::make('rank')
                    ->numeric()
                    ->default(null),
                TextInput::make('year')
                    ->default(null),
                TextInput::make('institute_id')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
