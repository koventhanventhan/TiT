<?php

namespace App\Filament\SuperAdmin\Resources\Announcements\Pages;

use App\Filament\SuperAdmin\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;
}
