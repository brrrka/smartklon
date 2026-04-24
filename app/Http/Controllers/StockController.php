<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Tag;
use App\Models\Transaction;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $items = Item::withCount([
            'tags as in_stock_count'  => fn($q) => $q->where('status', 'in_stock'),
            'tags as out_stock_count' => fn($q) => $q->where('status', 'out_of_stock'),
            'tags as total_tags_count',
        ])->orderBy('nama_barang')->get();

        $totalInStock    = Tag::where('status', 'in_stock')->count();
        $totalOutOfStock = Tag::where('status', 'out_of_stock')->count();
        $totalSku        = Item::count();

        $today         = today();
        $stockInToday  = Transaction::where('type', 'in')->whereDate('created_at', $today)->count();
        $stockOutToday = Transaction::where('type', 'out')->whereDate('created_at', $today)->count();

        return view('stock.index', compact(
            'items',
            'totalInStock',
            'totalOutOfStock',
            'totalSku',
            'stockInToday',
            'stockOutToday'
        ));

    }

    public function storeItem(Request $request)
    {
        $request->validate([
            'kode_barang' => 'required|string|max:50|unique:items,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'deskripsi'   => 'nullable|string',
            'satuan'      => 'nullable|string|max:50',
        ]);

        Item::create([
            'kode_barang' => strtoupper($request->kode_barang),
            'nama_barang' => $request->nama_barang,
            'deskripsi'   => $request->deskripsi,
            'satuan'      => $request->satuan ?? 'pcs',
        ]);

        return redirect()->route('stock.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function getTagsByItem(Item $item)
    {
        $tags = $item->tags()
            ->with(['transactions' => fn($q) => $q->latest()->take(1)])
            ->orderByRaw("status = 'in_stock' DESC")
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'item' => $item,
            'tags' => $tags,
        ]);
    }

    /**
     * Polling fallback — returns live stock counts per item + recent transactions.
     * Called by the frontend every few seconds when WebSocket is disconnected.
     */
    public function liveData()
    {
        $items = Item::withCount([
            'tags as in_stock_count'  => fn($q) => $q->where('status', 'in_stock'),
            'tags as out_stock_count' => fn($q) => $q->where('status', 'out_of_stock'),
            'tags as total_tags_count',
        ])->get(['id', 'kode_barang', 'nama_barang']);

        // Recent 20 transactions for the scan log
        $recentTags = Tag::with('item:id,nama_barang,kode_barang')
            ->latest('updated_at')
            ->take(20)
            ->get(['id', 'epc_id', 'item_id', 'status', 'updated_at']);

        $logs = $recentTags->map(fn($tag) => [
            'epc_id'           => $tag->epc_id,
            'item_id'          => $tag->item_id,
            'nama_barang'      => $tag->item->nama_barang ?? '-',
            'kode_barang'      => $tag->item->kode_barang ?? '-',
            'transaction_type' => $tag->status === 'in_stock' ? 'in' : 'out',
            'timestamp'        => $tag->updated_at->toDateTimeString(),
        ]);

        return response()->json([
            'items'      => $items,
            'total_in'   => Tag::where('status', 'in_stock')->count(),
            'total_out'  => Tag::where('status', 'out_of_stock')->count(),
            'logs'       => $logs,
            'ws_needed'  => true, // hint ke client untuk tetap coba WS
        ]);
    }
}

