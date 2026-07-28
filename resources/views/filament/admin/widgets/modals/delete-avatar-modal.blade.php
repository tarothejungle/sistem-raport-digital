<div>
    @if ($isOpen)
        <div
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            x-data="{ focused: null }"
            x-init="
                focused = document.activeElement;
                $nextTick(() => $refs.cancelBtn?.focus());
            "
            x-on:keydown.escape.window="$wire.close()"
            x-on:keydown.tab.prevent="
                const focusable = [...$el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])')].filter(el => !el.hasAttribute('disabled'));
                if (focusable.length === 0) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if ($event.shiftKey) {
                    if (document.activeElement === first) { last.focus(); }
                    else { first.focus(); }
                } else {
                    if (document.activeElement === last) { first.focus(); }
                    else { (focusable[focusable.indexOf(document.activeElement)+1]||first).focus(); }
                }
            "
        >
            <div
                class="fixed inset-0 bg-slate-950/50 backdrop-blur-[1px] dark:bg-slate-950/70"
                aria-hidden="true"
                x-on:click="$wire.close()"
            ></div>

            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="delete-avatar-title"
                aria-describedby="delete-avatar-desc"
                class="relative z-10 w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                x-trap.noscroll="true"
            >
                <div class="px-5 pt-5 sm:px-6 sm:pt-6">
                    <div class="flex items-start gap-3.5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-red-50 text-red-600 ring-1 ring-red-100 dark:bg-red-950/60 dark:text-red-400 dark:ring-red-900/50" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-trash" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <h2
                                id="delete-avatar-title"
                                class="text-[15px] font-semibold leading-6 text-slate-900 dark:text-white"
                            >
                                Hapus foto profil
                            </h2>
                            <p
                                id="delete-avatar-desc"
                                class="mt-1.5 text-[13px] leading-5 text-slate-600 dark:text-slate-400"
                            >
                                Apakah Anda yakin ingin menghapus foto profil? Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-slate-200 px-5 py-3.5 dark:border-slate-700 sm:px-6">
                    <button
                        type="button"
                        x-ref="cancelBtn"
                        wire:click="close"
                        class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-[13px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus-visible:ring-offset-slate-900"
                        x-on:click="$nextTick(() => focused?.focus())"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        wire:click="confirmDelete"
                        wire:loading.attr="disabled"
                        class="inline-flex min-h-9 items-center justify-center rounded-lg bg-red-600 px-3.5 py-2 text-[13px] font-semibold text-white shadow-sm transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:opacity-60 dark:focus-visible:ring-offset-slate-900"
                    >
                        <span wire:loading.remove wire:target="confirmDelete">Ya, hapus</span>
                        <span wire:loading wire:target="confirmDelete" class="inline-flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 animate-spin" />
                            Menghapus...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
