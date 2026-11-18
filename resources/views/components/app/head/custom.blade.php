@props([
    'title' => '',
    'keywords' => '',
    'description' => '',
])

@php
    use Capell\Frontend\Facades\Frontend;

    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesLivewire = $runtimeManifest?->usesLivewire ?? false;
@endphp

<x-capell::app.head.tokens />

<script>
    (function () {
        // A visitor's own choice wins; with none stored, follow the OS.
        const darkScheme = window.matchMedia?.('(prefers-color-scheme: dark)');

        function storedTheme() {
            try {
                return localStorage.theme;
            } catch (error) {
                return undefined;
            }
        }

        function setupTheme() {
            const stored = storedTheme();
            const isDarkMode = stored === 'dark' || (stored !== 'light' && darkScheme?.matches === true);

            document.documentElement.classList.toggle('dark', isDarkMode);
        }

        darkScheme?.addEventListener?.('change', () => {
            const stored = storedTheme();

            if (stored !== 'dark' && stored !== 'light') {
                setupTheme();
            }
        });

        function updateHeaderSticky() {
            document.body.classList.toggle('header-sticky', window.scrollY > 0);
        }

        function handleHeaderAndTheme() {
            setupTheme();

            const header = document.getElementById('header');
            if (!header) return;
            updateHeaderSticky();
        }

        setupTheme();

        @if ($usesLivewire)
            window.removeEventListener('scroll', updateHeaderSticky);
            window.addEventListener('scroll', updateHeaderSticky, { passive: true });

            document.addEventListener('livewire:load', updateHeaderSticky);
            document.addEventListener('livewire:navigated', handleHeaderAndTheme);
        @endif
    })();
</script>
