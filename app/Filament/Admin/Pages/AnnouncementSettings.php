<?php

namespace App\Filament\Admin\Pages;

use App\Models\AnnouncementSetting;
use App\Services\AnnouncementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

class AnnouncementSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Pengumuman Website';

    protected static ?string $slug = 'pengumuman-website';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 91;

    protected string $view = 'filament.admin.pages.announcement-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(AnnouncementService $announcements): void
    {
        abort_unless(static::canAccess(), 403);

        $setting = $announcements->setting();

        $this->form->fill([
            'enabled' => $setting?->enabled ?? false,
            'title' => $setting?->title,
            'message' => $setting?->message,
            'placement' => $setting?->placement ?? AnnouncementSetting::PLACEMENT_BEFORE_LOGIN,
            'starts_at' => $setting?->starts_at,
            'ends_at' => $setting?->ends_at,
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        AnnouncementSetting::query()->updateOrCreate(
            ['id' => AnnouncementSetting::query()->value('id') ?? 1],
            [
                'enabled' => (bool) $data['enabled'],
                'title' => $data['title'],
                'message' => $data['message'],
                'placement' => $data['placement'],
                'starts_at' => $data['starts_at'] ?: null,
                'ends_at' => $data['ends_at'] ?: null,
            ],
        );

        Notification::make()
            ->title('Pengumuman website disimpan')
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengumuman')
                    ->description('Pengumuman tampil sebagai dialog dan dapat ditutup pengguna.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Tampilkan pengumuman')
                            ->inline(false),
                        TextInput::make('title')
                            ->label('Judul pengumuman')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('message')
                            ->label('Isi pengumuman')
                            ->required()
                            ->rows(6)
                            ->maxLength(5000),
                        Select::make('placement')
                            ->label('Lokasi tampil')
                            ->options([
                                AnnouncementSetting::PLACEMENT_BEFORE_LOGIN => 'Sebelum login',
                                AnnouncementSetting::PLACEMENT_AFTER_LOGIN => 'Setelah login',
                                AnnouncementSetting::PLACEMENT_BOTH => 'Sebelum dan setelah login',
                            ])
                            ->required()
                            ->native(false),
                    ]),
                Section::make('Periode Tayang')
                    ->description('Tanpa waktu mulai, pengumuman aktif segera. Tanpa waktu selesai, pengumuman tetap aktif sampai dimatikan.')
                    ->schema([
                        Grid::make(2)->schema([
                            DateTimePicker::make('starts_at')
                                ->label('Mulai tampil')
                                ->seconds(false)
                                ->native(false)
                                ->timezone('Asia/Jakarta'),
                            DateTimePicker::make('ends_at')
                                ->label('Selesai tampil')
                                ->seconds(false)
                                ->native(false)
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
                            ->label('Simpan Pengumuman')
                            ->icon('heroicon-o-check')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Pengumuman Website';
    }

    public function getSubheading(): ?string
    {
        return 'Atur dialog informasi sebelum login, setelah login, atau keduanya.';
    }
}
