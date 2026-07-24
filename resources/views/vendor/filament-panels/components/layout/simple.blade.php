@php
    use Filament\Support\Enums\MaxWidth;

    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'after' => null,
        'heading' => null,
        'subheading' => null,
    ])

    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e3a8a 100%) !important;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        }
        .fi-simple-layout {
            background: transparent !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }
        .fi-simple-main-ctn {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        .fi-simple-main {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.3) !important;
            padding: 3rem 2.5rem !important;
            transition: all 0.3s ease;
            margin: 2rem 0 1rem 0 !important;
        }
        .dark .fi-simple-main {
            background: rgba(24, 24, 27, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        @media (max-width: 640px) {
            .fi-simple-main {
                padding: 2rem 1.5rem !important;
                margin: 1rem 0 !important;
            }
        }
        /* Style the inputs */
        .fi-simple-main input {
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            padding: 0.75rem 1rem !important;
            transition: all 0.2s ease !important;
            background-color: white !important;
            color: #0f172a !important;
        }
        .dark .fi-simple-main input {
            border-color: #3f3f46 !important;
            background-color: #18181b !important;
            color: white !important;
        }
        .fi-simple-main input:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1) !important;
        }
        /* Style the labels */
        .fi-simple-main label,
        .fi-simple-main .fi-fo-field-wrp-label,
        .fi-simple-main span {
            color: #334155 !important;
        }
        .dark .fi-simple-main label,
        .dark .fi-simple-main .fi-fo-field-wrp-label,
        .dark .fi-simple-main span {
            color: #e2e8f0 !important;
        }
        /* Style the primary buttons */
        .fi-simple-main button[type="submit"], 
        .fi-simple-main .fi-btn-color-primary {
            background: #2563eb !important;
            border-radius: 12px !important;
            padding: 0.75rem 1.5rem !important;
            font-weight: 700 !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important;
            color: white !important;
        }
        .fi-simple-main button[type="submit"]:hover,
        .fi-simple-main .fi-btn-color-primary:hover {
            background: #1d4ed8 !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3) !important;
            transform: translateY(-1px);
        }
        .fi-simple-main button[type="submit"]:active,
        .fi-simple-main .fi-btn-color-primary:active {
            transform: translateY(0);
        }
        /* Style links */
        .fi-simple-main a {
            color: #2563eb !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
        }
        .fi-simple-main a:hover {
            color: #1d4ed8 !important;
            text-decoration: underline !important;
        }
        /* Logo and headings */
        .fi-simple-header-heading {
            font-size: 1.75rem !important;
            font-weight: 800 !important;
            letter-spacing: -0.025em !important;
            color: #0f172a !important;
        }
        .dark .fi-simple-header-heading {
            color: white !important;
        }
        .fi-simple-header-subheading {
            color: #475569 !important;
            font-weight: 500 !important;
        }
        .dark .fi-simple-header-subheading {
            color: #94a3b8 !important;
        }
        /* Back to home button */
        .back-to-home {
            margin-bottom: 2rem;
            text-align: center;
        }
        .back-to-home a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #cbd5e1 !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(4px);
        }
        .back-to-home a:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.2);
        }
    </style>

    <div class="fi-simple-layout flex min-h-screen flex-col items-center">
        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div
                class="absolute end-0 top-0 flex h-16 items-center gap-x-4 pe-4 md:pe-6 lg:pe-8"
            >
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications()
                    ])
                @endif

                <x-filament-panels::user-menu />
            </div>
        @endif

        <div
            class="fi-simple-main-ctn flex w-full flex-grow items-center justify-center"
        >
            <main
                @class([
                    'fi-simple-main my-16 w-full bg-white px-6 py-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:rounded-xl sm:px-12',
                    match ($maxWidth ??= (filament()->getSimplePageMaxContentWidth() ?? MaxWidth::Large)) {
                        MaxWidth::ExtraSmall, 'xs' => 'max-w-xs',
                        MaxWidth::Small, 'sm' => 'max-w-sm',
                        MaxWidth::Medium, 'md' => 'max-w-md',
                        MaxWidth::Large, 'lg' => 'max-w-lg',
                        MaxWidth::ExtraLarge, 'xl' => 'max-w-xl',
                        MaxWidth::TwoExtraLarge, '2xl' => 'max-w-2xl',
                        MaxWidth::ThreeExtraLarge, '3xl' => 'max-w-3xl',
                        MaxWidth::FourExtraLarge, '4xl' => 'max-w-4xl',
                        MaxWidth::FiveExtraLarge, '5xl' => 'max-w-5xl',
                        MaxWidth::SixExtraLarge, '6xl' => 'max-w-6xl',
                        MaxWidth::SevenExtraLarge, '7xl' => 'max-w-7xl',
                        MaxWidth::Full, 'full' => 'max-w-full',
                        MaxWidth::MinContent, 'min' => 'max-w-min',
                        MaxWidth::MaxContent, 'max' => 'max-w-max',
                        MaxWidth::FitContent, 'fit' => 'max-w-fit',
                        MaxWidth::Prose, 'prose' => 'max-w-prose',
                        MaxWidth::ScreenSmall, 'screen-sm' => 'max-w-screen-sm',
                        MaxWidth::ScreenMedium, 'screen-md' => 'max-w-screen-md',
                        MaxWidth::ScreenLarge, 'screen-lg' => 'max-w-screen-lg',
                        MaxWidth::ScreenExtraLarge, 'screen-xl' => 'max-w-screen-xl',
                        MaxWidth::ScreenTwoExtraLarge, 'screen-2xl' => 'max-w-screen-2xl',
                        default => $maxWidth,
                    },
                ])
            >
                {{ $slot }}
            </main>

            <div class="back-to-home">
                <a href="/">
                    <svg style="width: 1.2rem; height: 1.2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Beranda
                </a>
            </div>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire?->getRenderHookScopes()) }}
    </div>
</x-filament-panels::layout.base>
