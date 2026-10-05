<?php

namespace App\Filament\Admin\Resources\ExamResults\Pages;

use App\Filament\Admin\Resources\ExamResults\ExamResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamResults extends ListRecords
{
    protected static string $resource = ExamResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
