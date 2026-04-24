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

            // Create sample tags for each item
            $epcs = [];
            for ($i = 1; $i <= rand(3, 6); $i++) {
                $epc = strtoupper(str_pad(dechex(crc32($item->kode_barang . $i . time())), 16, '0', STR_PAD_LEFT));
                $epcs[] = $epc;

                $status = $i <= 2 ? 'out_of_stock' : 'in_stock';

                $tag = Tag::updateOrCreate(
                    ['epc_id' => substr($epc, 0, 16)],
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
