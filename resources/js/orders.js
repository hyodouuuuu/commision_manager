const ordersPage = document.querySelector('[data-orders-page]');

if (ordersPage) {
    const ordersUrl = ordersPage.dataset.ordersUrl;
    const orderUrlTemplate = ordersPage.dataset.orderUrlTemplate;
    const customersUrl = ordersPage.dataset.customersUrl;
    const packagesUrl = ordersPage.dataset.packagesUrl;
    const addonsUrlTemplate = ordersPage.dataset.addonsUrlTemplate;
    const paymentsUrlTemplate = ordersPage.dataset.paymentsUrlTemplate;
    const tableBody = ordersPage.querySelector('[data-orders-table]');
    const alertBox = ordersPage.querySelector('[data-orders-alert]');
    const noticeBox = ordersPage.querySelector('[data-orders-notice]');
    const loadingIndicator = ordersPage.querySelector('[data-loading]');
    const formDialog = ordersPage.querySelector('[data-order-form-dialog]');
    const detailDialog = ordersPage.querySelector('[data-order-detail-dialog]');
    const orderForm = ordersPage.querySelector('[data-order-form]');
    const addonList = ordersPage.querySelector('[data-addon-list]');
    const packageSelect = orderForm.elements.package_id;
    const packagePriceInput = orderForm.elements.package_price;
    const customerSelect = orderForm.elements.customer_id;
    const packagePriceFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 2,
    });

    const state = {
        page: 1,
        pages: 1,
        currentOrder: null,
        customers: [],
        packages: [],
        searchTimer: null,
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function urlFor(template, id) {
        return template.replace('__ORDER__', encodeURIComponent(String(id)));
    }

    async function request(url, options = {}) {
        let response;

        try {
            response = await fetch(url, {
                ...options,
                headers: {
                    Accept: 'application/json',
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...options.headers,
                },
            });
        } catch {
            throw new Error('Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.');
        }

        if (response.status === 204) {
            return null;
        }

        let payload;

        try {
            payload = await response.json();
        } catch {
            throw new Error(`Server mengembalikan response yang tidak dapat dibaca (HTTP ${response.status}).`);
        }

        if (!response.ok) {
            const validationMessages = Object.values(payload.errors ?? {}).flat();
            const message = validationMessages.length
                ? validationMessages.join(' ')
                : payload.message ?? `Permintaan gagal (HTTP ${response.status}).`;

            const error = new Error(message);
            error.validationErrors = payload.errors ?? {};
            error.status = response.status;
            throw error;
        }

        return payload;
    }

    function showAlert(message, validationErrors = null) {
        alertBox.replaceChildren();
        alertBox.classList.remove('hidden');

        const summary = document.createElement('p');
        summary.textContent = message;
        alertBox.append(summary);

        if (validationErrors && Object.keys(validationErrors).length > 0) {
            const list = document.createElement('ul');
            list.className = 'mt-2 list-inside list-disc';

            Object.entries(validationErrors).forEach(([field, messages]) => {
                messages.forEach((fieldMessage) => {
                    const item = document.createElement('li');
                    item.textContent = `${field}: ${fieldMessage}`;
                    list.append(item);
                });
            });

            alertBox.append(list);
        }
    }

    function clearAlert() {
        alertBox.replaceChildren();
        alertBox.classList.add('hidden');
    }

    function showNotice(message) {
        noticeBox.textContent = message;
        noticeBox.classList.remove('hidden');
        window.setTimeout(() => noticeBox.classList.add('hidden'), 5000);
    }

    function formatMoney(value) {
        const amount = Number(value ?? 0);

        return Number.isFinite(amount) ? packagePriceFormatter.format(amount) : '—';
    }

    function paymentBadge(status) {
        const classes = {
            'Belum Bayar': 'bg-slate-100 text-slate-600',
            DP: 'bg-amber-50 text-amber-700',
            Lunas: 'bg-emerald-50 text-emerald-700',
        };
        const label = status ?? 'Belum Bayar';

        return `<span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold ${classes[label] ?? classes['Belum Bayar']}">${escapeHtml(label)}</span>`;
    }

    function workStatusBadge(status) {
        const labels = {
            belum_dikerjakan: 'Belum Dikerjakan',
            on_progress: 'On Progress',
            selesai: 'Selesai',
        };
        const classes = {
            belum_dikerjakan: 'bg-slate-100 text-slate-600',
            on_progress: 'bg-violet-50 text-violet-700',
            selesai: 'bg-blue-50 text-blue-700',
        };
        const label = labels[status] ?? status ?? 'Belum Dikerjakan';

        return `<span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold ${classes[status] ?? classes.belum_dikerjakan}">${escapeHtml(label)}</span>`;
    }

    function formatDate(value) {
        if (!value) {
            return '—';
        }

        const date = new Date(`${value}T00:00:00`);

        return Number.isNaN(date.getTime())
            ? escapeHtml(value)
            : new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }).format(date);
    }

    function renderRows(orders) {
        if (orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="10" class="px-5 py-14 text-center"><p class="text-sm font-semibold text-slate-700">Order tidak ditemukan</p><p class="mt-1 text-xs text-slate-400">Coba ubah pencarian atau filter kamu.</p></td></tr>';
            return;
        }

        tableBody.innerHTML = orders.map((order) => {
            const customer = order.customer ?? {};
            const accountName = customer.account_name ?? '—';
            const username = customer.username ? `@${customer.username}` : '—';
            const notes = order.notes ? escapeHtml(order.notes) : '<span class="text-slate-300">—</span>';
            const orderNumber = `#${String(order.queue_number).padStart(3, '0')}`;

            return `<tr class="transition hover:bg-slate-50/70">
                <td class="whitespace-nowrap px-4 py-4 sm:px-5"><span class="rounded-lg bg-violet-50 px-2.5 py-1.5 text-xs font-bold text-violet-700">${escapeHtml(orderNumber)}</span></td>
                <td class="max-w-40 truncate px-4 py-4 font-medium text-slate-800">${escapeHtml(accountName)}</td>
                <td class="max-w-36 truncate px-4 py-4 text-slate-500">${escapeHtml(username)}</td>
                <td class="max-w-44 truncate px-4 py-4 text-slate-600">${escapeHtml(order.package_name ?? order.package?.name ?? '—')}</td>
                <td class="whitespace-nowrap px-4 py-4 text-slate-600">${formatDate(order.order_date)}</td>
                <td class="whitespace-nowrap px-4 py-4 text-slate-600">${formatDate(order.deadline)}</td>
                <td class="px-4 py-4">${paymentBadge(order.payment_status)}</td>
                <td class="max-w-48 truncate px-4 py-4 text-slate-500" title="${notes === '<span class="text-slate-300">—</span>' ? '' : escapeHtml(order.notes)}">${notes}</td>
                <td class="px-4 py-4">${workStatusBadge(order.work_status)}</td>
                <td class="whitespace-nowrap px-4 py-4">
                    <div class="flex items-center gap-1">
                        <button type="button" data-order-action="detail" data-order-id="${escapeHtml(order.queue_number)}" class="rounded-lg px-2.5 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-50">Detail</button>
                        <button type="button" data-order-action="edit" data-order-id="${escapeHtml(order.queue_number)}" class="rounded-lg px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Edit</button>
                        <button type="button" data-order-action="delete" data-order-id="${escapeHtml(order.queue_number)}" class="rounded-lg p-2 text-rose-500 hover:bg-rose-50" aria-label="Hapus order ${escapeHtml(orderNumber)}">
                            <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    function currentFilters() {
        const parameters = new URLSearchParams({
            page: String(state.page),
            per_page: '15',
            sort: 'queue_asc',
            payment_status: ordersPage.querySelector('[data-filter-payment]').value,
            work_status: ordersPage.querySelector('[data-filter-work]').value,
        });
        const search = ordersPage.querySelector('[data-filter-search]').value.trim();
        const month = ordersPage.querySelector('[data-filter-month]').value;

        if (search) {
            parameters.set('search', search);
        }

        if (month) {
            parameters.set('month', month);
        }

        return parameters;
    }

    async function loadOrders() {
        clearAlert();
        loadingIndicator.classList.remove('hidden');
        loadingIndicator.classList.add('inline-flex');

        try {
            const response = await request(`${ordersUrl}?${currentFilters()}`);
            const paginator = response.data;
            const orders = paginator.data ?? [];
            state.pages = paginator.last_page ?? 1;
            state.page = paginator.current_page ?? 1;

            renderRows(orders);
            ordersPage.querySelector('[data-order-count]').textContent = String(paginator.total ?? orders.length);
            ordersPage.querySelector('[data-active-estimate]').textContent = formatMoney(response.active_estimate_total);
            ordersPage.querySelector('[data-pagination-summary]').textContent = paginator.total
                ? `Menampilkan ${paginator.from}–${paginator.to} dari ${paginator.total} order`
                : 'Belum ada order';
            ordersPage.querySelector('[data-page-indicator]').textContent = `${state.page} / ${state.pages}`;
            ordersPage.querySelector('[data-page-previous]').disabled = state.page <= 1;
            ordersPage.querySelector('[data-page-next]').disabled = state.page >= state.pages;
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="10" class="px-5 py-12 text-center text-sm text-slate-500">Daftar order belum dapat dimuat.</td></tr>';
            showAlert(error.message, error.validationErrors);
        } finally {
            loadingIndicator.classList.add('hidden');
            loadingIndicator.classList.remove('inline-flex');
        }
    }

    function initializeMonths() {
        const monthSelect = ordersPage.querySelector('[data-filter-month]');
        const currentMonth = new Date();

        for (let offset = 0; offset < 24; offset++) {
            const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - offset, 1);
            const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            const option = document.createElement('option');
            option.value = value;
            option.textContent = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(date);
            monthSelect.append(option);
        }
    }

    async function loadFormOptions() {
        if (state.customers.length && state.packages.length) {
            return;
        }

        const [customerResponse, packageResponse] = await Promise.all([
            request(`${customersUrl}?per_page=100`),
            request(`${packagesUrl}?per_page=100&is_active=1`),
        ]);
        state.customers = customerResponse.data ?? [];
        state.packages = packageResponse.data ?? [];

        customerSelect.innerHTML = '<option value="">Pilih customer...</option>' + state.customers.map((customer) =>
            `<option value="${escapeHtml(customer.id)}">${escapeHtml(customer.account_name)} · @${escapeHtml(customer.username)} (${escapeHtml(customer.platform)})</option>`,
        ).join('');
        packageSelect.innerHTML = '<option value="">Pilih paket...</option>' +
            state.packages.map((commissionPackage) =>
                `<option value="${escapeHtml(commissionPackage.id)}" data-price="${escapeHtml(commissionPackage.default_price)}">${escapeHtml(commissionPackage.name)} · ${escapeHtml(formatMoney(commissionPackage.default_price))}</option>`,
            ).join('') +
            '<option value="custom">Paket khusus / lainnya</option>';
    }

    function setCustomerMode(mode) {
        const isNew = mode === 'new';
        ordersPage.querySelector('[data-existing-customer-fields]').classList.toggle('hidden', isNew);
        ordersPage.querySelector('[data-new-customer-fields]').classList.toggle('hidden', !isNew);
        customerSelect.required = !isNew && !formDialog.dataset.editing;
        orderForm.elements.customer_account_name.required = isNew;
        orderForm.elements.customer_username.required = isNew;
        orderForm.elements.customer_platform.required = isNew;
    }

    function setPackageSelection() {
        const customPackage = packageSelect.value === 'custom';
        const customPackageField = ordersPage.querySelector('[data-custom-package-name]');
        customPackageField.classList.toggle('hidden', !customPackage);
        orderForm.elements.package_name.required = customPackage;

        if (customPackage) {
            packagePriceInput.value ||= '0.00';
        } else {
            const selectedOption = packageSelect.selectedOptions[0];
            const defaultPrice = selectedOption?.dataset.price;

            if (defaultPrice !== undefined) {
                packagePriceInput.value = defaultPrice;
            }
        }

        updateTotalPreview();
    }

    function addAddonRow(name = '', price = '') {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-[minmax(0,1fr)_minmax(7rem,0.55fr)_auto] items-end gap-2';
        row.innerHTML = `<label class="text-xs font-medium text-slate-600">Nama add-on
                <input data-addon-name maxlength="255" value="${escapeHtml(name)}" class="mt-1.5 h-10 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
            </label>
            <label class="text-xs font-medium text-slate-600">Harga (Rp)
                <input data-addon-price type="number" min="0" step="0.01" inputmode="decimal" value="${escapeHtml(price)}" class="mt-1.5 h-10 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
            </label>
            <button type="button" data-remove-addon class="mb-0.5 rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="Hapus add-on">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true"><path d="M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>`;
        addonList.append(row);
        updateTotalPreview();
    }

    function updateTotalPreview() {
        const packagePrice = Number.parseFloat(packagePriceInput.value) || 0;
        const addonTotal = [...addonList.querySelectorAll('[data-addon-price]')]
            .reduce((total, input) => total + (Number.parseFloat(input.value) || 0), 0);

        ordersPage.querySelector('[data-total-value]').textContent = formatMoney(packagePrice + addonTotal);
    }

    function resetOrderForm() {
        orderForm.reset();
        addonList.replaceChildren();
        orderForm.elements.order_date.value = new Date().toISOString().slice(0, 10);
        orderForm.elements.work_status.value = 'belum_dikerjakan';
        ordersPage.querySelector('[data-form-errors]').classList.add('hidden');
        ordersPage.querySelector('[data-form-errors]').replaceChildren();
        formDialog.dataset.editing = '';
        formDialog.dataset.orderId = '';
        ordersPage.querySelector('[data-form-title]').textContent = 'Tambah Order';
        ordersPage.querySelector('[data-save-order]').textContent = 'Simpan Order';
        ordersPage.querySelector('[data-package-fields]').classList.remove('hidden');
        ordersPage.querySelector('[data-package-snapshot]').classList.add('hidden');
        ordersPage.querySelector('[data-addon-fields]').classList.remove('hidden');
        [...orderForm.elements.package_id.options].forEach((option) => { option.disabled = false; });
        orderForm.elements.package_id.disabled = false;
        orderForm.elements.package_price.disabled = false;
        orderForm.elements.package_id.required = false;
        orderForm.elements.package_name.required = false;
        setCustomerMode('existing');
        setPackageSelection();
    }

    function displayFormError(error) {
        const box = ordersPage.querySelector('[data-form-errors]');
        box.replaceChildren();
        box.classList.remove('hidden');
        const summary = document.createElement('p');
        summary.textContent = error.message;
        box.append(summary);

        if (error.validationErrors && Object.keys(error.validationErrors).length) {
            const list = document.createElement('ul');
            list.className = 'mt-2 list-inside list-disc';

            Object.entries(error.validationErrors).forEach(([field, messages]) => {
                messages.forEach((message) => {
                    const item = document.createElement('li');
                    item.textContent = `${field}: ${message}`;
                    list.append(item);
                });
            });

            box.append(list);
        }
    }

    async function openCreateForm() {
        try {
            clearAlert();
            await loadFormOptions();
            resetOrderForm();
            formDialog.showModal();
        } catch (error) {
            showAlert(error.message);
        }
    }

    async function openEditForm(orderId) {
        try {
            clearAlert();
            await loadFormOptions();
            const response = await request(urlFor(orderUrlTemplate, orderId));
            const order = response.data;
            resetOrderForm();
            formDialog.dataset.editing = 'true';
            formDialog.dataset.orderId = orderId;
            ordersPage.querySelector('[data-form-title]').textContent = `Edit Order #${String(order.queue_number).padStart(3, '0')}`;
            ordersPage.querySelector('[data-save-order]').textContent = 'Simpan Perubahan';
            ordersPage.querySelector('[data-package-fields]').classList.add('hidden');
            ordersPage.querySelector('[data-addon-fields]').classList.add('hidden');

            const snapshot = ordersPage.querySelector('[data-package-snapshot]');
            snapshot.textContent = `Snapshot paket: ${order.package_name} · ${formatMoney(order.package_price)}. Snapshot dan addon tidak berubah saat mengedit jadwal atau customer.`;
            snapshot.classList.remove('hidden');

            customerSelect.value = String(order.customer_id);
            orderForm.elements.order_date.value = order.order_date ?? '';
            orderForm.elements.agreed_start_date.value = order.agreed_start_date ?? '';
            orderForm.elements.actual_start_date.value = order.actual_start_date ?? '';
            orderForm.elements.deadline.value = order.deadline ?? '';
            orderForm.elements.work_status.value = order.work_status ?? 'belum_dikerjakan';
            orderForm.elements.notes.value = order.notes ?? '';
            setPackageSelection();
            formDialog.showModal();
        } catch (error) {
            showAlert(error.message, error.validationErrors);
        }
    }

    function fieldValue(name) {
        return orderForm.elements[name].value.trim();
    }

    function selectedCustomerPayload(payload) {
        if (orderForm.querySelector('input[name="customer_mode"]:checked').value === 'new') {
            payload.customer = {
                account_name: fieldValue('customer_account_name'),
                username: fieldValue('customer_username'),
                platform: fieldValue('customer_platform'),
            };
        } else if (customerSelect.value) {
            payload.customer_id = Number(customerSelect.value);
        }

        return payload;
    }

    function formPayload(isEditing) {
        let payload = {
            order_date: fieldValue('order_date'),
            agreed_start_date: fieldValue('agreed_start_date') || null,
            actual_start_date: fieldValue('actual_start_date') || null,
            deadline: fieldValue('deadline') || null,
            notes: fieldValue('notes') || null,
            work_status: fieldValue('work_status'),
        };
        payload = selectedCustomerPayload(payload);

        if (!isEditing) {
            const selectedPackage = packageSelect.value;
            payload.package_price = packagePriceInput.value;

            if (selectedPackage === 'custom') {
                payload.package_name = fieldValue('package_name');
            } else if (selectedPackage) {
                payload.package_id = Number(selectedPackage);
            }
        }

        return payload;
    }

    function selectedAddons() {
        return [...addonList.querySelectorAll('.grid')].map((row) => ({
            name: row.querySelector('[data-addon-name]').value.trim(),
            price: row.querySelector('[data-addon-price]').value,
        })).filter((addon) => addon.name && addon.price !== '');
    }

    async function saveOrder(event) {
        event.preventDefault();
        const saveButton = ordersPage.querySelector('[data-save-order]');
        const isEditing = formDialog.dataset.editing === 'true';
        saveButton.disabled = true;
        clearAlert();

        try {
            const orderId = formDialog.dataset.orderId;
            const response = await request(
                isEditing ? urlFor(orderUrlTemplate, orderId) : ordersUrl,
                {
                    method: isEditing ? 'PATCH' : 'POST',
                    body: JSON.stringify(formPayload(isEditing)),
                },
            );
            const order = response.data;

            if (!isEditing) {
                const addons = selectedAddons();

                if (addons.length > 0) {
                    try {
                        await request(urlFor(addonsUrlTemplate, order.queue_number), {
                            method: 'POST',
                            body: JSON.stringify({ addons }),
                        });
                    } catch (error) {
                        formDialog.close();
                        await loadOrders();
                        showAlert(`Order #${order.queue_number} berhasil dibuat, tetapi addon belum tersimpan: ${error.message}`, error.validationErrors);
                        return;
                    }
                }
            }

            formDialog.close();
            showNotice(isEditing ? 'Perubahan order berhasil disimpan.' : `Order #${order.queue_number} berhasil ditambahkan.`);
            await loadOrders();
        } catch (error) {
            displayFormError(error);
        } finally {
            saveButton.disabled = false;
        }
    }

    function detailValue(label, value) {
        return `<div><dt class="text-xs font-medium text-slate-400">${escapeHtml(label)}</dt><dd class="mt-1 break-words text-sm font-medium text-slate-700">${escapeHtml(value || '—')}</dd></div>`;
    }

    async function openOrderDetail(orderId) {
        try {
            clearAlert();
            const response = await request(urlFor(orderUrlTemplate, orderId));
            const order = response.data;
            state.currentOrder = order;
            ordersPage.querySelector('[data-detail-queue]').textContent = `Antrean #${String(order.queue_number).padStart(3, '0')}`;
            ordersPage.querySelector('[data-detail-title]').textContent = order.package_name ?? 'Detail order';

            const addons = order.order_addons ?? [];
            const payments = order.payments ?? [];
            const addonMarkup = addons.length
                ? addons.map((addon) => `<li class="flex justify-between gap-4 py-2 text-sm"><span class="text-slate-600">${escapeHtml(addon.name)}</span><span class="font-medium text-slate-700">${formatMoney(addon.price)}</span></li>`).join('')
                : '<li class="py-2 text-sm text-slate-400">Belum ada addon.</li>';
            const paymentMarkup = payments.length
                ? payments.map((payment) => `<li class="flex justify-between gap-4 py-2 text-sm"><span class="text-slate-600">${formatDate(payment.payment_date)}${payment.notes ? ` · ${escapeHtml(payment.notes)}` : ''}</span><span class="font-medium text-slate-700">${formatMoney(payment.amount)}</span></li>`).join('')
                : '<li class="py-2 text-sm text-slate-400">Belum ada pembayaran.</li>';
            const detail = ordersPage.querySelector('[data-order-detail]');
            detail.innerHTML = `<section class="grid gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
                    ${detailValue('Nama akun', order.customer?.account_name)}
                    ${detailValue('Username', order.customer?.username ? `@${order.customer.username}` : '—')}
                    ${detailValue('Platform', order.customer?.platform)}
                    ${detailValue('Paket snapshot', `${order.package_name} · ${formatMoney(order.package_price)}`)}
                    ${detailValue('Tanggal order', formatDate(order.order_date))}
                    ${detailValue('Mulai disepakati', formatDate(order.agreed_start_date))}
                    ${detailValue('Mulai aktual', formatDate(order.actual_start_date))}
                    ${detailValue('Deadline', formatDate(order.deadline))}
                    ${detailValue('Status pekerjaan', order.work_status)}
                </section>
                <section>
                    <h3 class="text-sm font-semibold text-slate-800">Add-on</h3>
                    <ul class="mt-2 divide-y divide-slate-100">${addonMarkup}</ul>
                </section>
                <section>
                    <h3 class="text-sm font-semibold text-slate-800">Pembayaran</h3>
                    <ul class="mt-2 divide-y divide-slate-100">${paymentMarkup}</ul>
                </section>
                <section class="grid gap-3 rounded-xl border border-violet-100 bg-violet-50/60 p-4 sm:grid-cols-3">
                    ${detailValue('Total addon', formatMoney(order.addon_total))}
                    ${detailValue('Total harga', formatMoney(order.total_price))}
                    ${detailValue('Total pembayaran', formatMoney(order.total_payment))}
                    ${detailValue('Sisa pembayaran', formatMoney(order.remaining_payment))}
                    ${detailValue('Status pembayaran', order.payment_status)}
                </section>
                <section>
                    <h3 class="text-xs font-medium text-slate-400">Catatan</h3>
                    <p class="mt-1 whitespace-pre-wrap text-sm leading-6 text-slate-700">${escapeHtml(order.notes || 'Tidak ada catatan.')}</p>
                </section>`;

            detailDialog.showModal();
        } catch (error) {
            showAlert(error.message, error.validationErrors);
        }
    }

    async function deleteOrder(orderId) {
        if (!window.confirm('Hapus order ini secara permanen? Riwayat addon dan seluruh pembayaran terkait juga akan dihapus. Tindakan ini tidak dapat dibatalkan.')) {
            return;
        }

        try {
            await request(urlFor(orderUrlTemplate, orderId), { method: 'DELETE' });
            if (detailDialog.open) {
                detailDialog.close();
            }
            state.currentOrder = null;
            showNotice('Order dan riwayat transaksinya berhasil dihapus.');
            await loadOrders();
        } catch (error) {
            showAlert(error.message, error.validationErrors);
        }
    }

    function resetFilters() {
        ordersPage.querySelector('[data-filter-search]').value = '';
        ordersPage.querySelector('[data-filter-payment]').value = 'semua';
        ordersPage.querySelector('[data-filter-work]').value = 'semua';
        ordersPage.querySelector('[data-filter-month]').value = '';
        state.page = 1;
        loadOrders();
    }

    ordersPage.querySelector('[data-filter-search]').addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(() => {
            state.page = 1;
            loadOrders();
        }, 300);
    });

    ['[data-filter-payment]', '[data-filter-work]', '[data-filter-month]'].forEach((selector) => {
        ordersPage.querySelector(selector).addEventListener('change', () => {
            state.page = 1;
            loadOrders();
        });
    });

    ordersPage.querySelector('[data-reset-filters]').addEventListener('click', resetFilters);
    ordersPage.querySelector('[data-page-previous]').addEventListener('click', () => {
        if (state.page > 1) {
            state.page--;
            loadOrders();
        }
    });
    ordersPage.querySelector('[data-page-next]').addEventListener('click', () => {
        if (state.page < state.pages) {
            state.page++;
            loadOrders();
        }
    });

    ordersPage.querySelector('[data-create-order]').addEventListener('click', openCreateForm);
    ordersPage.querySelectorAll('[data-close-form]').forEach((button) => {
        button.addEventListener('click', () => formDialog.close());
    });
    ordersPage.querySelectorAll('[data-close-detail]').forEach((button) => {
        button.addEventListener('click', () => detailDialog.close());
    });
    ordersPage.querySelectorAll('input[name="customer_mode"]').forEach((radio) => {
        radio.addEventListener('change', () => setCustomerMode(radio.value));
    });

    packageSelect.addEventListener('change', setPackageSelection);
    packagePriceInput.addEventListener('input', updateTotalPreview);
    ordersPage.querySelector('[data-add-addon]').addEventListener('click', () => addAddonRow());
    addonList.addEventListener('input', updateTotalPreview);
    addonList.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-addon]');

        if (removeButton) {
            removeButton.closest('.grid').remove();
            updateTotalPreview();
        }
    });
    orderForm.addEventListener('submit', saveOrder);

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-order-action]');

        if (!button) {
            return;
        }

        const { orderAction, orderId } = button.dataset;

        if (orderAction === 'detail') {
            openOrderDetail(orderId);
        } else if (orderAction === 'edit') {
            openEditForm(orderId);
        } else if (orderAction === 'delete') {
            deleteOrder(orderId);
        }
    });

    ordersPage.querySelector('[data-edit-detail]').addEventListener('click', () => {
        if (state.currentOrder) {
            const { queue_number: orderId } = state.currentOrder;
            detailDialog.close();
            openEditForm(orderId);
        }
    });
    ordersPage.querySelector('[data-delete-detail]').addEventListener('click', () => {
        if (state.currentOrder) {
            deleteOrder(state.currentOrder.queue_number);
        }
    });

    initializeMonths();
    loadOrders();
}
