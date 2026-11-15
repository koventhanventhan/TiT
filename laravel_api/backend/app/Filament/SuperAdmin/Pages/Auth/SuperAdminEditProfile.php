<?php

namespace App\Filament\SuperAdmin\Pages\Auth;

use App\Notifications\SuperAdminPasswordChangedNotification;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password as PasswordRule;

class SuperAdminEditProfile extends EditProfile
{
    protected static ?string $navigationLabel = 'My account';

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/edit-profile.form.password.label'))
            ->password()
            ->revealable(Filament::arePasswordsRevealable())
            ->rule(
                PasswordRule::min(12)
                    ->letters()
                    ->numbers()
                    ->symbols()
            )
            ->helperText('Minimum 12 characters, with letters, numbers, and symbols.')
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->dehydrated(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn ($state): string => (string) $state)
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $passwordChanged = !empty($data['password']);

        if ($passwordChanged) {
            $data['password_changed_at'] = now();
        }

        $record->fill($data);
        $record->save();

        if ($passwordChanged) {
            // Invalidate other sessions
            if (Schema::hasTable('sessions')) {
                $currentSessionId = session()->isStarted() ? session()->getId() : null;
                $query = DB::table('sessions')->where('user_id', $record->id);
                if ($currentSessionId) {
                    $query->where('id', '!=', $currentSessionId);
                }
                $query->delete();
            }

            // Revoke Sanctum tokens
            if (method_exists($record, 'tokens')) {
                $record->tokens()->delete();
            }

            // Dispatch security notice
            try {
                $record->notify(new SuperAdminPasswordChangedNotification());
            } catch (\Throwable $e) {
                \Log::warning('Security notification failed: ' . $e->getMessage());
            }

            \Log::info("password updated for user #{$record->id}");
        }

        return $record;
    }
}
