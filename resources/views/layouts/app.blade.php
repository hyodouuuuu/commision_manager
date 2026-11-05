<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f8fc">
    <title>@yield('title', 'Dashboard') · Commission Manager</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <div
            id="sidebar-overlay"
            class="fixed inset-0 z-30 hidden bg-slate-950/40 backdrop-blur-[2px] lg:hidden"
            aria-hidden="true"
        ></div>

        <aside
            id="app-sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            aria-label="Navigasi utama"
        >
            <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3" aria-label="Commission Manager, Dashboard">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-violet-600 text-white shadow-sm shadow-violet-200">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M5 18V9.5L12 5l7 4.5V18h-5v-5h-4v5H5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="m9 9 3 2 3-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-bold tracking-tight text-slate-900">Commission</span>
                        <span class="block text-xs font-medium text-slate-500">Manager</span>
                    </span>
                </a>
                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                    data-sidebar-close
                    aria-label="Tutup menu navigasi"
                >
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="px-4 pt-7">
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Workspace</p>
                <nav class="mt-3 flex flex-col gap-1" aria-label="Menu aplikasi">
                    @php
                        $navigation = [
                            ['label' => 'Dashboard', 'route' => 'dashboard', 'key' => 'dashboard', 'icon' => 'dashboard'],
                            ['label' => 'Daftar Order', 'route' => 'dashboard.orders', 'key' => 'orders', 'icon' => 'orders'],
                            ['label' => 'Customer', 'route' => 'dashboard.customers', 'key' => 'customers', 'icon' => 'customers'],
                            ['label' => 'Paket Commission', 'route' => 'dashboard.packages', 'key' => 'packages', 'icon' => 'packages'],
                            ['label' => 'Keuangan', 'route' => 'dashboard.finance', 'key' => 'finance', 'icon' => 'finance'],
                        ];
                    @endphp

                    @foreach ($navigation as $item)
                        @php($isActive = $activeNav === $item['key'])
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($isActive) aria-current="page" @endif
                            class="{{ $isActive ? 'bg-violet-50 text-violet-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                        >
                            @switch($item['icon'])
                                @case('dashboard')
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5 {{ $isActive ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }}" aria-hidden="true">
                                        <rect x="4" y="4" width="6" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                                        <rect x="14" y="4" width="6" height="4" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                                        <rect x="14" y="11" width="6" height="9" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                                        <rect x="4" y="14" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                                    </svg>
                                    @break
                                @case('orders')
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5 {{ $isActive ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }}" aria-hidden="true">
                                        <path d="M8 5h10a2 2 0 0 1 2 2v12H6V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                        <path d="M9 9h6M9 13h8M9 17h5M4 8v12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                    @break
                                @case('customers')
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5 {{ $isActive ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }}" aria-hidden="true">
                                        <circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.7"/>
                                        <path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.3a3 3 0 0 1 0 5.8m1 2.5a5 5 0 0 1 3.5 4.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                    @break
                                @case('packages')
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5 {{ $isActive ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }}" aria-hidden="true">
                                        <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                        <path d="m4.5 7.8 7.5 4.4 7.5-4.4M12 12.5V21m-4-16 8 4.5" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    </svg>
                                    @break
                                @case('finance')
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5 {{ $isActive ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }}" aria-hidden="true">
                                        <rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                                        <path d="M3.5 9h17M8 14h3m5 0h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                    @break
                            @endswitch
                            <span>{{ $item['label'] }}</span>
                            @if ($item['key'] === 'orders')
                                <span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">12</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="mt-auto p-4">
                <div class="rounded-2xl border border-violet-100 bg-violet-50/70 p-4">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-800">Ruang berkarya</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Semua commission kamu, tertata dalam satu tempat.</p>
                </div>
                <div class="mt-4 flex items-center gap-3 px-2 py-2">
                    <span class="flex size-9 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">CM</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-slate-800">Commission Studio</span>
                        <span class="block truncate text-xs text-slate-500">Workspace</span>
                    </span>
                    <span class="size-2 rounded-full bg-emerald-500" aria-label="Status aktif"></span>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur sm:px-6 lg:h-20 lg:px-10">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden"
                        data-sidebar-open
                        aria-label="Buka menu navigasi"
                        aria-controls="app-sidebar"
                        aria-expanded="false"
                    >
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900 sm:text-base">@yield('page-heading', 'Dashboard')</p>
                        <p class="hidden text-xs text-slate-500 sm:block">Commission Manager <span class="px-1 text-slate-300">/</span> @yield('breadcrumb', 'Ringkasan')</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-4">
                    <span class="hidden items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500 md:inline-flex">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                        Semua tersinkron
                    </span>
                    <button type="button" class="relative flex size-10 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100" aria-label="Notifikasi">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="absolute right-2 top-2 size-2 rounded-full border-2 border-white bg-violet-500"></span>
                    </button>
                    <span class="flex size-9 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">CS</span>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-10 lg:py-10">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
