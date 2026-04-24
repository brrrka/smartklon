<?php

namespace App\Events;

use App\Models\Tag;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RfidScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(Tag $tag, string $transactionType, string $message)
    {
        $tag->load('item');

        $this->payload = [
            'epc_id'           => $tag->epc_id,
            'item_id'          => $tag->item_id,
            'kode_barang'      => $tag->item->kode_barang ?? '-',
            'nama_barang'      => $tag->item->nama_barang ?? '-',
            'tag_status'       => $tag->status,
            'transaction_type' => $transactionType,
            'message'          => $message,
            'timestamp'        => now()->toDateTimeString(),
            'in_stock_total'   => $tag->item ? $tag->item->tags()->where('status', 'in_stock')->count() : 0,
            'out_of_stock_total' => $tag->item ? $tag->item->tags()->where('status', 'out_of_stock')->count() : 0,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('rfid-scanner'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'rfid.scanned';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
