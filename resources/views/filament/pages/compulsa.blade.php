<x-filament-panels::page>
    {{-- El proyecto no tiene un build de Tailwind que escanee las vistas de la app (solo se usa
         el CSS ya precompilado que trae Filament). Para que las utilidades extra usadas en el
         diseño de esta página (gradientes, blur, rotate, etc.) sí se generen, cargamos el
         Tailwind Play CDN (mismo enfoque ya usado en resources/views/components/guest-layout.blade.php)
         y lo configuramos para que reconozca los colores dinámicos de Filament y el dark mode por clase. --}}
    @once
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                corePlugins: {
                    // Filament ya trae su propio reset de estilos base; si dejamos el preflight
                    // de Tailwind activo, pisa los estilos de inputs/botones/tablas de TODO el panel.
                    preflight: false,
                },
                theme: {
                    extend: {
                        colors: {
                            // Filament almacena --primary-500, etc. como componentes RGB separados por
                            // COMA (ej. "245, 158, 11"), tal como se ve en su propio ColorManager y en su
                            // CSS compilado (rgba(var(--primary-500),var(--tw-bg-opacity,1))). Por eso usamos
                            // rgba(var(...), <alpha-value>) y NO el patrón moderno "rgb(... / <alpha-value>)"
                            // (que requiere valores separados por espacio y generaría CSS inválido aquí).
                            primary: {
                                50: 'rgba(var(--primary-50), <alpha-value>)',
                                100: 'rgba(var(--primary-100), <alpha-value>)',
                                200: 'rgba(var(--primary-200), <alpha-value>)',
                                300: 'rgba(var(--primary-300), <alpha-value>)',
                                400: 'rgba(var(--primary-400), <alpha-value>)',
                                500: 'rgba(var(--primary-500), <alpha-value>)',
                                600: 'rgba(var(--primary-600), <alpha-value>)',
                                700: 'rgba(var(--primary-700), <alpha-value>)',
                                800: 'rgba(var(--primary-800), <alpha-value>)',
                                900: 'rgba(var(--primary-900), <alpha-value>)',
                                950: 'rgba(var(--primary-950), <alpha-value>)',
                            },
                        },
                    },
                },
            };
        </script>
    @endonce

    @php
        $calculation = $this->activeCalculation;
    @endphp

    @if ($calculation)
        @include('filament.pages.compulsa.partials.loaded', ['calculation' => $calculation])
    @else
        @include('filament.pages.compulsa.partials.empty')
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
