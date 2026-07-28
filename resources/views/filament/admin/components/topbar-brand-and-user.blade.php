@php
    $user = auth()->user();
@endphp

<div class="raport-topbar-brand">
    @include('filament.admin.components.brand-logo', [
        'showText' => true,
    ])
</div>

@if ($user)
    <div class="raport-topbar-user-summary">
        <div class="raport-topbar-user-summary__greeting">
            Halo, {{ $user->name }}
        </div>

        <div class="raport-topbar-user-summary__role">
            @php
                $roleLabel = match ($user->role) {
                    'admin' => 'Administrator',
                    'guru' => 'Guru',
                    'siswa' => 'Siswa',
                    default => 'Pengguna',
                };
            @endphp
            {{ $roleLabel }}
        </div>
    </div>
@endif

@once
    <script>
        (() => {
            const avatarSize = '2.75rem';

            const applyTopbarAvatarSize = () => {
                const topbar = document.querySelector('.fi-topbar');

                if (!topbar) {
                    return;
                }

                const avatars = topbar.querySelectorAll('.fi-avatar');
                const avatar = avatars[avatars.length - 1];

                if (!avatar) {
                    return;
                }

                avatar.style.setProperty('width', avatarSize, 'important');
                avatar.style.setProperty('height', avatarSize, 'important');
                avatar.style.setProperty('min-width', avatarSize, 'important');
                avatar.style.setProperty('min-height', avatarSize, 'important');
                avatar.style.setProperty('max-width', avatarSize, 'important');
                avatar.style.setProperty('max-height', avatarSize, 'important');
                avatar.style.setProperty('object-fit', 'cover', 'important');
            };

            document.addEventListener('DOMContentLoaded', applyTopbarAvatarSize);
            document.addEventListener('livewire:navigated', applyTopbarAvatarSize);

            setTimeout(applyTopbarAvatarSize, 300);
        })();
    </script>
@endonce