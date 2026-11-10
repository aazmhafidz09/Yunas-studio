@extends('layouts.master')

@section('title')
    Edit Transaksi
@endsection

@section('css')
    <style>
        .price-summary-card {
            position: sticky;
            top: 80px;
        }
        .section-title {
            font-size: 1.1rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', ['title' => 'Transaksi', 'pagetitle' => 'Daftar Transaksi', 'breadcrumbs' => [['text' => 'Transaksi', 'url' => route('transaksi.index')], ['text' => 'Edit Transaksi', 'url' => '']]])
    @endcomponent

    {{-- Tampilkan Error Validasi Global jika ada --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-block-helper me-2"></i>
            <strong>Terjadi Kesalahan!</strong> Silakan periksa inputan Anda.
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('transaksi.update', $transaksi->transaction_id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="section-title"><i class="mdi mdi-file-document-edit-outline text-primary me-1"></i> Edit Transaksi ({{ $transaksi->receipt_code }})</h5>
                        
                        {{-- Customer Details Row --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="customer_name">Nama Pelanggan</label>
                                    <input type="text" class="form-control form-control-sm @error('customer_name') is-invalid @enderror" id="customer_name" name="customer_name" value="{{ old('customer_name', $transaksi->customer_name) }}" required>
                                    @error('customer_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="phone_number">Nomor Telepon (WA)</label>
                                    <input type="text" class="form-control form-control-sm @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $transaksi->phone_number) }}">
                                    @error('phone_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label d-block mb-2">Status Pembayaran</label>
                                    <div class="pt-2">
                                            @foreach(['belum dibayar', 'dp', 'sudah dibayar'] as $status)
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" id="status_{{ $loop->iteration }}" name="status" value="{{ $status }}" class="form-check-input payment-status-radio" {{ old('status', $transaksi->status) == $status ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="status_{{ $loop->iteration }}">
                                                        @if($status == 'dp') DP
                                                        @elseif($status == 'sudah dibayar') Lunas
                                                        @else Belum Dibayar
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                    </div>
                                    @error('status')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            {{-- UPGRADE: Payment Type & DP Amount Container (EDIT MODE) --}}
                            <div class="col-md-12 row" id="payment-details-container" style="display: none;">
                                <div class="col-md-6" id="dp-amount-container" style="display: none;">
                                    <div class="mb-3">
                                        <label for="dp_amount">Jumlah DP (Rp)</label>
                                        <input type="number" class="form-control form-control-sm" id="dp_amount" name="dp_amount" value="{{ old('dp_amount', $transaksi->dp_amount) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label d-block mb-2">Tipe Pembayaran</label>
                                        <div class="pt-2">
                                            @foreach(['Cash', 'Transfer/Qris'] as $type)
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" id="payment_type_{{ $loop->iteration }}" name="payment_type" value="{{ $type }}" class="form-check-input payment-type-radio" {{ old('payment_type', $transaksi->payment_type) == $type ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="payment_type_{{ $loop->iteration }}">
                                                        {{ $type }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- UPDATED: Packet and Status Row --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_id" class="form-label">Produk</label>
                                    <select class="form-select @error('product_id') is-invalid @enderror" id="product_id">
                                        <option value="" disabled selected>-- Pilih Produk --</option>
                                        @foreach($packets as $productName => $packetGroup)
                                            <option value="{{ $packetGroup->first()->product_id }}"
                                                    data-product-name="{{ $productName }}">
                                                {{ $productName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="packet_id" class="form-label">Paket</label>
                                    <select class="form-select @error('packet_id') is-invalid @enderror" id="packet_id" name="packet_id" required>
                                        <option value="" data-price="0" disabled selected>-- Pilih Paket --</option>
                                    </select>
                                    @error('packet_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="process_status" class="form-label">Status Pengerjaan</label>
                                    <select class="form-select @error('process_status') is-invalid @enderror" id="process_status" name="process_status" required>
                                        {{-- UPDATE STATUS LIST SESUAI CONTROLLER --}}
                                        @foreach ([
                                            'Pelanggan Belum Foto', 
                                            'Pelanggan Pilih Foto', 
                                            'Proses Edit', 
                                            'Proses Cetak', 
                                            'Selesai'
                                        ] as $status)
                                            <option value="{{ $status }}" {{ old('process_status', $transaksi->process_status) == $status ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('process_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="text-danger small mt-1" id="process-status-warning" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="discount-input">Diskon (Rp)</label>
                                    <input type="number" class="form-control" id="discount-input" name="discount" value="{{ old('discount', $transaksi->discount) }}" min="0">
                                </div>
                            </div>
                        </div>
                        
                        {{-- Other sections (Included Additionals, Extra Additionals, Note) --}}
                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-check-all text-primary me-1"></i> Tambahan Termasuk (Default)</h5>
                        <div id="included-additionals-container" class="mb-3"></div>

                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-plus-box-multiple text-primary me-1"></i> Tambahan Ekstra</h5>
                        <div id="extra-additionals-container" class="mb-3"></div>
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label for="add_additional_select" class="form-label">Tambah Item</label>
                                <div class="input-group">
                                    <select id="add_additional_select" class="form-select">
                                        <option value="">Pilih tambahan...</option>
                                        @foreach($all_additionals as $additional)
                                            <option value="{{ $additional->id }}" data-name="{{ $additional->name }}" data-price="{{ $additional->price }}">{{ $additional->name }} (Rp {{ number_format($additional->price, 0, ',', '.') }})</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-success" type="button" id="add-additional-btn">Tambah</button>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-pencil-outline text-primary me-1"></i> Catatan Transaksi</h5>
                        <div class="mb-3">
                            <textarea class="form-control" id="note" name="note" rows="3" placeholder="Tambahkan catatan internal untuk transaksi ini...">{{ old('note', $transaksi->note) }}</textarea>
                        </div>
                        
                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-image-multiple text-primary me-1"></i> URL Galeri Foto (Untuk Pilihan Edit & Cetak)</h5>
                        <div class="mb-3">
                            <input type="url" class="form-control form-control-sm @error('url_images') is-invalid @enderror" id="url_images" name="url_images" value="{{ old('url_images', $transaksi->url_images) }}" placeholder="https://...">
                            @error('url_images')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-image-multiple text-primary me-1"></i> URL Hasil Foto (Final Edit)</h5>
                        <div class="mb-3">
                            <input type="url" class="form-control form-control-sm @error('url_photos_result') is-invalid @enderror" id="url_photos_result" name="url_photos_result" value="{{ old('url_photos_result', $transaksi->url_photos_result) }}" placeholder="https://...">
                            @error('url_photos_result')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card price-summary-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Ringkasan Harga</h5>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <tbody>
                                    <tr><td>Harga Paket:</td><td id="summary-packet-price" class="text-end fw-bold">Rp 0</td></tr>
                                    <tr><td>Tambahan Ekstra:</td><td id="summary-additionals-price" class="text-end fw-bold">Rp 0</td></tr>
                                    <tr><td class="border-0">Subtotal:</td><td id="summary-subtotal" class="text-end fw-bold border-0">Rp 0</td></tr>
                                    <tr><td class="border-0 pt-0">Diskon:</td><td id="summary-discount" class="border-0 pt-0 text-end text-danger">- Rp 0</td></tr>
                                    <tr class="bg-light" id="summary-total-row"><th class="fs-5">Total Harga:</th><th id="summary-total-price" class="text-end fs-5">Rp 0</th></tr>
                                    <tr id="summary-dp-row" style="display: none;"><td class="fw-bold">DP Terbayar:</td><td id="summary-dp-paid" class="text-end fw-bold">Rp 0</td></tr>
                                    <tr class="bg-light" id="summary-remaining-row" style="display: none;"><th class="fs-5">Sisa Tagihan:</th><th id="summary-remaining-balance" class="text-end fs-5">Rp 0</th></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light">Simpan Perubahan</button>
                        </div>
                        <a href="{{ route('transaksi.index') }}" class="btn btn-light d-block mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form Elements
    const productSelect = document.getElementById('product_id');
    const packetSelect = document.getElementById('packet_id');
    const discountInput = document.getElementById('discount-input');
    const includedContainer = document.getElementById('included-additionals-container');
    const extraContainer = document.getElementById('extra-additionals-container');
    const addSelect = document.getElementById('add_additional_select');
    const addBtn = document.getElementById('add-additional-btn');
    const statusRadios = document.querySelectorAll('.payment-status-radio');
    const dpAmountContainer = document.getElementById('dp-amount-container');
    const dpAmountInput = document.getElementById('dp_amount');
    const urlInput = document.getElementById('url_images');
    const urlFinalInput = document.getElementById('url_photos_result');
    const processSelect = document.getElementById('process_status');
    const warningText = document.getElementById('process-status-warning');
    const paymentDetailsContainer = document.getElementById('payment-details-container');
    const paymentTypeRadios = document.querySelectorAll('.payment-type-radio');
    const canPrint = @json($canPrint); // Value from backend

    const summary = {
        packetEl: document.getElementById('summary-packet-price'),
        additionalsEl: document.getElementById('summary-additionals-price'),
        subtotalEl: document.getElementById('summary-subtotal'),
        discountEl: document.getElementById('summary-discount'),
        totalPriceEl: document.getElementById('summary-total-price'),
        dpPaidEl: document.getElementById('summary-dp-paid'),
        remainingBalanceEl: document.getElementById('summary-remaining-balance'),
        totalRow: document.getElementById('summary-total-row'),
        dpRow: document.getElementById('summary-dp-row'),
        remainingRow: document.getElementById('summary-remaining-row'),
    };

    const productPackets = {!! json_encode($packets) !!};
    const existingExtraAdditionals = @json($transaksi->additionals->mapWithKeys(function ($item) {
        return [$item->id => ['name' => $item->name, 'price' => $item->pivot->price, 'quantity' => $item->pivot->quantity]];
    }));

    const formatCurrency = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

    function updateSummary() {
        const selectedPacketOption = packetSelect.options[packetSelect.selectedIndex];
        const packetPrice = parseFloat(selectedPacketOption ? selectedPacketOption.dataset.price : 0) || 0;

        let extraAdditionalsPrice = 0;
        document.querySelectorAll('.extra-additional-row').forEach(row => {
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
            extraAdditionalsPrice += price * qty;
        });

        const subtotal = packetPrice + extraAdditionalsPrice;
        const discount = parseFloat(discountInput.value) || 0;
        const total = subtotal - discount > 0 ? subtotal - discount : 0;
        const dpAmount = parseFloat(dpAmountInput.value) || 0;
        const remaining = total - dpAmount;
        const selectedStatus = document.querySelector('.payment-status-radio:checked').value;

        summary.packetEl.textContent = formatCurrency(packetPrice);
        summary.additionalsEl.textContent = formatCurrency(extraAdditionalsPrice);
        summary.subtotalEl.textContent = formatCurrency(subtotal);
        summary.discountEl.textContent = `- ${formatCurrency(discount)}`;
        summary.totalPriceEl.textContent = formatCurrency(total);

        if (selectedStatus === 'dp') {
            summary.totalRow.style.display = 'none';
            summary.dpRow.style.display = '';
            summary.remainingRow.style.display = '';
            summary.dpPaidEl.textContent = formatCurrency(dpAmount);
            summary.remainingBalanceEl.textContent = formatCurrency(remaining > 0 ? remaining : 0);
        } else {
            summary.totalRow.style.display = '';
            summary.dpRow.style.display = 'none';
            summary.remainingRow.style.display = 'none';
        }
    }

    function addExtraRow(id, name, price, quantity) {
        if (document.getElementById(`extra-additional-row-${id}`)) return;
        const template = `
            <div class="row align-items-center mb-2 extra-additional-row" id="extra-additional-row-${id}">
                <div class="col-md-5"><input type="text" class="form-control" value="${name}" readonly></div>
                <div class="col-md-3"><div class="input-group"><span class="input-group-text">Rp</span><input type="number" name="additionals[${id}][price]" class="form-control price-input" value="${price}" readonly></div></div>
                <div class="col-md-2"><input type="number" name="additionals[${id}][quantity]" class="form-control quantity-input" value="${quantity}" min="1"></div>
                <div class="col-md-2"><button type="button" class="btn btn-sm btn-danger remove-additional-btn w-100">X</button></div>
            </div>`;
        if (extraContainer.innerHTML.includes('No extra additionals added.')) extraContainer.innerHTML = '';
        extraContainer.insertAdjacentHTML('beforeend', template);
    }

    function togglePaymentFields() {
        const selectedStatus = document.querySelector('.payment-status-radio:checked')?.value;
        
        if (selectedStatus === 'belum dibayar') {
            paymentDetailsContainer.style.display = 'none';
            // Only clear values if changing TO 'belum dibayar' from something else
            // dpAmountInput.value = ''; // Optional: decide if you want to clear or keep hidden
            // paymentTypeRadios.forEach(radio => radio.checked = false);
        } else {
            paymentDetailsContainer.style.display = 'flex'; // Use flex because it's a row
            
            if (selectedStatus === 'dp') {
                 dpAmountContainer.style.display = 'block';
            } else { // lunas
                 dpAmountContainer.style.display = 'none';
            }
        }
        updateSummary();
    }

    function fetchAndDisplayDefaults() {
        const packetId = packetSelect.value;
        includedContainer.innerHTML = '<p class="text-muted">Memuat...</p>';
        if (!packetId) {
            includedContainer.innerHTML = '<p class="text-muted">Pilih paket untuk melihat item yang termasuk.</p>';
            return;
        }

        fetch(`/packets/${packetId}/default-additionals`)
            .then(response => response.json())
            .then(data => {
                includedContainer.innerHTML = '';
                if (data.length === 0) {
                    includedContainer.innerHTML = '<p class="text-muted">Paket ini tidak memiliki tambahan termasuk.</p>';
                } else {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'included-item d-inline-block border rounded-pill px-2 py-1 me-2 mb-2';
                        div.textContent = `${item.quantity}x ${item.name}`;
                        includedContainer.appendChild(div);
                    });
                }
            })
            .catch(() => includedContainer.innerHTML = '<p class="text-danger">Gagal memuat item termasuk.</p>');
    }

    // --- LOGIC BARU: Toggle Status Dropdown berdasarkan URL & Payment Status ---
    function checkUrlStatus() {
        const hasUrl = urlInput.value.trim() !== '';
        const hasUrlFinal = urlFinalInput.value.trim() !== '';
        const paymentStatus = document.querySelector('.payment-status-radio:checked').value;
        const options = processSelect.options;
        const currentStatus = processSelect.value; // Status saat ini sebelum diubah

        warningText.style.display = 'none';
        warningText.textContent = '';

        for (let i = 0; i < options.length; i++) {
            const status = options[i].value;
            let disabled = false;
            let suffix = '';

            // Reset text option (hapus suffix lama)
            // options[i].text = status; // Dont reset here to keep original logic simple

            // LOGIC DISABLED STATUS
            if (status === 'Pelanggan Pilih Foto' && !hasUrl) {
                disabled = true;
                suffix = ' (Isi Link Galeri)';
            }
            if (status === 'Proses Cetak') {
                if (!canPrint) {
                    disabled = true;
                    suffix = ' (Tidak Ada Cetak)';
                } else if (!hasUrlFinal) {
                    disabled = true;
                    suffix = ' (Isi Link Final)';
                }
            }
            if (status === 'Selesai') {
                if (paymentStatus !== 'sudah dibayar') {
                    disabled = true;
                    suffix = ' (Belum Lunas)';
                } else if (!hasUrlFinal) {
                    disabled = true;
                    suffix = ' (Isi Link Final)';
                }
            }

            options[i].disabled = disabled;
            options[i].text = status + suffix;
        }

        // Tampilkan warning jika status saat ini menjadi disabled setelah pengecekan
        const currentOption = processSelect.querySelector(`option[value="${currentStatus}"]`);
        if (currentOption && currentOption.disabled) {
            warningText.textContent = 'Status saat ini tidak valid dengan data yang ada. Harap lengkapi data atau ubah status.';
            warningText.style.display = 'block';
        }
    }

    // Event Listener untuk input yang mempengaruhi status
    urlInput.addEventListener('input', checkUrlStatus);
    urlFinalInput.addEventListener('input', checkUrlStatus);
    statusRadios.forEach(radio => radio.addEventListener('change', () => {
        togglePaymentFields();
        checkUrlStatus();
    }));

    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const productName = selectedOption ? selectedOption.dataset.productName : null;
        packetSelect.innerHTML = '<option value="" data-price="0" disabled selected>-- Pilih Paket --</option>';

        if (productName && productPackets[productName]) {
            productPackets[productName].forEach(packet => {
                const option = document.createElement('option');
                option.value = packet.id;
                option.textContent = `${packet.name} (Rp ${new Intl.NumberFormat('id-ID').format(packet.price)})`;
                option.dataset.price = packet.price;
                packetSelect.appendChild(option);
            });
            packetSelect.disabled = false;
        } else {
            packetSelect.disabled = true;
        }
        fetchAndDisplayDefaults();
        updateSummary();
    });

    packetSelect.addEventListener('change', () => {
        fetchAndDisplayDefaults();
        updateSummary();
    });

    addBtn.addEventListener('click', () => {
        const selected = addSelect.options[addSelect.selectedIndex];
        if (!selected.value) return;
        addExtraRow(selected.value, selected.dataset.name, selected.dataset.price, 1);
        updateSummary();
        addSelect.value = '';
    });

    extraContainer.addEventListener('click', e => {
        if (e.target.matches('.remove-additional-btn, .remove-additional-btn *')) {
            e.target.closest('.extra-additional-row').remove();
            updateSummary();
        }
    });

    extraContainer.addEventListener('input', e => {
        if (e.target.classList.contains('quantity-input')) updateSummary();
    });
    
    discountInput.addEventListener('input', updateSummary);
    dpAmountInput.addEventListener('input', updateSummary);

    // --- INITIALIZATION FOR EDIT PAGE ---
    function initializeEditForm() {
        const initialProductId = "{{ $transaksi->packet->product_id ?? '' }}";
        const initialPacketId = "{{ $transaksi->packet_id ?? '' }}";

        // Populate existing extra additionals
        const existingExtraAdditionals = @json($transaksi->additionals->mapWithKeys(function ($item) {
            return [$item->id => ['name' => $item->name, 'price' => $item->pivot->price, 'quantity' => $item->pivot->quantity]];
        }));
        
        for (const id in existingExtraAdditionals) {
            const item = existingExtraAdditionals[id];
            addExtraRow(id, item.name, item.price, item.quantity);
        }

        if (initialProductId) {
            productSelect.value = initialProductId;
            
            // Manually trigger the change event to populate packets
            // We need to re-create the logic because dispatchEvent might not work if listeners aren't ready
             const productName = productSelect.options[productSelect.selectedIndex].dataset.productName;
             const packets = productPackets[productName];

             packets.forEach(packet => {
                const option = document.createElement('option');
                option.value = packet.id;
                option.textContent = `${packet.name} (Rp ${new Intl.NumberFormat('id-ID').format(packet.price)})`;
                option.dataset.price = packet.price;
                packetSelect.appendChild(option);
            });
            packetSelect.disabled = false;
            
            // Set the selected packet after packets are populated
            packetSelect.value = initialPacketId;
        }
        
        togglePaymentFields();
        fetchAndDisplayDefaults(); // This will also trigger updateSummary
        checkUrlStatus(); // Jalankan cek status saat inisialisasi halaman
    }

    initializeEditForm();
});
</script>
@endsection