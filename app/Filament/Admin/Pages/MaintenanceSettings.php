<?php

namespace App\Filament\Admin\Pages;

use App\Models\MaintenanceSetting;
use App\Models\User;
use App\Services\SiteAvailabilityService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class MaintenanceSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Maintenance Website';

    protected static ?string $slug = 'maintenance-website';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.admin.pages.maintenance-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(SiteAvailabilityService $availability): void
    {
        abort_unless(static::canAccess(), 403);

        $setting = $availability->setting();

        $this->form->fill([
            'enabled' => $setting?->enabled ?? false,
            'title' => $setting?->title ?? config('filament-maintenance.default_title'),
            'message' => $setting?->message ?? config('filament-maintenance.default_message'),
            'starts_at' => $setting?->starts_at,
            'ends_at' => $setting?->ends_at,
        ]);
    }

    public function save(SiteAvailabilityService $availability): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $setting = $availability->setting();

        abort_unless($setting instanceof MaintenanceSetting, 503);

        $setting->forceFill([
            'enabled' => (bool) $data['enabled'],
            'title' => $data['title'],
            'message' => $data['message'],
            'starts_at' => $data['starts_at'] ?: null,
            'ends_at' => $data['ends_at'] ?: null,
            'manager_roles' => [User::ROLE_ADMIN],
            'allowed_roles' => [User::ROLE_ADMIN],
            'enabled_by' => $data['enabled'] ? (string) auth()->id() : $setting->enabled_by,
            'enabled_at' => $data['enabled'] ? ($setting->enabled_at ?? now()) : $setting->enabled_at,
            'disabled_by' => $data['enabled'] ? $setting->disabled_by : (string) auth()->id(),
            'disabled_at' => $data['enabled'] ? $setting->disabled_at : now(),
        ])->save();

        Notification::make()
            ->title('Pengaturan maintenance disimpan')
            ->body($setting->isActive() ? 'Website sekarang hanya dapat diakses Administrator.' : 'Website tetap dapat diakses seluruh pengguna.')
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status Maintenance')
                    ->description('Saat aktif sesuai jadwal, semua sesi non-Administrator dihentikan dan website menampilkan halaman maintenance.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Aktifkan maintenance')
                            ->inline(false),
                    ]),
                Section::make('Informasi untuk Pengguna')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul maintenance')
                            ->placeholder('Update Fitur')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('message')
                            ->label('Pesan maintenance')
                            ->placeholder('Website sedang dalam proses pengembangan fitur...')
                            ->required()
                            ->rows(5)
                            ->maxLength(5000),
                    ]),
                Section::make('Periode Maintenance')
                    ->description('Kosongkan waktu mulai untuk aktif sekarang. Waktu selesai wajib diisi dan website otomatis dibuka setelah lewat.')
                    ->schema([
                        Grid::make(2)->schema([
                            DateTimePicker::make('starts_at')
                                ->label('Mulai')
                                ->seconds(false)
                                ->native(false)
                                ->timezone('Asia/Jakarta'),
                            DateTimePicker::make('ends_at')
                                ->label('Estimasi selesai')
                                ->seconds(false)
                                ->native(false)
                                ->required()
                                ->timezone('Asia/Jakarta')
                                ->after('starts_at'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Simpan Pengaturan')
                            ->icon('heroicon-o-check')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Maintenance Website';
    }

    public function getSubheading(): ?string
    {
        return 'Atur periode penutupan website dan informasi yang ditampilkan kepada pengguna.';
    }
}
