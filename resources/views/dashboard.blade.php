@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid" id="stats-grid">
    <div class="stat-card" id="stat-total-items">
        <div class="stat-card-icon stat-card-icon--blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" stroke="currentColor" stroke-width="2"/>
                <line x1="7" y1="7" x2="7.01" y2="7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value">{{ $totalItems }}</span>
            <span class="stat-card-label">Total SKU Produk</span>
        </div>
        <div class="stat-card-trend stat-card-trend--neutral">Katalog</div>
    </div>

    <div class="stat-card" id="stat-in-stock">
        <div class="stat-card-icon stat-card-icon--green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <polyline points="17 6 23 6 23 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value" id="dash-in-stock">{{ $totalInStock }}</span>
            <span class="stat-card-label">Unit Stok Tersedia</span>
        </div>
        <div class="stat-card-trend stat-card-trend--up">In Stock</div>
    </div>

    <div class="stat-card" id="stat-out-stock">
        <div class="stat-card-icon stat-card-icon--red">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <polyline points="23 18 13.5 8.5 8.5 13.5 1 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <polyline points="17 18 23 18 23 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value" id="dash-out-stock">{{ $totalOutOfStock }}</span>
            <span class="stat-card-label">Unit Stok Keluar</span>
        </div>
        <div class="stat-card-trend stat-card-trend--down">Out of Stock</div>
    </div>

    <div class="stat-card" id="stat-today">
        <div class="stat-card-icon stat-card-icon--amber">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="stat-card-body">
            <span class="stat-card-value">{{ $stockInToday + $stockOutToday }}</span>
            <span class="stat-card-label">Transaksi Hari Ini</span>
        </div>
        <div class="stat-card-trend">
            <span style="color:var(--green-500)">+{{ $stockInToday }}</span>
            &nbsp;/&nbsp;
            <span style="color:var(--red-500)">-{{ $stockOutToday }}</span>
        </div>
    </div>
</div>

{{-- ===== MAIN GRID ===== --}}
<div class="dashboard-grid">

    {{-- Notifikasi Real-Time --}}
    <div class="dashboard-card dashboard-card--wide" id="card-realtime">
        <div class="card-header">
            <div class="card-header-left">
                <h2 class="card-title">
                    <span class="pulse-dot"></span>
                    Notifikasi Kejadian Real-Time
                </h2>
            </div>
            <div class="card-header-right">
                <span class="badge badge--live">LIVE</span>
            </div>
        </div>
        <div class="card-body">
            <div id="realtime-feed" class="realtime-feed">
                <div class="realtime-empty" id="realtime-empty">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" opacity="0.3">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M12 8v4l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <p>Menunggu data scan RFID...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Rak & Produk --}}
    <div class="dashboard-card" id="card-rack-status">
        <div class="card-header">
            <h2 class="card-title">Status Rak & Produk</h2>
            <a href="{{ route('stock.index') }}" class="card-link">Lihat Semua →</a>
        </div>
        <div class="card-body">
            <div class="stock-summary-list" id="stock-summary-list">
                @forelse($topItems as $item)
                <div class="stock-summary-item">
                    <div class="stock-summary-info">
                        <span class="stock-summary-code">{{ $item->kode_barang }}</span>
                        <span class="stock-summary-name">{{ $item->nama_barang }}</span>
                    </div>
                    <div class="stock-summary-bar-wrap">
                        @php
                            $total = $item->total_tags_count ?: 1;
                            $pct   = round(($item->in_stock_count / $total) * 100);
                            $barClass = $pct > 60 ? 'bar--green' : ($pct > 30 ? 'bar--amber' : 'bar--red');
                        @endphp
                        <div class="stock-summary-bar">
                            <div class="stock-summary-fill {{ $barClass }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="stock-summary-pct">{{ $pct }}%</span>
                    </div>
                    <div class="stock-summary-counts">
                        <span class="count-in">{{ $item->in_stock_count }}</span>
                        <span class="count-sep">/</span>
                        <span class="count-total">{{ $item->total_tags_count }}</span>
                    </div>
                </div>
                @empty
                <p class="text-muted text-sm">Belum ada data produk.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Log Kejadian --}}
    <div class="dashboard-card dashboard-card--wide" id="card-log">
        <div class="card-header">
            <h2 class="card-title">Log Kejadian Terbaru</h2>
            <span class="badge badge--info">{{ $recentTransactions->count() }} entri terbaru</span>
        </div>
        <div class="card-body card-body--no-padding">
            <table class="data-table" id="log-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>EPC ID</th>
                        <th>Produk</th>
                        <th>Tipe</th>
                    </tr>
                </thead>
                <tbody id="log-table-body">
                    @forelse($recentTransactions as $tx)
                    <tr class="fade-in">
                        <td class="text-mono text-sm">{{ $tx->created_at->format('d/m H:i:s') }}</td>
                        <td class="text-mono text-sm text-muted">{{ $tx->epc_id }}</td>
                        <td>
                            @if($tx->tag && $tx->tag->item)
                                <span class="text-sm font-medium">{{ $tx->tag->item->nama_barang }}</span>
                                <span class="text-xs text-muted block">{{ $tx->tag->item->kode_barang }}</span>
                            @else
                                <span class="text-muted text-sm">—</span>
                            @endif
                        </td>
                        <td>
                            @if($tx->type === 'in')
                                <span class="badge badge--success">MASUK</span>
                            @else
                                <span class="badge badge--danger">KELUAR</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted text-sm py-6">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
window.addEventListener('rfid-scanned', function(e) {
    const data = e.detail;

    // Update stat cards
    const inStockEl  = document.getElementById('dash-in-stock');
    const outStockEl = document.getElementById('dash-out-stock');

    if (data.transaction_type === 'in' && inStockEl) {
        inStockEl.textContent = parseInt(inStockEl.textContent) + 1;
    } else if (data.transaction_type === 'out' && outStockEl) {
        outStockEl.textContent = parseInt(outStockEl.textContent) + 1;
        if (inStockEl) inStockEl.textContent = Math.max(0, parseInt(inStockEl.textContent) - 1);
    }

    // Add to real-time feed
    const feed  = document.getElementById('realtime-feed');
    const empty = document.getElementById('realtime-empty');
    if (empty) empty.remove();

    const typeClass = data.transaction_type === 'in' ? 'feed-item--in' : 'feed-item--out';
    const typeLabel = data.transaction_type === 'in' ? 'MASUK' : 'KELUAR';
    const typeBadge = data.transaction_type === 'in' ? 'badge--success' : 'badge--danger';

    const item = document.createElement('div');
    item.className = `feed-item ${typeClass} fade-in`;
    item.innerHTML = `
        <div class="feed-item-time">${data.timestamp}</div>
        <div class="feed-item-epc text-mono">${data.epc_id}</div>
        <div class="feed-item-name">${data.nama_barang} <span class="text-muted text-xs">(${data.kode_barang})</span></div>
        <span class="badge ${typeBadge}">${typeLabel}</span>
    `;

    feed.insertBefore(item, feed.firstChild);

    // Keep max 20 items
    const items = feed.querySelectorAll('.feed-item');
    if (items.length > 20) items[items.length - 1].remove();

    // Add new row to log table
    const tbody = document.getElementById('log-table-body');
    if (tbody) {
        const now = new Date();
        const timeStr = now.toLocaleDateString('id-ID', {day:'2-digit',month:'2-digit'}) + ' '
                      + now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
        const row = document.createElement('tr');
        row.className = 'fade-in';
        row.innerHTML = `
            <td class="text-mono text-sm">${timeStr}</td>
            <td class="text-mono text-sm text-muted">${data.epc_id}</td>
            <td><span class="text-sm font-medium">${data.nama_barang}</span><span class="text-xs text-muted block">${data.kode_barang}</span></td>
            <td><span class="badge ${typeBadge}">${typeLabel}</span></td>
        `;
        tbody.insertBefore(row, tbody.firstChild);
        const rows = tbody.querySelectorAll('tr');
        if (rows.length > 15) rows[rows.length - 1].remove();
    }
});
</script>
@endpush
