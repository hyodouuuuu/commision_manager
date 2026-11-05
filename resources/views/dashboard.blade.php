@extends('layouts.app', ['activeNav' => 'dashboard'])

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')
@section('breadcrumb', 'Ringkasan')

@section('content')
    <section class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium text-violet-700">Kamis, 5 November 2026</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Selamat datang kembali ✨</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Ini ringkasan commission kamu hari ini. Semoga harimu produktif dan penuh inspirasi.</p>
        </div>
        <a href="{{ route('dashboard.orders') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200">
            <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Buat order baru
        </a>
    </section>

    @php
        $stats = [
            [
                'label' => 'Pendapatan Real',
                'value' => 'Rp 2.450.000',
                'caption' => 'Pembayaran diterima',
                'change' => '+12,8%',
                'icon' => 'income',
                'iconClass' => 'bg-emerald-50 text-emerald-600',
                'changeClass' => 'text-emerald-700 bg-emerald-50',
            ],
            [
                'label' => 'Estimasi Pendapatan',
                'value' => 'Rp 5.800.000',
                'caption' => 'Dari order aktif',
                'change' => 'Aktif',
                'icon' => 'estimate',
                'iconClass' => 'bg-violet-50 text-violet-600',
                'changeClass' => 'text-violet-700 bg-violet-50',
            ],
            [
                'label' => 'Total Order',
                'value' => '24',
                'caption' => 'Semua commission',
                'change' => '+4 bulan ini',
                'icon' => 'orders',
                'iconClass' => 'bg-blue-50 text-blue-600',
                'changeClass' => 'text-blue-700 bg-blue-50',
            ],
            [
                'label' => 'Antrean Aktif',
                'value' => '8',
                'caption' => 'Perlu dikerjakan',
                'change' => '3 prioritas',
                'icon' => 'queue',
                'iconClass' => 'bg-amber-50 text-amber-600',
                'changeClass' => 'text-amber-700 bg-amber-50',
            ],
        ];
    @endphp

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Statistik commission">
        @foreach ($stats as $stat)
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/40">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl {{ $stat['iconClass'] }}">
                        @switch($stat['icon'])
                            @case('income')
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                    <path d="M4 17 9 12l3 3 7-8M14 7h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                @break
                            @case('estimate')
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                    <path d="M12 3v18m5-13.5C17 5.6 14.8 4 12 4S7 5.6 7 7.5 9.2 11 12 12s5 2.5 5 4.5-2.2 3.5-5 3.5-5-1.6-5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                                @break
                            @case('orders')
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                    <path d="M8 5h10a2 2 0 0 1 2 2v12H6V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M9 9h6M9 13h8M9 17h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                                @break
                            @case('queue')
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                @break
                        @endswitch
                    </div>
                    <span class="rounded-lg px-2 py-1 text-[11px] font-semibold {{ $stat['changeClass'] }}">{{ $stat['change'] }}</span>
                </div>
                <p class="mt-5 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $stat['caption'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/40 sm:p-6" aria-label="Deadline terdekat">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M12 7v5l3 2M5.5 5.5l-2-2m15 2 2-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Deadline Terdekat</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Jangan lewatkan jadwal commission berikutnya.</p>
                </div>
            </div>
            <a href="{{ route('dashboard.orders') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-violet-700 hover:text-violet-800">
                Lihat semua order
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

        <div class="mt-5 overflow-hidden rounded-xl border border-slate-100">
            <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 bg-slate-50/80 px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_auto_auto]">
                <span>Commission</span>
                <span class="hidden sm:block">Customer</span>
                <span>Status</span>
                <span>Deadline</span>
            </div>
            <div class="divide-y divide-slate-100">
                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-4 py-4 sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_auto_auto]">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-xs font-bold text-violet-700">#021</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800">Chibi — Paket Detail</span>
                            <span class="mt-0.5 block text-xs text-slate-400">2 ekspresi · 1 outfit</span>
                        </span>
                    </div>
                    <span class="hidden truncate text-sm text-slate-600 sm:block">@starlightvt</span>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">On progress</span>
                    <span class="text-right text-xs font-medium text-slate-600">08 Nov</span>
                </div>
                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-4 py-4 sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_auto_auto]">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-bold text-blue-700">#019</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800">Normal — Paket Standar</span>
                            <span class="mt-0.5 block text-xs text-slate-400">Full body · 1 ekspresi</span>
                        </span>
                    </div>
                    <span class="hidden truncate text-sm text-slate-600 sm:block">@moondroplive</span>
                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700">Belum dikerjakan</span>
                    <span class="text-right text-xs font-medium text-slate-600">12 Nov</span>
                </div>
            </div>
        </div>
    </section>

    <p class="mt-5 text-center text-xs text-slate-400">Data yang ditampilkan adalah contoh untuk pratinjau dashboard.</p>
@endsection
