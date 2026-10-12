<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#f59e0b">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<link rel="apple-touch-icon" href="/icons/icon-192.png">

<script>
    (function () {
        if (window.__pwaInstall) {
            return;
        }

        window.__pwaInstall = true;

        const state = { prompt: null };

        const isStandalone = () =>
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;

        const isIos = () =>
            /iphone|ipad|ipod/i.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        function render() {
            const canInstall = ! isStandalone() && (state.prompt !== null || isIos());

            document.querySelectorAll('[data-pwa-install]').forEach((el) => {
                el.hidden = ! canInstall;
            });
        }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            state.prompt = event;
            render();
        });

        window.addEventListener('appinstalled', () => {
            state.prompt = null;
            render();
        });

        window.matchMedia('(display-mode: standalone)').addEventListener('change', render);
        document.addEventListener('DOMContentLoaded', render);
        document.addEventListener('livewire:navigated', render);

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-pwa-install-button]');

            if (! button) {
                return;
            }

            if (state.prompt) {
                state.prompt.prompt();
                await state.prompt.userChoice;
                state.prompt = null; // event hanya bisa dipakai sekali
                render();

                return;
            }

            // iOS: tidak ada dialog otomatis, tampilkan petunjuk manual.
            const help = button.closest('[data-pwa-install]').querySelector('[data-pwa-help]');

            if (help) {
                help.hidden = ! help.hidden;
            }
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
            });
        }
    })();
</script>