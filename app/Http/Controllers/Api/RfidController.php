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
            'epc' => 'required|string|min:4|max:64',
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
        // Mode TIDAK auto-reset; staf harus set manual ke idle.
        // -------------------------------------------------------
        if ($mode === 'batch_in') {
            if (!$state->target_item_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target produk belum dipilih untuk mode Batch Masuk.',
                ], 422);
            }

            $tag = Tag::firstOrCreate(
                ['epc_id' => $epc],
                ['item_id' => $state->target_item_id, 'status' => 'in_stock']
            );

            if (!$tag->wasRecentlyCreated) {
                $tag->status = 'in_stock';
                $tag->save();
            }

            Transaction::create(['epc_id' => $epc, 'type' => 'in']);
            broadcast(new RfidScanned($tag, 'in', "Batch: {$epc} → {$tag->item->nama_barang}"));

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
        // Daftarkan TEPAT SATU tag ke item target.
        // Setelah 1 scan berhasil → otomatis reset ke idle.
        //
        // Perbedaan dengan batch_in:
        //   batch_in  = terus scan sampai user matikan manual
        //   single_in = 1 scan saja, lalu idle otomatis
        // -------------------------------------------------------
        if ($mode === 'single_in') {
            if (!$state->target_item_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target produk belum dipilih untuk mode Single In.',
                ], 422);
            }

            // Cek apakah EPC ini sudah terdaftar di item LAIN
            $existingTag = Tag::where('epc_id', $epc)->first();
            if ($existingTag && $existingTag->item_id !== (int) $state->target_item_id) {
                $otherItem = $existingTag->item->nama_barang ?? '-';
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag {$epc} sudah terdaftar untuk produk lain ({$otherItem}).",
                ], 409);
            }

            // Daftarkan atau update tag
            $tag = Tag::firstOrCreate(
                ['epc_id' => $epc],
                ['item_id' => $state->target_item_id, 'status' => 'in_stock']
            );

            if (!$tag->wasRecentlyCreated) {
                $tag->status = 'in_stock';
                $tag->save();
            }

            Transaction::create(['epc_id' => $epc, 'type' => 'in']);

            // ★ Auto-reset ke idle setelah 1 scan berhasil ★
            $state->active_mode    = 'idle';
            $state->target_item_id = null;
            $state->updated_at     = now();
            $state->save();

            broadcast(new RfidScanned($tag, 'in', "Single: {$epc} → {$tag->item->nama_barang}"));

            return response()->json([
                'status'     => 'ok',
                'message'    => "1 unit {$tag->item->nama_barang} berhasil ditambahkan.",
                'epc_id'     => $epc,
                'item_id'    => $tag->item_id,
                'tag_status' => $tag->status,
                'mode'       => 'single_in',
                'auto_reset' => true,   // beri tahu ESP32 bahwa scanner kembali idle
            ], 200);
        }

        // -------------------------------------------------------
        // MODE: out
        // Scan tag yang ADA di stok → ubah jadi out_of_stock.
        //
        // Kenapa bisa error 404?
        //   → EPC yang di-scan belum pernah didaftarkan sama sekali
        //     (belum pernah di-batch_in atau single_in).
        //     Jadi tag asing yang tidak dikenal sistem.
        //
        // Kenapa bisa error 422?
        //   → Tag ada di database, tapi statusnya sudah out_of_stock
        //     (sudah pernah diambil sebelumnya).
        // -------------------------------------------------------
        if ($mode === 'out') {
            $tag = Tag::where('epc_id', $epc)->first();

            // 404 = EPC tidak dikenal sistem sama sekali
            if (!$tag) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag {$epc} tidak ditemukan. Daftarkan dulu via Batch/Single Masuk.",
                ], 404);
            }

            // 422 = Tag ada tapi sudah berstatus out_of_stock
            if ($tag->status !== 'in_stock') {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag {$epc} sudah berstatus keluar sebelumnya.",
                ], 422);
            }

            $tag->status = 'out_of_stock';
            $tag->save();

            Transaction::create(['epc_id' => $epc, 'type' => 'out']);
            broadcast(new RfidScanned($tag, 'out', "{$epc} → {$tag->item->nama_barang} keluar"));

            return response()->json([
                'status'     => 'ok',
                'message'    => "1 unit {$tag->item->nama_barang} dicatat keluar.",
                'epc_id'     => $epc,
                'item_id'    => $tag->item_id,
                'tag_status' => $tag->status,
                'mode'       => 'out',
            ], 200);
        }

        // -------------------------------------------------------
        // MODE: check_stock
        // Hanya baca informasi tag, tidak ada perubahan data.
        // -------------------------------------------------------
        if ($mode === 'check_stock') {
            $tag = Tag::with('item')->where('epc_id', $epc)->first();

            if (!$tag) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag {$epc} tidak ditemukan.",
                ], 404);
            }

            return response()->json([
                'status'     => 'ok',
                'epc_id'     => $epc,
                'nama_barang'=> $tag->item->nama_barang ?? '-',
                'kode_barang'=> $tag->item->kode_barang ?? '-',
                'tag_status' => $tag->status,
                'mode'       => 'check_stock',
            ], 200);
        }

        // -------------------------------------------------------
        // MODE: idle — semua scan diabaikan
        // -------------------------------------------------------
        return response()->json([
            'status'  => 'idle',
            'message' => 'Scanner sedang idle, scan tidak diproses.',
        ], 202); // 202 = diterima tapi tidak diproses (beda dari 200 = sukses)

    }
}
