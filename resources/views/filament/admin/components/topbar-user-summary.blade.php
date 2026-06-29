@php
    $user = auth()->user();

    $roleLabel = match ($user?->role) {
        'admin' => 'Administrator',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
        default => 'Pengguna',
    };

    $avatarSize = '2.75rem';
@endphp

@if ($user)
    <div class="raport-topbar-user-summary">
        <div
            class="raport-topbar-user-summary__greeting"
            style="font-size: 0.95rem; font-weight: 750;"
        >
            Hi, {{ $user->name }}
        </div>

        <div
            class="raport-topbar-user-summary__role"
            style="font-size: 0.76rem; font-weight: 500;"
        >
            {{ $roleLabel }}
        </div>
    </div>
@endif

@once
    <script>
        (() => {
            const avatarSize = '{{ $avatarSize }}';

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