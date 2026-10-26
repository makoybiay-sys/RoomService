<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\FolioCharge;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Para sa F&B Manager Chart
    public function revenueStats(Request $request)
    {
        $filter = $request->get('filter', 'today');
        $query = Order::where('status', 'CHARGED');

        if ($filter === 'today') {
            $query->whereDate('delivered_at', date('Y-m-d'));
        } elseif ($filter === 'week') {
            $query->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereMonth('delivered_at', date('m'))->whereYear('delivered_at', date('Y'));
        }

        $orders = $query->get();
        
        $chartData = [];
        $totalCash = 0;
        $totalCharge = 0;

        foreach ($orders as $o) {
            $date = date('Y-m-d', strtotime($o->delivered_at));
            if (!isset($chartData[$date])) {
                $chartData[$date] = ['date' => $date, 'cash' => 0, 'charge' => 0];
            }
            if ($o->payment_method === 'CASH') {
                $chartData[$date]['cash'] += $o->total_amount;
                $totalCash += $o->total_amount;
            } else {
                $chartData[$date]['charge'] += $o->total_amount;
                $totalCharge += $o->total_amount;
            }
        }

        return response()->json([
            'chart' => array_values($chartData),
            'totals' => [
                'cash' => $totalCash,
                'charge' => $totalCharge,
                'grand' => $totalCash + $totalCharge
            ]
        ]);
    }

    // Para sa Front Desk Unpaid Folios (INAYOS)
    public function unpaidFolios()
    {
        // Kukunin lang natin yung mga CHARGED orders na nagamitan ng CHARGE_TO_ROOM
        $folios = FolioCharge::whereHas('order', function($q) {
            $q->where('status', 'CHARGED')
              ->where('payment_method', 'CHARGE_TO_ROOM');
        })->get();
            
        // I-group by room gamit ang Collection (safe sa SQLite)
        $rooms = $folios->groupBy('room_number')->map(function($items, $room) {
            return [
                'room_number' => $room,
                'total_orders' => $items->count(),
                'total_charge' => $items->sum('amount')
            ];
        })->values();

        return response()->json($rooms);
    }

    // Para sa Daily Reports
    public function reports(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));

        // 1. Charges per Room
        $rooms = FolioCharge::whereDate('charge_date', $date)
            ->select('room_number', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('room_number')
            ->orderBy('room_number')
            ->get();

        // 2. Top Selling Dishes
        $top = OrderItem::whereHas('order', function($q) use ($date) {
                $q->whereDate('created_at', $date);
            })
            ->select('item_name', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return response()->json([
            'rooms' => $rooms,
            'top' => $top
        ]);
    }
}