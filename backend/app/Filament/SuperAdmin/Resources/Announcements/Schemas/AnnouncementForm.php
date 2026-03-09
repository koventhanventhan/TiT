<?php

namespace App\Filament\SuperAdmin\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Broadcast Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options([
                                'info' => 'Information',
                                'success' => 'Success',
                                'warning' => 'Warning',
                                'danger' => 'Danger',
                            ])
                            ->default('info')
                            ->required(),
                        Textarea::make('message')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Visibility & Schedule')
                    ->schema([
                        Select::make('target_role')
                            ->options([
                                null => 'Everyone',
                                'admin' => 'Institute Admins',
                                'teacher' => 'Teachers',
                                'user' => 'Students',
                            ])
                            ->label('Target Audience'),
                        Toggle::make('is_active')
                            ->label('Active Now')
                            ->default(true),
                        DateTimePicker::make('starts_at')
                            ->label('Starts Displaying'),
                        DateTimePicker::make('expires_at')
                            ->label('Stops Displaying'),
                    ])->columns(2),
            ]);
    }
}
