<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ScannerState;
use Illuminate\Http\Request;

class ScannerStateController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'active_mode'    => 'required|in:idle,batch_in,single_in,out,check_stock',
            'target_item_id' => 'nullable|exists:items,id',
        ]);

        $state = ScannerState::current();
        $state->active_mode    = $request->active_mode;
        $state->target_item_id = in_array($request->active_mode, ['batch_in', 'single_in'])
            ? $request->target_item_id
            : null;
        $state->updated_at     = now();
        $state->save();


        return response()->json([
            'success'     => true,
            'active_mode' => $state->active_mode,
            'message'     => 'Mode scanner berhasil diubah ke: ' . $state->active_mode,
        ]);
    }

    public function current()
    {
        $state = ScannerState::with('targetItem')->find(1);
        return response()->json($state);
    }
}
