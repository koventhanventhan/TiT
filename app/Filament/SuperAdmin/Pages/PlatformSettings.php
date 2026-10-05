<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;

class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string | \UnitEnum | null $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Platform Settings';
    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.super-admin.pages.platform-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::whereNull('institute_id')->get();
        
        $data = [];
        foreach ($settings as $setting) {
            $data[$setting->key] = $setting->value;
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->required(),
                        FileUpload::make('site_logo')
                            ->label('Site Logo')
                            ->directory('settings')
                            ->image(),
                    ])->columns(2),

                Section::make('Zoom API Configuration')
                    ->description('These credentials will be used as default for the platform.')
                    ->schema([
                        TextInput::make('zoom_client_id')
                            ->label('Zoom Client ID'),
                        TextInput::make('zoom_client_secret')
                            ->label('Zoom Client Secret')
                            ->password(),
                        TextInput::make('zoom_account_id')
                            ->label('Zoom Account ID'),
                    ])->columns(2),

                Section::make('PayHere Payment Gateway')
                    ->description('Global payment gateway credentials.')
                    ->schema([
                        TextInput::make('payhere_merchant_id')
                            ->label('Merchant ID'),
                        TextInput::make('payhere_merchant_secret')
                            ->label('Merchant Secret')
                            ->password(),
                        TextInput::make('payhere_app_id')
                            ->label('App ID'),
                        TextInput::make('payhere_app_secret')
                            ->label('App Secret')
                            ->password(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key, 'institute_id' => null],
                ['value' => $value, 'group' => 'platform']
            );
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
