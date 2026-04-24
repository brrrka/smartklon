@extends('layouts.app')

@section('title', 'Ketersediaan Stok Produk')
@section('page-title', 'Ketersediaan Stok Produk')

@section('content')

{{-- ================================================================
     FLOATING SCAN OVERLAY (posisi fixed top, bukan full page)
     Berlaku untuk: single_in, batch_in, out, check_stock
     ================================================================ --}}
<div id="scan-toast" class="scan-toast" style="display:none;" role="status" aria-live="polite">
    <div class="scan-toast-inner">
        {{-- Kiri: animasi + info produk --}}
        <div class="scan-toast-left">
            <div class="scan-toast-radar">
                <div class="radar-sm-ring ring-1"></div>
                <div class="radar-sm-ring ring-2"></div>
                <div class="radar-sm-center" id="toast-mode-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="white" stroke-width="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="white" stroke-width="2"/></svg>
                </div>
            </div>
            <div class="scan-toast-info">
                <div class="scan-toast-mode-label" id="toast-mode-label">Scanning…</div>
                <div class="scan-toast-product">
                    <strong id="toast-product-name">—</strong>
                    <span class="badge badge--code" id="toast-product-code">—</span>
                </div>
            </div>
        </div>

        {{-- Tengah: real-time stats --}}
        <div class="scan-toast-stats">
            <div class="scan-toast-stat">
                <span class="scan-toast-stat-val scan-toast-stat-val--green" id="toast-in-count">—</span>
                <span class="scan-toast-stat-label">In Stock</span>
            </div>
            <div class="scan-toast-divider"></div>
            <div class="scan-toast-stat">
                <span class="scan-toast-stat-val scan-toast-stat-val--red" id="toast-out-count">—</span>
                <span class="scan-toast-stat-label">Keluar</span>
            </div>
            <div class="scan-toast-divider"></div>
            <div class="scan-toast-stat">
                <span class="scan-toast-stat-val" id="toast-pct">—</span>
                <span class="scan-toast-stat-label">Tersedia</span>
            </div>
        </div>

        {{-- Kanan: countdown + actions --}}
        <div class="scan-toast-right">
            <div class="scan-toast-countdown" id="toast-countdown-wrap">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span id="toast-countdown">30</span>s
            </div>
            <div class="scan-toast-actions">
                <button class="btn-toast-done" id="toast-done-btn" onclick="finishScan()">Selesai</button>
                <button class="btn-toast-cancel" onclick="cancelScan()">✕</button>
            </div>
        </div>
    </div>

    {{-- Progress bar detik --}}
    <div class="scan-toast-progress">
        <div class="scan-toast-progress-bar" id="toast-progress-bar"></div>
    </div>
</div>

{{-- ================================================================
     MODAL PILIH PRODUK (untuk Batch Masuk)
     ================================================================ --}}
<div id="batch-modal-overlay" class="batch-modal-overlay" style="display:none;" onclick="closeBatchModal(event)">
    <div class="batch-modal" role="dialog" aria-modal="true">
        <div class="batch-modal-header">
            <div class="batch-modal-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div>
                <h3 class="batch-modal-title">Batch Masuk — Pilih Produk Target</h3>
                <p class="batch-modal-sub">Scanner akan aktif terus sampai kamu klik Selesai</p>
            </div>
            <button class="batch-modal-close" onclick="closeBatchModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/><line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/></svg>
            </button>
        </div>
        <div class="batch-modal-body">
            <label class="form-label" for="batch-product-select">Produk Target</label>
            <select class="form-select" id="batch-product-select">
                <option value="">-- Pilih produk --</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" data-kode="{{ $item->kode_barang }}" data-nama="{{ $item->nama_barang }}">
                        {{ $item->kode_barang }} — {{ $item->nama_barang }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="batch-modal-footer">
            <button class="btn btn--secondary" onclick="closeBatchModal()">Batal</button>
            <button class="btn btn--primary" onclick="startBatchScan()" id="batch-start-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><polygon points="5 3 19 12 5 21 5 3" fill="currentColor"/></svg>
                Mulai Scan
            </button>
        </div>
    </div>
</div>

{{-- ================================================================
     STAT CARDS — 4 kolom (+ Transaksi Hari Ini)
     ================================================================ --}}
<div class="stats-grid" id="stats-grid">
    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" stroke="currentColor" stroke-width="2"/><line x1="7" y1="7" x2="7.01" y2="7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value">{{ $totalSku }}</span>
            <span class="stat-card-label">Total SKU Produk</span>
        </div>
        <div class="stat-card-trend stat-card-trend--neutral">Katalog aktif</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 6 23 6 23 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value" id="stock-in-count">{{ $totalInStock }}</span>
            <span class="stat-card-label">Unit Tersedia</span>
        </div>
        <div class="stat-card-trend stat-card-trend--up">In Stock</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--red">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 18 23 18 23 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value" id="stock-out-count">{{ $totalOutOfStock }}</span>
            <span class="stat-card-label">Unit Keluar</span>
        </div>
        <div class="stat-card-trend stat-card-trend--down">Out of Stock</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--amber">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value" id="today-total">{{ $stockInToday + $stockOutToday }}</span>
            <span class="stat-card-label">Transaksi Hari Ini</span>
        </div>
        <div class="stat-card-trend">
            <span style="color:var(--green-500)" id="today-in">+{{ $stockInToday }}</span>&nbsp;/&nbsp;<span style="color:var(--red-500)" id="today-out">-{{ $stockOutToday }}</span>
        </div>
    </div>
</div>

{{-- ================================================================
     ROW 2: PANEL INTERAKSI (kiri) + LOG SCAN REAL-TIME (kanan)
     ================================================================ --}}
<div class="stock-control-row">

    {{-- ===== PANEL INTERAKSI (kiri) ===== --}}
    <div class="card stock-control-panel">
        <div class="card-header">
            <div class="card-header-left">
                <h2 class="card-title">
                    <span class="pulse-dot" id="scanner-pulse"></span>
                    Mode Scanner
                </h2>
                <span class="scanner-mode-badge" id="scanner-mode-text">Memuat…</span>
            </div>
        </div>
        <div class="card-body">
            {{-- Mode buttons --}}
            <div class="scanner-mode-buttons">
                <button class="btn-mode" id="mode-idle" onclick="setMode('idle')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07" stroke="currentColor" stroke-width="2"/></svg>
                    Idle
                </button>
                <button class="btn-mode btn-mode--in" id="mode-batch-in" onclick="openBatchModal()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Batch Masuk
                </button>
                <button class="btn-mode btn-mode--out" id="mode-out" onclick="showOutToast()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="12 19 19 12 12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Stok Keluar
                </button>
                <button class="btn-mode btn-mode--check" id="mode-check" onclick="showCheckToast()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Cek Stok
                </button>
            </div>

            <div class="scanner-status-bar" id="scanner-status-bar">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span id="scanner-status-text">Memuat status…</span>
            </div>
        </div>

        {{-- Collapsible: Tambah Produk ke Katalog --}}
        <div class="panel-divider"></div>
        <div class="card-header" style="cursor:pointer;padding:14px 20px;" onclick="toggleAddForm()">
            <div class="card-header-left">
                <h2 class="card-title" style="font-size:13px;font-weight:600;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="margin-right:4px;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" stroke="currentColor" stroke-width="2"/></svg>
                    Tambah Produk ke Katalog
                </h2>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" id="toggle-add-icon" style="transition:transform .25s;flex-shrink:0"><polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="card-body" id="add-form-body" style="display:none;padding-top:0;">
            <p class="text-sm text-muted" style="margin-bottom:12px;">Untuk mendaftarkan <strong>jenis produk baru</strong>. Tambah unit fisik lewat tombol "＋ 1 Unit" di tabel.</p>
            <form method="POST" action="{{ route('stock.items.store') }}">
                @csrf
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label" for="kode_barang">Kode Barang</label>
                        <input type="text" id="kode_barang" name="kode_barang" class="form-input {{ $errors->has('kode_barang') ? 'form-input--error' : '' }}" placeholder="BRG-006" value="{{ old('kode_barang') }}" required>
                        @error('kode_barang')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nama_barang">Nama Barang</label>
                        <input type="text" id="nama_barang" name="nama_barang" class="form-input {{ $errors->has('nama_barang') ? 'form-input--error' : '' }}" placeholder="Nama produk" value="{{ old('nama_barang') }}" required>
                        @error('nama_barang')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="satuan">Satuan</label>
                        <input type="text" id="satuan" name="satuan" class="form-input" placeholder="pcs" value="{{ old('satuan', 'pcs') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="deskripsi">Deskripsi</label>
                        <input type="text" id="deskripsi" name="deskripsi" class="form-input" placeholder="Opsional" value="{{ old('deskripsi') }}">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary" style="font-size:13px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== LOG SCAN REAL-TIME (kanan) ===== --}}
    <div class="card stock-log-panel">
        <div class="card-header">
            <div class="card-header-left">
                <h2 class="card-title">
                    <span class="pulse-dot pulse-dot--green" id="log-pulse"></span>
                    Log Scan Real-Time
                </h2>
                <span class="badge badge--live" id="log-live-badge" style="display:none;">LIVE</span>
                <span class="badge badge--offline" id="log-offline-badge" style="display:none;">OFFLINE</span>
            </div>
            <button onclick="clearScanLog()" style="font-size:11px;padding:3px 8px;border:1px solid var(--grey-200);border-radius:5px;background:#fff;cursor:pointer;color:var(--grey-500);">Bersihkan</button>
        </div>
        <div class="card-body" style="padding:12px 16px;flex:1;">
            <div id="stock-scan-log" class="stock-scan-log">
                <div class="realtime-empty" id="log-empty-state">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" opacity="0.25"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <span id="log-empty-text">Menunggu koneksi WebSocket…</span>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /stock-control-row --}}

{{-- ================================================================
     TABEL STOK PRODUK
     ================================================================ --}}
<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <h2 class="card-title">Daftar Stok Produk</h2>
        </div>
        <div class="stock-table-filters">
            {{-- Search --}}
            <div class="search-wrapper">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input type="text" class="search-input" id="stock-search" placeholder="Cari produk…" oninput="applyFilters()">
            </div>
            {{-- Filter dropdown --}}
            <select class="filter-select" id="stock-filter" onchange="applyFilters()">
                <option value="all">Semua</option>
                <option value="in_stock">In Stock > 0</option>
                <option value="empty">Stok Kosong</option>
            </select>
            {{-- Sort dropdown --}}
            <select class="filter-select" id="stock-sort" onchange="applyFilters()">
                <option value="name_asc">Nama A–Z</option>
                <option value="in_desc">In Stock ↓</option>
                <option value="in_asc">In Stock ↑</option>
                <option value="pct_desc">Ketersediaan ↓</option>
                <option value="pct_asc">Ketersediaan ↑</option>
            </select>
        </div>
    </div>
    <div class="card-body card-body--no-padding">
        <table class="data-table" id="stock-table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th>Satuan</th>
                    <th>In Stock</th>
                    <th>Out</th>
                    <th>Total</th>
                    <th>Ketersediaan</th>
                    <th style="width:100px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="stock-table-body">
                @forelse($items as $item)
                @php
                    $pct = $item->total_tags_count > 0 ? round(($item->in_stock_count / $item->total_tags_count) * 100) : 0;
                    $barClass = $pct > 60 ? 'bar--green' : ($pct > 30 ? 'bar--amber' : 'bar--red');
                @endphp
                <tr class="stock-row" id="row-{{ $item->id }}"
                    data-item-id="{{ $item->id }}"
                    data-name="{{ strtolower($item->nama_barang) }}"
                    data-kode="{{ strtolower($item->kode_barang) }}"
                    data-in="{{ $item->in_stock_count }}"
                    data-pct="{{ $pct }}">
                    <td>
                        <button class="expand-btn" onclick="toggleTagList({{ $item->id }})" id="expand-btn-{{ $item->id }}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" class="expand-icon" id="expand-icon-{{ $item->id }}">
                                <polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </td>
                    <td><span class="badge badge--code">{{ $item->kode_barang }}</span></td>
                    <td class="font-medium">{{ $item->nama_barang }}</td>
                    <td class="text-muted text-sm">{{ $item->satuan }}</td>
                    <td><span class="count-pill count-pill--green" id="in-count-{{ $item->id }}">{{ $item->in_stock_count }}</span></td>
                    <td><span class="count-pill count-pill--red" id="out-count-{{ $item->id }}">{{ $item->out_stock_count }}</span></td>
                    <td class="text-muted text-sm" id="total-count-{{ $item->id }}">{{ $item->total_tags_count }}</td>
                    <td>
                        <div class="mini-bar-wrap">
                            <div class="mini-bar"><div class="mini-bar-fill {{ $barClass }}" id="bar-fill-{{ $item->id }}" style="width:{{ $pct }}%"></div></div>
                            <span class="mini-bar-pct" id="bar-pct-{{ $item->id }}">{{ $pct }}%</span>
                        </div>
                    </td>
                    <td>
                        <button class="btn-add-unit"
                            onclick="startSingleIn({{ $item->id }}, '{{ addslashes($item->nama_barang) }}', '{{ $item->kode_barang }}')"
                            title="Tambah 1 unit via RFID">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            1 Unit
                        </button>
                    </td>
                </tr>
                <tr class="tag-list-row" id="tags-{{ $item->id }}" style="display:none;">
                    <td colspan="9" class="tag-list-cell">
                        <div class="tag-list-inner" id="tag-list-inner-{{ $item->id }}">
                            <div class="tag-list-loading"><div class="spinner"></div><span>Memuat…</span></div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted" style="padding:32px;">Belum ada produk. Tambahkan via panel di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ════════════════════════════════════════════════════════════
// STATE
// ════════════════════════════════════════════════════════════
let activeMode     = 'idle';       // current scanner mode
let activeItemId   = null;         // item target aktif
let activeItemName = '';
let activeItemCode = '';
let scanToastTimer = null;
let toastSeconds   = 30;
let wsConnected    = false;

// ════════════════════════════════════════════════════════════
// WEBSOCKET STATUS — gated LIVE/OFFLINE badge
// ════════════════════════════════════════════════════════════
window.Echo.connector.pusher.connection.bind('connected', () => {
    wsConnected = true;
    document.getElementById('log-live-badge').style.display    = '';
    document.getElementById('log-offline-badge').style.display = 'none';
    const emptyText = document.getElementById('log-empty-text');
    if (emptyText) emptyText.textContent = 'Menunggu scan RFID… Log akan muncul di sini secara real-time';
    const pulse = document.getElementById('log-pulse');
    if (pulse) pulse.classList.add('pulse-dot--green');
});
window.Echo.connector.pusher.connection.bind('disconnected', () => {
    wsConnected = false;
    document.getElementById('log-live-badge').style.display    = 'none';
    document.getElementById('log-offline-badge').style.display = '';
    const emptyText = document.getElementById('log-empty-text');
    if (emptyText) emptyText.textContent = 'WebSocket terputus. Periksa koneksi Reverb.';
    const pulse = document.getElementById('log-pulse');
    if (pulse) { pulse.classList.remove('pulse-dot--green'); pulse.style.background = 'var(--grey-300)'; }
});

// ════════════════════════════════════════════════════════════
// SCANNER STATE
// ════════════════════════════════════════════════════════════
async function loadScannerState() {
    try {
        const r = await fetch('{{ route("scanner.mode.current") }}');
        const d = await r.json();
        if (d) {
            activeMode = d.active_mode;
            activeItemId = d.target_item_id;
            applyModeUI(d.active_mode, d.target_item_id);
        }
    } catch {}
}

async function setMode(mode, targetId = null) {
    try {
        const r = await fetch('{{ route("scanner.mode.update") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ active_mode: mode, target_item_id: targetId }),
        });
        const d = await r.json();
        if (d.success) { activeMode = mode; activeItemId = targetId; applyModeUI(mode, targetId); }
    } catch {}
}

function applyModeUI(mode, targetItemId) {
    document.querySelectorAll('.btn-mode').forEach(b => b.classList.remove('btn-mode--active'));
    const map = { idle:'mode-idle', batch_in:'mode-batch-in', out:'mode-out', check_stock:'mode-check' };
    const btn = document.getElementById(map[mode] || map[activeMode]);
    if (btn) btn.classList.add('btn-mode--active');

    const labels = { idle:'Idle (Scanner OFF)', batch_in:'Batch Masuk', single_in:'Single – 1 Tag', out:'Stok Keluar', check_stock:'Cek Stok' };
    document.getElementById('scanner-mode-text').textContent = labels[mode] || mode;

    const msgs = {
        idle:        '⏸ Scanner idle — semua scan diabaikan.',
        batch_in:    '📦 Batch Masuk aktif — scan terus sampai klik Selesai.',
        single_in:   '📥 Single Masuk — menunggu 1 scan, lalu otomatis idle.',
        out:         '📤 Stok Keluar — scan tag untuk mencatat pengambilan.',
        check_stock: '🔍 Cek Stok — scan tag untuk lihat info tanpa mutasi.',
    };
    document.getElementById('scanner-status-text').textContent = msgs[mode] || mode;

    const pulse = document.getElementById('scanner-pulse');
    if (pulse) {
        const colors = { idle:'', batch_in:'pulse-dot--green', single_in:'pulse-dot--blue', out:'pulse-dot--red', check_stock:'pulse-dot--blue' };
        pulse.className = 'pulse-dot ' + (colors[mode] || '');
    }
}

// ════════════════════════════════════════════════════════════
// FLOATING TOAST OVERLAY
// ════════════════════════════════════════════════════════════
const TOAST_SECONDS = 30; // single_in/ out / check_stock timeout

function showToast(mode, itemId, itemName, itemCode) {
    activeItemId   = itemId;
    activeItemName = itemName;
    activeItemCode = itemCode;

    // Ambil stats dari row
    const inEl  = document.getElementById('in-count-'  + itemId);
    const outEl = document.getElementById('out-count-' + itemId);
    const pctEl = document.getElementById('bar-pct-'   + itemId);

    document.getElementById('toast-product-name').textContent  = itemName || '—';
    document.getElementById('toast-product-code').textContent  = itemCode || '—';
    document.getElementById('toast-in-count').textContent      = inEl  ? inEl.textContent  : '—';
    document.getElementById('toast-out-count').textContent     = outEl ? outEl.textContent : '—';
    document.getElementById('toast-pct').textContent           = pctEl ? pctEl.textContent : '—';

    const modeLabels = { single_in: '📥 Single Masuk', batch_in: '📦 Batch Masuk', out: '📤 Stok Keluar', check_stock: '🔍 Cek Stok' };
    document.getElementById('toast-mode-label').textContent = modeLabels[mode] || mode;

    // Batch mode: no countdown
    const isIndefinite = mode === 'batch_in' || mode === 'out' || mode === 'check_stock';
    const countdownWrap = document.getElementById('toast-countdown-wrap');
    if (isIndefinite) {
        countdownWrap.style.display = 'none';
        document.getElementById('toast-progress-bar').style.width = '100%';
        document.getElementById('toast-progress-bar').style.transition = 'none';
    } else {
        countdownWrap.style.display = 'flex';
        startToastCountdown(TOAST_SECONDS);
    }

    document.getElementById('scan-toast').style.display = 'flex';
    setTimeout(() => document.getElementById('scan-toast').classList.add('scan-toast--visible'), 10);
}

function startToastCountdown(secs) {
    clearInterval(scanToastTimer);
    toastSeconds = secs;
    const bar = document.getElementById('toast-progress-bar');
    bar.style.transition = 'none';
    bar.style.width = '100%';
    void bar.offsetWidth;
    bar.style.transition = `width ${secs}s linear`;
    bar.style.width = '0%';

    document.getElementById('toast-countdown').textContent = secs;
    scanToastTimer = setInterval(() => {
        toastSeconds--;
        const el = document.getElementById('toast-countdown');
        if (el) el.textContent = toastSeconds;
        if (toastSeconds <= 0) cancelScan();
    }, 1000);
}

function updateToastStats(inCount, outCount, pct) {
    document.getElementById('toast-in-count').textContent  = inCount;
    document.getElementById('toast-out-count').textContent = outCount;
    document.getElementById('toast-pct').textContent       = pct + '%';
}

function hideToast() {
    clearInterval(scanToastTimer);
    const toast = document.getElementById('scan-toast');
    toast.classList.remove('scan-toast--visible');
    setTimeout(() => { toast.style.display = 'none'; }, 300);
}

async function finishScan() {
    hideToast();
    await setMode('idle');
}

async function cancelScan() {
    hideToast();
    if (activeMode !== 'idle') await setMode('idle');
}

// ════════════════════════════════════════════════════════════
// SINGLE IN
// ════════════════════════════════════════════════════════════
async function startSingleIn(itemId, itemName, itemCode) {
    await setMode('single_in', itemId);
    showToast('single_in', itemId, itemName, itemCode);
}

// ════════════════════════════════════════════════════════════
// BATCH MODAL
// ════════════════════════════════════════════════════════════
function openBatchModal() {
    document.getElementById('batch-modal-overlay').style.display = 'flex';
    setTimeout(() => document.getElementById('batch-modal-overlay').classList.add('batch-modal-overlay--visible'), 10);
}

function closeBatchModal(e) {
    if (e && e.target !== document.getElementById('batch-modal-overlay')) return;
    document.getElementById('batch-modal-overlay').classList.remove('batch-modal-overlay--visible');
    setTimeout(() => { document.getElementById('batch-modal-overlay').style.display = 'none'; }, 250);
}

async function startBatchScan() {
    const sel    = document.getElementById('batch-product-select');
    const itemId = sel.value;
    if (!itemId) { sel.style.borderColor = 'var(--red-400)'; setTimeout(() => sel.style.borderColor = '', 1200); return; }

    const opt    = sel.options[sel.selectedIndex];
    const name   = opt.getAttribute('data-nama') || opt.text;
    const code   = opt.getAttribute('data-kode') || '';

    closeBatchModal();
    await setMode('batch_in', itemId);

    activeItemId   = itemId;
    activeItemName = name;
    activeItemCode = code;

    showToast('batch_in', itemId, name, code);
}

// ════════════════════════════════════════════════════════════
// OUT / CHECK_STOCK mode — show toast immediately, no item specific
// ════════════════════════════════════════════════════════════
const origSetMode = setMode;
document.getElementById('mode-out').addEventListener('click', async () => {
    await setMode('out');
    showToast('out', null, 'Semua Produk', '');
});
document.getElementById('mode-check').addEventListener('click', async () => {
    await setMode('check_stock');
    showToast('check_stock', null, 'Semua Produk', '');
});

// ════════════════════════════════════════════════════════════
// WEBSOCKET EVENT — master handler
// ════════════════════════════════════════════════════════════
window.addEventListener('rfid-scanned', function(e) {
    const data   = e.detail;
    const itemId = parseInt(data.item_id);
    const type   = data.transaction_type;

    // 1. Jika single_in berhasil untuk item target → tutup toast
    if (activeMode === 'single_in' && activeItemId && itemId === parseInt(activeItemId) && type === 'in') {
        hideToast();
        setTimeout(() => applyModeUI('idle', null), 400);
    }

    // 2. Update scan log
    addScanLog(data);

    // 3. Update stat cards
    const inEl  = document.getElementById('stock-in-count');
    const outEl = document.getElementById('stock-out-count');
    if (type === 'in')  { if (inEl) { inEl.textContent  = parseInt(inEl.textContent) + 1; flashEl(inEl); } }
    if (type === 'out') {
        if (outEl) { outEl.textContent = parseInt(outEl.textContent) + 1; flashEl(outEl); }
        if (inEl)  { inEl.textContent  = Math.max(0, parseInt(inEl.textContent) - 1); flashEl(inEl); }
    }

    // 4. Transaksi hari ini
    const todayTotalEl = document.getElementById('today-total');
    const todayInEl    = document.getElementById('today-in');
    const todayOutEl   = document.getElementById('today-out');
    if (todayTotalEl) { todayTotalEl.textContent = parseInt(todayTotalEl.textContent) + 1; flashEl(todayTotalEl); }
    if (type === 'in'  && todayInEl)  { const cur = parseInt(todayInEl.textContent.replace('+','')); todayInEl.textContent  = '+' + (cur + 1); }
    if (type === 'out' && todayOutEl) { const cur = parseInt(todayOutEl.textContent.replace('-','')); todayOutEl.textContent = '-' + (cur + 1); }

    // 5. Update baris tabel
    if (data.in_stock_total !== undefined) {
        updateRowStats(itemId, parseInt(data.in_stock_total), parseInt(data.out_of_stock_total));
        // update toast stats jika sedang aktif untuk item ini
        if (activeItemId && itemId === parseInt(activeItemId)) {
            const total = parseInt(data.in_stock_total) + parseInt(data.out_of_stock_total);
            const pct   = total > 0 ? Math.round((data.in_stock_total / total) * 100) : 0;
            updateToastStats(data.in_stock_total, data.out_of_stock_total, pct);
        }
    }

    // 6. Jika tag list terbuka, refresh
    setTimeout(() => refreshTagList(itemId), 300);
});

// ════════════════════════════════════════════════════════════
// TABLE HELPERS
// ════════════════════════════════════════════════════════════
function updateRowStats(itemId, newIn, newOut) {
    const total    = newIn + newOut;
    const pct      = total > 0 ? Math.round((newIn / total) * 100) : 0;
    const barClass = pct > 60 ? 'bar--green' : (pct > 30 ? 'bar--amber' : 'bar--red');

    const inEl   = document.getElementById('in-count-'    + itemId);
    const outEl  = document.getElementById('out-count-'   + itemId);
    const totEl  = document.getElementById('total-count-' + itemId);
    const fillEl = document.getElementById('bar-fill-'    + itemId);
    const pctEl  = document.getElementById('bar-pct-'     + itemId);
    const row    = document.getElementById('row-'         + itemId);

    if (inEl)  { inEl.textContent  = newIn;  flashEl(inEl); }
    if (outEl) { outEl.textContent = newOut; flashEl(outEl); }
    if (totEl) totEl.textContent = total;
    if (fillEl) { fillEl.className = 'mini-bar-fill ' + barClass; fillEl.style.width = pct + '%'; }
    if (pctEl)  { pctEl.textContent = pct + '%'; flashEl(pctEl); }
    if (row)    { row.setAttribute('data-in', newIn); row.setAttribute('data-pct', pct); }
}

function flashEl(el) {
    if (!el) return;
    el.classList.remove('count-flash');
    void el.offsetWidth;
    el.classList.add('count-flash');
    setTimeout(() => el.classList.remove('count-flash'), 600);
}

// ════════════════════════════════════════════════════════════
// SCAN LOG
// ════════════════════════════════════════════════════════════
function addScanLog(data) {
    const log = document.getElementById('stock-scan-log');
    const emp = document.getElementById('log-empty-state');
    if (emp) emp.remove();

    const isIn    = data.transaction_type === 'in';
    const time    = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const epcShort = data.epc_id ? data.epc_id.substring(0, 10) + '…' : '—';

    const el = document.createElement('div');
    el.className = 'scan-log-item scan-log-item--' + (isIn ? 'in' : 'out') + ' fade-in';
    el.innerHTML = `
        <div class="scan-log-indicator ${isIn ? 'scan-log-dot--in' : 'scan-log-dot--out'}"></div>
        <div class="scan-log-body">
            <span class="scan-log-name">${data.nama_barang || '—'}</span>
            <span class="scan-log-epc">${epcShort}</span>
        </div>
        <div class="scan-log-right">
            <span class="badge ${isIn ? 'badge--success' : 'badge--danger'}" style="font-size:10px">${isIn ? '+IN' : '−OUT'}</span>
            <span class="scan-log-time">${time}</span>
        </div>`;
    log.insertBefore(el, log.firstChild);
    const items = log.querySelectorAll('.scan-log-item');
    if (items.length > 50) items[items.length - 1].remove();

    const pulse = document.getElementById('log-pulse');
    if (pulse) { pulse.style.transform = 'scale(1.6)'; setTimeout(() => pulse.style.transform = '', 300); }
}

function clearScanLog() {
    document.getElementById('stock-scan-log').innerHTML = `
        <div class="realtime-empty" id="log-empty-state">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" opacity="0.25"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <span id="log-empty-text">Log dibersihkan. Menunggu scan…</span>
        </div>`;
}

// ════════════════════════════════════════════════════════════
// TAG LIST
// ════════════════════════════════════════════════════════════
async function toggleTagList(itemId) {
    const row   = document.getElementById('tags-' + itemId);
    const icon  = document.getElementById('expand-icon-' + itemId);
    const inner = document.getElementById('tag-list-inner-' + itemId);
    if (!row) return;
    if (row.style.display === 'none' || !row.style.display) {
        row.style.display = 'table-row'; icon.style.transform = 'rotate(180deg)';
        inner.innerHTML   = '<div class="tag-list-loading"><div class="spinner"></div><span>Memuat…</span></div>';
        try { const r = await fetch(`/stock/items/${itemId}/tags`); const d = await r.json(); renderTagList(inner, d.tags); } catch { inner.innerHTML = '<p class="text-muted text-sm" style="padding:12px;">Gagal memuat.</p>'; }
    } else { row.style.display = 'none'; icon.style.transform = 'rotate(0)'; }
}

function renderTagList(container, tags) {
    if (!tags?.length) { container.innerHTML = '<div class="tag-empty-state"><p>Belum ada tag. Gunakan Batch Masuk atau ＋ 1 Unit.</p></div>'; return; }
    const inStock = tags.filter(t => t.status === 'in_stock');
    const out     = tags.filter(t => t.status !== 'in_stock');
    let firstIn   = true;
    let html      = `<div class="tag-grid"><div class="tag-grid-header"><span>📦 <strong>${inStock.length}</strong> In Stock</span><span class="text-muted">📤 <strong>${out.length}</strong> Out</span></div><div class="tag-chips">`;
    tags.forEach(tag => {
        const isOut  = tag.status === 'out_of_stock';
        const isFifo = !isOut && firstIn;
        if (!isOut) firstIn = false;
        const date = tag.created_at ? new Date(tag.created_at).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) : '-';
        html += `<div class="tag-chip ${isOut?'tag-chip--out':'tag-chip--in'} ${isFifo?'tag-chip--fifo':''}">
            ${isFifo?'<span class="fifo-label">AMBIL</span>':''}
            <span class="tag-epc">${tag.epc_id}</span>
            <span class="tag-date">${date}</span>
            <span class="badge ${isOut?'badge--danger':'badge--success'}" style="font-size:10px">${isOut?'OUT':'IN'}</span>
        </div>`;
    });
    html += '</div></div>';
    container.innerHTML = html;
}

async function refreshTagList(itemId) {
    const row = document.getElementById('tags-' + itemId);
    const inner = document.getElementById('tag-list-inner-' + itemId);
    if (row?.style.display !== 'none' && inner) {
        try { const r = await fetch(`/stock/items/${itemId}/tags`); const d = await r.json(); renderTagList(inner, d.tags); } catch {}
    }
}

// ════════════════════════════════════════════════════════════
// FILTERING & SORTING
// ════════════════════════════════════════════════════════════
function applyFilters() {
    const q      = (document.getElementById('stock-search').value || '').toLowerCase();
    const filter = document.getElementById('stock-filter').value;
    const sort   = document.getElementById('stock-sort').value;

    const rows = Array.from(document.querySelectorAll('.stock-row'));

    // Filter
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const kode = row.getAttribute('data-kode') || '';
        const inN  = parseInt(row.getAttribute('data-in') || 0);

        const matchSearch = !q || name.includes(q) || kode.includes(q);
        const matchFilter = filter === 'all'      ? true
                          : filter === 'in_stock' ? inN > 0
                          : filter === 'empty'    ? inN === 0
                          : true;

        const show = matchSearch && matchFilter;
        row.style.display = show ? '' : 'none';
        const tagRow = document.getElementById('tags-' + row.getAttribute('data-item-id'));
        if (tagRow && !show) tagRow.style.display = 'none';
    });

    // Sort visible rows
    const tbody  = document.getElementById('stock-table-body');
    const visible = rows.filter(r => r.style.display !== 'none');
    visible.sort((a, b) => {
        const aIn  = parseInt(a.getAttribute('data-in') || 0);
        const bIn  = parseInt(b.getAttribute('data-in') || 0);
        const aPct = parseInt(a.getAttribute('data-pct') || 0);
        const bPct = parseInt(b.getAttribute('data-pct') || 0);
        const aName = a.getAttribute('data-name') || '';
        const bName = b.getAttribute('data-name') || '';
        if (sort === 'name_asc')  return aName.localeCompare(bName);
        if (sort === 'in_desc')   return bIn  - aIn;
        if (sort === 'in_asc')    return aIn  - bIn;
        if (sort === 'pct_desc')  return bPct - aPct;
        if (sort === 'pct_asc')   return aPct - bPct;
        return 0;
    });
    visible.forEach(row => {
        tbody.appendChild(row);
        const tagRow = document.getElementById('tags-' + row.getAttribute('data-item-id'));
        if (tagRow) tbody.appendChild(tagRow);
    });
}

// ════════════════════════════════════════════════════════════
// MISC
// ════════════════════════════════════════════════════════════
function toggleAddForm() {
    const body    = document.getElementById('add-form-body');
    const icon    = document.getElementById('toggle-add-icon');
    const visible = body.style.display !== 'none';
    body.style.display    = visible ? 'none' : 'block';
    icon.style.transform  = visible ? '' : 'rotate(180deg)';
}

@if($errors->any()) toggleAddForm(); @endif

loadScannerState();
</script>

<style>
/* ── Floating Scan Toast ── */
.scan-toast {
    position: fixed; top: -120px; left: 50%; transform: translateX(-50%);
    width: min(780px, calc(100vw - 32px));
    background: white; border-radius: 14px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.08);
    border: 1px solid var(--grey-200);
    z-index: 9999; overflow: hidden;
    transition: top .35s cubic-bezier(.34,1.56,.64,1);
    flex-direction: column;
}
.scan-toast--visible { top: 16px; }
.scan-toast-inner { display: flex; align-items: center; gap: 16px; padding: 14px 18px; }
.scan-toast-left  { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
.scan-toast-radar { position:relative; width:40px; height:40px; flex-shrink:0; }
.radar-sm-ring    { position:absolute; border-radius:50%; border:1.5px solid var(--primary-400); opacity:.5; top:50%;left:50%; }
.ring-1 { width:28px;height:28px; transform:translate(-50%,-50%); animation:radarPulse 1.8s ease-out infinite; }
.ring-2 { width:40px;height:40px; transform:translate(-50%,-50%); animation:radarPulse 1.8s ease-out infinite .6s; }
@keyframes radarPulse { 0%{opacity:.6;transform:translate(-50%,-50%) scale(.6);} 100%{opacity:0;transform:translate(-50%,-50%) scale(1.15);} }
.radar-sm-center { position:absolute;top:50%;left:50%;transform:translate(-50%,-50%); width:24px;height:24px; background:var(--primary-500); border-radius:50%; display:flex;align-items:center;justify-content:center; }
.scan-toast-info { min-width:0; }
.scan-toast-mode-label { font-size:11px;font-weight:600;color:var(--primary-600);letter-spacing:.04em; margin-bottom:2px; }
.scan-toast-product { display:flex;align-items:center;gap:6px;flex-wrap:wrap; }
.scan-toast-product strong { font-size:14px;color:var(--grey-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px; }
.scan-toast-stats  { display:flex;align-items:center;gap:0;flex-shrink:0;border-left:1px solid var(--grey-100);border-right:1px solid var(--grey-100);padding:0 16px;margin:0 4px; }
.scan-toast-stat   { display:flex;flex-direction:column;align-items:center;gap:2px;padding:0 12px; }
.scan-toast-stat-val { font-size:18px;font-weight:700;line-height:1; }
.scan-toast-stat-val--green { color:var(--green-600); }
.scan-toast-stat-val--red   { color:var(--red-500); }
.scan-toast-stat-label { font-size:10px;color:var(--grey-400);font-weight:500; }
.scan-toast-divider { width:1px;height:32px;background:var(--grey-100); }
.scan-toast-right  { display:flex;align-items:center;gap:12px;flex-shrink:0; }
.scan-toast-countdown { display:flex;align-items:center;gap:4px;font-size:12px;color:var(--grey-400); }
.scan-toast-actions { display:flex;gap:6px; }
.btn-toast-done   { padding:6px 14px;border-radius:7px;border:none;background:var(--primary-500);color:white;font-size:12px;font-weight:600;cursor:pointer; transition:background .15s; }
.btn-toast-done:hover { background:var(--primary-600); }
.btn-toast-cancel { padding:6px 10px;border-radius:7px;border:1px solid var(--grey-200);background:white;color:var(--grey-500);font-size:12px;cursor:pointer; }
.scan-toast-progress { height:3px;background:var(--grey-100); }
.scan-toast-progress-bar { height:100%;background:var(--primary-400);width:100%;transform-origin:left; }

/* ── Batch Modal ── */
.batch-modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9990;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .25s; }
.batch-modal-overlay--visible { opacity:1; }
.batch-modal { background:white;border-radius:16px;width:420px;max-width:calc(100vw - 32px);overflow:hidden;box-shadow:0 16px 60px rgba(0,0,0,.2);transform:scale(.96) translateY(8px);transition:transform .25s; }
.batch-modal-overlay--visible .batch-modal { transform:scale(1) translateY(0); }
.batch-modal-header { display:flex;align-items:flex-start;gap:12px;padding:20px 20px 0; position:relative; }
.batch-modal-icon { width:36px;height:36px;background:var(--primary-100);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--primary-600);flex-shrink:0; }
.batch-modal-title { font-size:15px;font-weight:700;color:var(--grey-800);margin:0 0 2px; }
.batch-modal-sub   { font-size:12px;color:var(--grey-400);margin:0; }
.batch-modal-close { position:absolute;right:16px;top:16px;border:none;background:none;cursor:pointer;color:var(--grey-400);padding:4px; }
.batch-modal-body  { padding:16px 20px; }
.batch-modal-footer { display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 20px; }

/* ── 2-column layout ── */
.stock-control-row { display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px; }
@media(max-width:900px){ .stock-control-row{grid-template-columns:1fr;} }
.stock-control-panel { display:flex;flex-direction:column; }
.stock-log-panel     { display:flex;flex-direction:column; }
.panel-divider { height:1px;background:var(--grey-100);margin:0; }
.scanner-mode-badge { font-size:11px;font-weight:600;color:var(--primary-600);background:var(--primary-50);border:1px solid var(--primary-100);border-radius:20px;padding:2px 10px; }
.scanner-mode-buttons { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px; }

/* ── Filter row ── */
.stock-table-filters { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.filter-select { height:34px;padding:0 10px;border:1px solid var(--grey-200);border-radius:8px;font-size:12px;background:white;color:var(--grey-700);cursor:pointer;outline:none; }
.filter-select:focus { border-color:var(--primary-400); }

/* ── Log panel ── */
.stock-scan-log { display:flex;flex-direction:column;gap:5px;max-height:300px;overflow-y:auto;padding-right:2px; }
.stock-scan-log::-webkit-scrollbar{width:3px;} .stock-scan-log::-webkit-scrollbar-thumb{background:var(--grey-200);border-radius:2px;}
.scan-log-item { display:flex;align-items:center;gap:9px;padding:7px 10px;border-radius:7px;border-left:2px solid transparent; }
.scan-log-item--in  { background:var(--green-50);border-left-color:var(--green-400); }
.scan-log-item--out { background:var(--red-50);border-left-color:var(--red-400); }
.scan-log-indicator { width:7px;height:7px;border-radius:50%;flex-shrink:0; }
.scan-log-dot--in  { background:var(--green-500); } .scan-log-dot--out { background:var(--red-500); }
.scan-log-body { flex:1;min-width:0; }
.scan-log-name { display:block;font-size:12px;font-weight:600;color:var(--grey-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.scan-log-epc  { display:block;font-size:10px;color:var(--grey-400);font-family:monospace; }
.scan-log-right { display:flex;flex-direction:column;align-items:flex-end;gap:2px;flex-shrink:0; }
.scan-log-time  { font-size:10px;color:var(--grey-400);font-variant-numeric:tabular-nums; }
.badge--offline { background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA; font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;letter-spacing:.06em; }

/* ── Flash animation ── */
@keyframes countFlash { 0%{transform:scale(1);} 30%{transform:scale(1.3);background:var(--primary-100);color:var(--primary-700);} 100%{transform:scale(1);} }
.count-flash { animation:countFlash .6s ease; }

/* ── nav-item--soon ── */
.nav-item--soon { opacity:.5; pointer-events:none; }
</style>
@endpush
