<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Tag;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalItems       = Item::count();
        $totalInStock     = Tag::where('status', 'in_stock')->count();
        $totalOutOfStock  = Tag::where('status', 'out_of_stock')->count();
        $totalTags        = Tag::count();

        $today = today();
        $stockInToday  = Transaction::where('type', 'in')->whereDate('created_at', $today)->count();
        $stockOutToday = Transaction::where('type', 'out')->whereDate('created_at', $today)->count();

        $recentTransactions = Transaction::with(['tag.item'])
            ->latest()
            ->take(10)
            ->get();

        $topItems = Item::withCount([
            'tags as in_stock_count'    => fn($q) => $q->where('status', 'in_stock'),
            'tags as out_stock_count'   => fn($q) => $q->where('status', 'out_of_stock'),
            'tags as total_tags_count',
        ])->orderByDesc('in_stock_count')->take(5)->get();

        return view('dashboard', compact(
            'totalItems',
            'totalInStock',
            'totalOutOfStock',
            'totalTags',
            'stockInToday',
            'stockOutToday',
            'recentTransactions',
            'topItems'
        ));
    }
}
