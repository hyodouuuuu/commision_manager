@extends('layouts.app', ['activeNav' => $activeNav])

@section('title', $title)
@section('page-heading', $title)
@section('breadcrumb', $title)

@section('content')
    <section class="rounded-2xl border border-slate-200/80 bg-white px-6 py-16 text-center shadow-sm shadow-slate-200/40 sm:px-10">
        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
            <svg viewBox="0 0 24 24" fill="none" class="size-7" aria-hidden="true">
                <path d="M12 3v18m5-13.5C17 5.6 14.8 4 12 4S7 5.6 7 7.5 9.2 11 12 12s5 2.5 5 4.5-2.2 3.5-5 3.5-5-1.6-5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
        </span>
        <p class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-violet-600">Commission Manager</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">{{ $description }}</p>
        <span class="mt-6 inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">Halaman ini akan segera tersedia</span>
    </section>
@endsection
