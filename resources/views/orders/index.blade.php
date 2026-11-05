@extends('layouts.app', ['activeNav' => $activeNav])

@section('title', 'Daftar Order')
@section('page-heading', 'Daftar Order')
@section('breadcrumb', 'Antrean commission')

@section('content')
    <section
        data-orders-page
        data-orders-url="{{ route('orders.index') }}"
        data-order-url-template="{{ route('orders.show', ['order' => '__ORDER__']) }}"
        data-customers-url="{{ route('customers.index') }}"
        data-packages-url="{{ route('packages.index') }}"
        data-addons-url-template="{{ route('orders.addons.index', ['order' => '__ORDER__']) }}"
        data-payments-url-template="{{ route('orders.payments.index', ['order' => '__ORDER__']) }}"
    >
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-violet-700">Workspace commission</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Daftar Order</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Atur antrean, pantau pembayaran, dan ikuti progres setiap commission.</p>
            </div>
            <button type="button" data-create-order class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Tambah Order
            </button>
        </div>

        <div class="mt-7 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-200/40 xl:flex-row xl:items-center">
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Cari order</span>
                <svg viewBox="0 0 24 24" fill="none" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/>
                    <path d="m16 16 4.2 4.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
                <input data-filter-search type="search" placeholder="Cari nama akun, username, atau nomor antrean..." class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100">
            </label>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:flex">
                <label class="min-w-0">
                    <span class="sr-only">Filter pembayaran</span>
                    <select data-filter-payment class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-600 outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100 xl:w-44">
                        <option value="semua">Semua Pembayaran</option>
                        <option value="belum_bayar">Belum Bayar</option>
                        <option value="dp">DP</option>
                        <option value="lunas">Lunas</option>
                    </select>
                </label>
                <label class="min-w-0">
                    <span class="sr-only">Filter pekerjaan</span>
                    <select data-filter-work class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-600 outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100 xl:w-48">
                        <option value="semua">Semua Pekerjaan</option>
                        <option value="belum_dikerjakan">Belum Dikerjakan</option>
                        <option value="on_progress">On Progress</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </label>
                <label class="min-w-0">
                    <span class="sr-only">Filter bulan order</span>
                    <select data-filter-month class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-600 outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100 xl:w-44">
                        <option value="">Semua Bulan</option>
                    </select>
                </label>
            </div>
            <button type="button" data-reset-filters class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl px-3 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="M4.5 8A8 8 0 1 1 4 14M4.5 4v4h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Reset
            </button>
        </div>

        <div data-orders-alert class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert" aria-live="polite"></div>
        <div data-orders-notice class="mt-4 hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status" aria-live="polite"></div>

        <section class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-200/30">
                <p class="text-xs font-medium text-slate-500">Order ditemukan</p>
                <p data-order-count class="mt-1 text-lg font-bold text-slate-900">—</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-200/30 sm:col-span-2">
                <p class="text-xs font-medium text-slate-500">Estimasi pendapatan order aktif</p>
                <p data-active-estimate class="mt-1 text-lg font-bold text-slate-900">—</p>
            </div>
        </section>

        <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-200/40" aria-label="Order commission">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4 sm:px-5">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Antrean commission</h2>
                    <p class="mt-1 text-xs text-slate-500">Nomor antrean mengikuti urutan order dibuat.</p>
                </div>
                <span data-loading class="hidden items-center gap-2 text-xs font-medium text-slate-400">
                    <span class="size-2 animate-pulse rounded-full bg-violet-500"></span>
                    Memuat
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1180px] border-collapse text-left">
                    <thead class="bg-slate-50/80">
                        <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <th scope="col" class="px-4 py-3.5 sm:px-5">No</th>
                            <th scope="col" class="px-4 py-3.5">Nama Akun</th>
                            <th scope="col" class="px-4 py-3.5">Username</th>
                            <th scope="col" class="px-4 py-3.5">Paket</th>
                            <th scope="col" class="px-4 py-3.5">Tanggal Order</th>
                            <th scope="col" class="px-4 py-3.5">Deadline</th>
                            <th scope="col" class="px-4 py-3.5">Status Pembayaran</th>
                            <th scope="col" class="px-4 py-3.5">Catatan</th>
                            <th scope="col" class="px-4 py-3.5">Status Pekerjaan</th>
                            <th scope="col" class="px-4 py-3.5">Aksi</th>
                        </tr>
                    </thead>
                    <tbody data-orders-table class="divide-y divide-slate-100 text-sm">
                        <tr>
                            <td colspan="10" class="px-5 py-12 text-center text-sm text-slate-400">Memuat daftar order...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <p data-pagination-summary class="text-xs text-slate-500">—</p>
                <div class="flex items-center gap-2">
                    <button type="button" data-page-previous class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Sebelumnya</button>
                    <span data-page-indicator class="min-w-16 text-center text-xs font-medium text-slate-500">—</span>
                    <button type="button" data-page-next class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Berikutnya</button>
                </div>
            </div>
        </section>
    </section>

    <dialog data-order-form-dialog class="m-auto max-h-[92vh] w-[min(96vw,760px)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45">
        <form data-order-form>
            <div class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:px-7">
                <div>
                    <h2 data-form-title class="text-lg font-bold text-slate-900">Tambah Order</h2>
                    <p class="mt-1 text-xs text-slate-500">Nomor antrean dibuat otomatis oleh sistem.</p>
                </div>
                <button type="button" data-close-form class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup form">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-7">
                <fieldset class="sm:col-span-2">
                    <legend class="text-sm font-semibold text-slate-800">Customer</legend>
                    <div class="mt-3 flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="radio" name="customer_mode" value="existing" checked class="accent-violet-600">
                            Pilih customer
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="radio" name="customer_mode" value="new" class="accent-violet-600">
                            Buat customer baru
                        </label>
                    </div>
                    <div data-existing-customer-fields class="mt-3">
                        <label class="block text-xs font-medium text-slate-600" for="order-customer">Customer</label>
                        <select id="order-customer" name="customer_id" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                            <option value="">Memuat customer...</option>
                        </select>
                    </div>
                    <div data-new-customer-fields class="mt-3 hidden grid gap-3 sm:grid-cols-3">
                        <label class="text-xs font-medium text-slate-600">Nama akun
                            <input name="customer_account_name" maxlength="255" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        </label>
                        <label class="text-xs font-medium text-slate-600">Username
                            <input name="customer_username" maxlength="255" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        </label>
                        <label class="text-xs font-medium text-slate-600">Platform
                            <input name="customer_platform" maxlength="255" placeholder="X, Instagram, Discord..." class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="sm:col-span-2">
                    <legend class="text-sm font-semibold text-slate-800">Paket commission</legend>
                    <div data-package-snapshot class="mt-2 hidden rounded-xl border border-violet-100 bg-violet-50/70 px-4 py-3 text-sm text-slate-700"></div>
                    <div data-package-fields class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">Paket
                            <select name="package_id" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                <option value="">Memuat paket...</option>
                            </select>
                        </label>
                        <label data-custom-package-name class="hidden text-xs font-medium text-slate-600">Nama paket
                            <input name="package_name" maxlength="255" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        </label>
                        <label class="text-xs font-medium text-slate-600">Harga paket order (Rp)
                            <input name="package_price" type="number" min="0" step="0.01" inputmode="decimal" required class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        </label>
                    </div>
                </fieldset>

                <label class="text-xs font-medium text-slate-600">Tanggal order
                    <input name="order_date" type="date" required class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                </label>
                <label class="text-xs font-medium text-slate-600">Status pekerjaan
                    <select name="work_status" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        <option value="belum_dikerjakan">Belum Dikerjakan</option>
                        <option value="on_progress">On Progress</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </label>
                <label class="text-xs font-medium text-slate-600">Tanggal mulai disepakati
                    <input name="agreed_start_date" type="date" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                </label>
                <label class="text-xs font-medium text-slate-600">Tanggal mulai aktual
                    <input name="actual_start_date" type="date" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                </label>
                <label class="text-xs font-medium text-slate-600 sm:col-span-2">Deadline
                    <input name="deadline" type="date" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100 sm:max-w-xs">
                </label>
                <fieldset data-addon-fields class="sm:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <legend class="text-sm font-semibold text-slate-800">Add-on</legend>
                        <button type="button" data-add-addon class="rounded-lg px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-50">+ Tambah add-on</button>
                    </div>
                    <div data-addon-list class="mt-3 flex flex-col gap-3"></div>
                </fieldset>
                <div data-total-preview class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 sm:col-span-2">
                    <span class="text-sm font-medium text-slate-600">Total harga</span>
                    <span data-total-value class="text-base font-bold text-slate-900">Rp 0</span>
                </div>
                <label class="text-xs font-medium text-slate-600 sm:col-span-2">Catatan
                    <textarea name="notes" rows="3" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"></textarea>
                </label>
                <div data-form-errors class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 sm:col-span-2" role="alert" aria-live="polite"></div>
            </div>
            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:px-7">
                <button type="button" data-close-form class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" data-save-order class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 disabled:cursor-wait disabled:opacity-60">Simpan Order</button>
            </div>
        </form>
    </dialog>

    <dialog data-order-detail-dialog class="m-auto max-h-[92vh] w-[min(96vw,720px)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45">
        <div class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:px-7">
            <div>
                <p data-detail-queue class="text-xs font-semibold uppercase tracking-wider text-violet-700"></p>
                <h2 data-detail-title class="mt-1 text-lg font-bold text-slate-900">Detail order</h2>
            </div>
            <button type="button" data-close-detail class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup detail">
                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <div data-order-detail class="flex flex-col gap-5 px-5 py-5 sm:px-7"></div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-7">
            <button type="button" data-delete-detail class="mr-auto rounded-xl px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hapus permanen</button>
            <button type="button" data-edit-detail class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
            <button type="button" data-close-detail class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700">Tutup</button>
        </div>
    </dialog>
@endsection
