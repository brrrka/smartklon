<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Tag;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['kode_barang' => 'BRG-001', 'nama_barang' => 'Indomie Goreng', 'deskripsi' => 'Mie Instan Goreng', 'satuan' => 'pcs'],
            ['kode_barang' => 'BRG-002', 'nama_barang' => 'Aqua Botol 600ml', 'deskripsi' => 'Air Mineral 600ml', 'satuan' => 'botol'],
            ['kode_barang' => 'BRG-003', 'nama_barang' => 'Teh Botol Sosro 450ml', 'deskripsi' => 'Teh Botol 450ml', 'satuan' => 'botol'],
            ['kode_barang' => 'BRG-004', 'nama_barang' => 'Minyak Goreng 1L', 'deskripsi' => 'Minyak Goreng Kemasan 1L', 'satuan' => 'liter'],
            ['kode_barang' => 'BRG-005', 'nama_barang' => 'Gula Pasir 1kg', 'deskripsi' => 'Gula Pasir Kemasan 1kg', 'satuan' => 'kg'],
        ];

        foreach ($items as $itemData) {
            $item = Item::updateOrCreate(['kode_barang' => $itemData['kode_barang']], $itemData);

            // EPC 24-char sesuai format tag UHF real dari foto frame:
            // Frame: [11 00 EE 00] [EPC 12 byte] [RSSI 2 byte]
            // Struktur EPC: E2 80 69 15 00 00 [byte6] 1B 22 B8 [byte10] F5
            // Unik di byte-6 (40/50) dan byte-10 (serial).
            // Prefix 10 byte pertama dibedakan per item via byte-6.
            $byte6   = strtoupper(dechex(0x40 + ($item->id % 2)));  // '40' atau '42', beda per item
            $prefix  = 'E28069150000' . str_pad($byte6, 2, '0', STR_PAD_LEFT) . '1B22B8';
            $epcs = [];
            for ($i = 1; $i <= rand(3, 6); $i++) {
                // byte-10 unik per tag: hash kode_barang+i mod 0xFF, format 2 hex
                $serial = strtoupper(str_pad(dechex(abs(crc32($item->kode_barang . '-' . $i)) & 0xFF), 2, '0', STR_PAD_LEFT));
                $epc    = $prefix . $serial . 'F5';  // total: 12+2+2+2 = 20+4 = 24 char

                $epcs[] = $epc;

                $status = $i <= 2 ? 'out_of_stock' : 'in_stock';

                $tag = Tag::updateOrCreate(
                    ['epc_id' => $epc],
                    [
                        'item_id' => $item->id,
                        'status' => $status,
                    ]
                );

                Transaction::create([
                    'epc_id' => $tag->epc_id,
                    'type' => 'in',
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(1, 30)),
                ]);

                if ($status === 'out_of_stock') {
                    Transaction::create([
                        'epc_id' => $tag->epc_id,
                        'type' => 'out',
                        'created_at' => now()->subDays(rand(0, 1)),
                        'updated_at' => now()->subDays(rand(0, 1)),
                    ]);
                }
            }
        }
    }
}
