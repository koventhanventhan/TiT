<?php

namespace App\Filament\SuperAdmin\Resources\InstituteResource\Pages;

use App\Filament\SuperAdmin\Resources\InstituteResource;
use App\Models\Institute;
use Closure;
use DomainException;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInstitute extends EditRecord
{
    protected static string $resource = InstituteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Setup checklist'),

            Actions\Action::make('suspend')
                ->label(fn (): string => (!$this->getRecord()->canBeDeletedSafely() && (int) $this->getRecord()->id !== 1) ? 'Suspend instead' : 'Suspend')
                ->icon('heroicon-o-no-symbol')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => "Suspend {$this->getRecord()->name}?")
                ->modalDescription(fn (): string => "Suspending {$this->getRecord()->name} blocks sign-ins for all its users until reactivated.")
                ->hidden(fn (): bool => (int) $this->getRecord()->id === 1 || $this->getRecord()->status !== 'active')
                ->action(function (): void {
                    /** @var Institute $record */
                    $record = $this->getRecord();
                    try {
                        $record->update(['status' => 'suspended']);
                        $this->refreshFormData(['status']);

                        Notification::make()
                            ->title("{$record->name} suspended")
                            ->success()
                            ->send();
                    } catch (DomainException $e) {
                        Notification::make()
                            ->title('Cannot suspend institute')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('delete_institute')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->hidden(fn (): bool => !$this->getRecord()->canBeDeletedSafely())
                ->requiresConfirmation()
                ->modalHeading(fn (): string => "Delete {$this->getRecord()->name}?")
                ->modalDescription(fn (): string => "Type \"{$this->getRecord()->name}\" below to confirm deletion.")
                ->form(fn (): array => [
                    TextInput::make('confirm_name')
                        ->label("Type \"{$this->getRecord()->name}\" to confirm")
                        ->required()
                        ->rule(function (): Closure {
                            $expected = $this->getRecord()->name;

                            return static function (string $attribute, mixed $value, Closure $fail) use ($expected): void {
                                if (trim((string) $value) !== $expected) {
                                    $fail("Please type \"{$expected}\" exactly to confirm deletion.");
                                }
                            };
                        }),
                ])
                ->action(function () {
                    /** @var Institute $record */
                    $record = $this->getRecord();
                    try {
                        $name = $record->name;
                        $record->delete();

                        Notification::make()
                            ->title("{$name} deleted")
                            ->success()
                            ->send();

                        return redirect()->to(InstituteResource::getUrl('index'));
                    } catch (DomainException $e) {
                        Notification::make()
                            ->title('Cannot delete institute')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
