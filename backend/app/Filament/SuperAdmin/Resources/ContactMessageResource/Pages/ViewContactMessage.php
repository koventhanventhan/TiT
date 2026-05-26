<?php

namespace App\Filament\SuperAdmin\Resources\ContactMessageResource\Pages;

use App\Filament\SuperAdmin\Resources\ContactMessageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Mark as read when viewed
        if (!$this->record->is_read) {
            $this->record->update(['is_read' => true]);
        }
    }
}
