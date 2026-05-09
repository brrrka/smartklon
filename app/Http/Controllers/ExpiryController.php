<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Expiry;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpiryController extends Controller
{
    /**
     * Menampilkan halaman utama Expiry / Dashboard
     */
    public function index()
    {
        $today = today();
        $warningDate = today()->addDays(7);

        // 1. Ambil data item beserta jumlah batch yang tersedia (status = 0) dan habis (status = 1)
        $items = Item::withCount([
            'expiries as active_batches_count'  => fn($q) => $q->where('status', 0),
            'expiries as inactive_batches_count' => fn($q) => $q->where('status', 1),
            'expiries as total_batches_count',
        ])->orderBy('nama_barang')->get();

        $totalSku = Item::count();

        // 2. Hitung ketersediaan fisik (Tersedia / Habis)
        $totalActiveBatches   = Expiry::where('status', 0)->count();
        $totalInactiveBatches = Expiry::where('status', 1)->count();

        // 3. Hitung status waktu kedaluwarsa HANYA untuk batch yang masih aktif/tersedia (status = 0)
        $totalExpired = Expiry::where('status', 0)
            ->where('expiry_date', '<', $today)
            ->count();

        $totalWarning = Expiry::where('status', 0)
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $warningDate)
            ->count();

        $totalSafe    = Expiry::where('status', 0)
            ->where('expiry_date', '>', $warningDate)
            ->count();

        // 4. Hitung transaksi hari ini (opsional jika Anda masih pakai tabel transactions)
        $stockInToday  = Transaction::where('type', 'in')->whereDate('created_at', $today)->count();
        $stockOutToday = Transaction::where('type', 'out')->whereDate('created_at', $today)->count();

        return view('expiry.index', compact(
            'items',
            'totalActiveBatches',
            'totalInactiveBatches',
            'totalSku',
            'totalExpired',
            'totalWarning',
            'totalSafe',
            'stockInToday',
            'stockOutToday'
        ));
    }

    /**
     * Menyimpan data master barang (Item) baru
     */
    public function storeItem(Request $request)
    {
        $request->validate([
            'kode_barang' => 'required|string|max:50|unique:items,kode_barang',
            'barcode'     => 'nullable|string|max:50|unique:items,barcode',
            'nama_barang' => 'required|string|max:255',
            'deskripsi'   => 'nullable|string',
            'satuan'      => 'nullable|string|max:50',
        ]);

        Item::create([
            'kode_barang' => strtoupper($request->kode_barang),
            'barcode'     => $request->barcode ? strtoupper($request->barcode) : null,
            'nama_barang' => $request->nama_barang,
            'deskripsi'   => $request->deskripsi,
            'satuan'      => $request->satuan ?? 'PCS',
        ]);

        return redirect()->route('expiry.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Menyimpan data batch (stok masuk) baru
     */
    public function storeBatch(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'item_id'      => 'required|exists:items,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date'  => 'required|date',
        ], [
            // Kustomisasi pesan error (opsional)
            'item_id.required'      => 'Produk target wajib dipilih.',
            'item_id.exists'        => 'Produk tidak ditemukan di database.',
            'batch_number.required' => 'Nomor batch wajib diisi.',
            'expiry_date.required'  => 'Tanggal kedaluwarsa wajib diisi.',
            'expiry_date.date'      => 'Format tanggal tidak valid.',
        ]);

        // 2. Simpan ke database
        Expiry::create([
            'item_id'      => $request->item_id,
            'batch_number' => strtoupper($request->batch_number), // Ubah ke huruf besar agar seragam
            'expiry_date'  => $request->expiry_date,
            'status'       => 0, // 0 = Tersedia / Aktif (sesuai default Blueprint)
        ]);

        // 3. (Opsional) Jika Anda masih ingin mencatat riwayat ke tabel transactions untuk metrik "Transaksi Hari Ini"
        /*
        Transaction::create([
            'item_id' => $request->item_id,
            'type'    => 'in',
            // Jika ada tabel yang mencatat keterangan, bisa ditambahkan:
            // 'notes' => 'Batch Masuk: ' . strtoupper($request->batch_number)
        ]);
        */

        // 4. Redirect kembali dengan pesan sukses
        return redirect()->route('expiry.index')->with('success', 'Data Batch berhasil ditambahkan!');
    }

    /**
     * API: Mengambil daftar batch/kadaluarsa berdasarkan Item (diurutkan FEFO)
     */
    public function getExpiriesByItem(Item $item)
    {
        $expiries = $item->expiries()
            ->orderBy('status', 'asc')       // Yang ketersediaannya aktif (0) di atas
            ->orderBy('expiry_date', 'asc')  // Tanggal expired paling dekat di atas (FEFO)
            ->get();

        return response()->json([
            'item'     => $item,
            'expiries' => $expiries,
        ]);
    }

    /**
     * API: Polling fallback — returns live batch counts per item + recent updates.
     */
    public function liveData()
    {
        // 1. Ambil list item beserta perhitungan batch aktifnya
        $items = Item::withCount([
            'expiries as active_batches_count'   => fn($q) => $q->where('status', 0),
            'expiries as inactive_batches_count' => fn($q) => $q->where('status', 1),
            'expiries as total_batches_count',
        ])->get(['id', 'kode_barang', 'nama_barang']);

        // 2. Ambil 20 data batch terakhir yang diperbarui sebagai log/history
        $recentExpiries = Expiry::with('item:id,nama_barang,kode_barang')
            ->latest('updated_at')
            ->take(20)
            ->get();

        // 3. Mapping data log agar informatif (termasuk status Safe/Warning/Expired)
        $logs = $recentExpiries->map(fn($expiry) => [
            'batch_number'  => $expiry->batch_number,
            'item_id'       => $expiry->item_id,
            'nama_barang'   => $expiry->item->nama_barang ?? '-',
            'kode_barang'   => $expiry->item->kode_barang ?? '-',
            'expiry_date'   => Carbon::parse($expiry->expiry_date)->format('Y-m-d'),

            // Akses property dari Accessor di Model (Safe, Warning, Expired)
            'days_left'     => $expiry->days_left,
            'expiry_state'  => $expiry->expiry_state,

            // Status Fisik (Tersedia / Habis)
            'status_fisik'  => $expiry->status == 0 ? 'Tersedia' : 'Habis/Dibuang',
            'timestamp'     => $expiry->updated_at->toDateTimeString(),
        ]);

        return response()->json([
            'items'          => $items,
            'total_active'   => Expiry::where('status', 0)->count(),
            'total_inactive' => Expiry::where('status', 1)->count(),
            'logs'           => $logs,
            'ws_needed'      => true,
        ]);
    }
}
