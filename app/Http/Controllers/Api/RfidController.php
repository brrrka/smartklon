<?php

namespace App\Http\Controllers\Api;

use App\Events\RfidScanned;
use App\Http\Controllers\Controller;
use App\Models\ScannerState;
use App\Models\Tag;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'epc' => ['required', 'string', 'regex:/^[0-9A-Fa-f]{8,64}$/'],
        ]);

        $epc   = strtoupper(trim($request->epc));
        $state = ScannerState::with('targetItem')->find(1);

        if (!$state) {
            return response()->json(['status' => 'error', 'message' => 'Scanner state not initialized'], 500);
        }

        $mode = $state->active_mode;

        // -------------------------------------------------------
        // MODE: batch_in
        // Daftarkan banyak tag ke satu item secara terus-menerus.
        // - Tag baru                          → buat + catat transaksi + broadcast
        // - Tag ada, item sama, out_of_stock  → update in_stock + transaksi + broadcast
        // - Tag ada, item sama, in_stock      → skip (sudah terdaftar, tidak perlu ulang)
        // - Tag ada, item BEDA               → 409 (tag milik produk lain)
        // -------------------------------------------------------
        if ($mode === 'batch_in') {
            if (!$state->target_item_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target produk belum dipilih untuk mode Batch Masuk.',
                ], 422);
            }

            $tag = Tag::findByEpc($epc);

            if ($tag) {
                // Tag terdaftar ke item LAIN → tolak
                if ($tag->item_id !== (int) $state->target_item_id) {
                    $otherItem = $tag->item?->nama_barang ?? '-';
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Tag {$epc} sudah terdaftar untuk produk lain ({$otherItem}).",
                    ], 409);
                }

                // Tag sudah in_stock untuk item yang sama → tidak perlu update apapun
                if ($tag->status === 'in_stock') {
                    return response()->json([
                        'status'   => 'ok',
                        'skipped'  => true,
                        'message'  => 'Tag sudah tercatat in_stock untuk produk ini.',
                        'epc_id'   => $epc,
                        'item_id'  => $tag->item_id,
                        'tag_status' => $tag->status,
                        'mode'     => 'batch_in',
                    ], 200);
                }

                // Tag ada, out_of_stock, item sama → kembalikan ke in_stock
                $tag->status = 'in_stock';
                $tag->save();
            } else {
                // Tag baru → daftarkan
                $tag = Tag::create([
                    'epc_id'  => $epc,
                    'item_id' => $state->target_item_id,
                    'status'  => 'in_stock',
                ]);
                $tag->load('item');
            }

            Transaction::create(['epc_id' => $epc, 'type' => 'in']);
            broadcast(new RfidScanned($tag, 'in', "Batch: {$epc} → " . ($tag->item?->nama_barang ?? '-')));

            return response()->json([
                'status'     => 'ok',
                'message'    => 'Stok masuk (batch) dicatat.',
                'epc_id'     => $epc,
                'item_id'    => $tag->item_id,
                'tag_status' => $tag->status,
                'mode'       => 'batch_in',
            ], 200);
        }

        // -------------------------------------------------------
        // MODE: single_in
        // Daftarkan TEPAT SATU tag ke item target lalu auto-idle.
        // - Tag baru                          → buat + transaksi + broadcast + idle
        // - Tag ada, item sama, out_of_stock  → update in_stock + transaksi + broadcast + idle
        // - Tag ada, item sama, in_stock      → 409 (slot tidak dikonsumsi, coba tag lain)
        // - Tag ada, item BEDA               → 409 (slot tidak dikonsumsi)
        // -------------------------------------------------------
        if ($mode === 'single_in') {
            if (!$state->target_item_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target produk belum dipilih untuk mode Single In.',
                ], 422);
            }

            $tag = Tag::findByEpc($epc);

            if ($tag) {
                // Tag terdaftar ke item LAIN → tolak, jangan konsumsi slot
                if ($tag->item_id !== (int) $state->target_item_id) {
                    $otherItem = $tag->item?->nama_barang ?? '-';
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Tag {$epc} sudah terdaftar untuk produk lain ({$otherItem}).",
                    ], 409);
                }

                // Tag sudah in_stock untuk item yang sama → tolak, jangan konsumsi slot
                if ($tag->status === 'in_stock') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Tag {$epc} sudah in_stock untuk produk ini. Gunakan tag lain.",
                    ], 409);
                }

                // Tag ada, out_of_stock, item sama → kembalikan ke in_stock
                $tag->status = 'in_stock';
                $tag->save();
            } else {
                // Tag baru → daftarkan
                $tag = Tag::create([
                    'epc_id'  => $epc,
                    'item_id' => $state->target_item_id,
                    'status'  => 'in_stock',
                ]);
                $tag->load('item');
            }

            Transaction::create(['epc_id' => $epc, 'type' => 'in']);

            // Auto-reset ke idle setelah 1 scan berhasil
            $state->active_mode    = 'idle';
            $state->target_item_id = null;
            $state->updated_at     = now();
            $state->save();

            broadcast(new RfidScanned($tag, 'in', "Single: {$epc} → " . ($tag->item?->nama_barang ?? '-')));

            return response()->json([
                'status'     => 'ok',
                'message'    => "1 unit " . ($tag->item?->nama_barang ?? '-') . " berhasil ditambahkan.",
                'epc_id'     => $epc,
                'item_id'    => $tag->item_id,
                'tag_status' => $tag->status,
                'mode'       => 'single_in',
                'auto_reset' => true,
            ], 200);
        }

        // -------------------------------------------------------
        // MODE: out
        // Scan tag yang ADA di stok → ubah jadi out_of_stock.
        // - Tag tidak ditemukan         → 404
        // - Tag sudah out_of_stock     → 422
        // - Tag in_stock               → update + transaksi + broadcast
        // -------------------------------------------------------
        if ($mode === 'out') {
            $tag = Tag::findByEpc($epc);

            if (!$tag) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag {$epc} tidak ditemukan. Daftarkan dulu via Batch/Single Masuk.",
                ], 404);
            }

            if ($tag->status !== 'in_stock') {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag {$epc} sudah berstatus keluar sebelumnya.",
                ], 422);
            }

            $tag->status = 'out_of_stock';
            $tag->save();

            Transaction::create(['epc_id' => $epc, 'type' => 'out']);
            broadcast(new RfidScanned($tag, 'out', "{$epc} → " . ($tag->item?->nama_barang ?? '-') . " keluar"));

            return response()->json([
                'status'     => 'ok',
                'message'    => "1 unit " . ($tag->item?->nama_barang ?? '-') . " dicatat keluar.",
                'epc_id'     => $epc,
                'item_id'    => $tag->item_id,
                'tag_status' => $tag->status,
                'mode'       => 'out',
            ], 200);
        }

        // -------------------------------------------------------
        // MODE: check_stock
        // Hanya baca info tag, tidak ada perubahan data.
        // Broadcast agar scan log real-time terupdate.
        // -------------------------------------------------------
        if ($mode === 'check_stock') {
            $tag = Tag::findByEpc($epc);

            if (!$tag) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag {$epc} tidak ditemukan.",
                ], 404);
            }

            broadcast(new RfidScanned($tag, 'check', "Cek: {$epc} → " . ($tag->item?->nama_barang ?? '-')));

            return response()->json([
                'status'      => 'ok',
                'epc_id'      => $epc,
                'nama_barang' => $tag->item?->nama_barang ?? '-',
                'kode_barang' => $tag->item?->kode_barang ?? '-',
                'tag_status'  => $tag->status,
                'mode'        => 'check_stock',
            ], 200);
        }

        // -------------------------------------------------------
        // MODE: idle — semua scan diabaikan
        // -------------------------------------------------------
        return response()->json([
            'status'  => 'idle',
            'message' => 'Scanner sedang idle, scan tidak diproses.',
        ], 202);
    }
}
