<?php

namespace App\Filament\SuperAdmin\Resources\InstituteResource\Pages;

use App\Filament\SuperAdmin\Resources\InstituteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstitutes extends ListRecords
{
    protected static string $resource = InstituteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
