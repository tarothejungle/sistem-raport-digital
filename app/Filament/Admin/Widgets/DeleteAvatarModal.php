<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteAvatarModal extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public bool $isOpen = false;

    #[On('openDeleteAvatarModal')]
    public function openModal(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function confirmDelete(): void
    {
        $user = auth()->user();

        if ($user === null || blank($user->avatar_path)) {
            $this->close();

            return;
        }

        Storage::disk('public')->delete($user->avatar_path);

        $user->update(['avatar_path' => null]);

        $this->close();

        Notification::make()
            ->success()
            ->title('Foto profil berhasil dihapus.')
            ->send();

        $this->dispatch('avatarDeleted');
    }

    public function render(): View
    {
        return view('filament.admin.widgets.modals.delete-avatar-modal');
    }
}
