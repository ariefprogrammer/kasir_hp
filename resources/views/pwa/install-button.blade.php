<div data-pwa-install hidden class="{{ $class ?? '' }}">
    <x-filament::button
        type="button"
        color="primary"
        icon="heroicon-o-arrow-down-tray"
        class="w-full"
        data-pwa-install-button
    >
        Install Aplikasi
    </x-filament::button>

    <p data-pwa-help hidden class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Di iPhone/iPad: ketuk tombol Bagikan di Safari, lalu pilih "Tambah ke Layar Utama".
    </p>
</div>