<?php

namespace App\Filament\SuperAdmin\Resources\ContactMessageResource\Pages;

use App\Filament\SuperAdmin\Resources\ContactMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;
}
