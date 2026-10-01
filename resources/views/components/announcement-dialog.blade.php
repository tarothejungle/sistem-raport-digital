@php
    $placement = auth()->check()
        ? \App\Models\AnnouncementSetting::PLACEMENT_AFTER_LOGIN
        : \App\Models\AnnouncementSetting::PLACEMENT_BEFORE_LOGIN;
    $announcement = app(\App\Services\AnnouncementService::class)->activeFor($placement);
@endphp

@if ($announcement)
    <div
        x-data="{
            open: false,
            alwaysShow: @js($placement === \App\Models\AnnouncementSetting::PLACEMENT_BEFORE_LOGIN),
            key: @js('announcement-'.$announcement->getKey().'-'.$announcement->updated_at?->timestamp.'-'.$placement),
            init() {
                this.open = this.alwaysShow || sessionStorage.getItem(this.key) !== 'dismissed'
                if (this.open) this.$nextTick(() => this.$refs.closeButton.focus())
            },
            close() {
                if (! this.alwaysShow) sessionStorage.setItem(this.key, 'dismissed')
                this.open = false
            },
        }"
        x-cloak
        x-show="open"
        x-on:keydown.escape.window="close()"
        class="raport-announcement"
        role="dialog"
        aria-modal="true"
        aria-labelledby="raport-announcement-title"
        aria-describedby="raport-announcement-message"
    >
        <div class="raport-announcement__backdrop" aria-hidden="true"></div>
        <section class="raport-announcement__panel" x-trap.noscroll="open">
            <div class="raport-announcement__icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-megaphone" class="h-6 w-6" />
            </div>
            <p class="raport-announcement__eyebrow">Pengumuman</p>
            <h2 id="raport-announcement-title">{{ $announcement->title }}</h2>
            <p id="raport-announcement-message">{{ $announcement->message }}</p>
            <button x-ref="closeButton" type="button" x-on:click="close()">Saya Mengerti</button>
        </section>
    </div>
@endif
