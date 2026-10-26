<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\FolioCharge;
use App\Models\StatusHistory;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_number' => 'required',
            'guest_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $floor = 1;
        if (preg_match('/^(\d+)/', $data['room_number'], $m)) {
            $num = (int)$m[1];
            if ($num >= 100) $floor = (int)substr((string)$num, 0, -2);
        }

        DB::beginTransaction();
        try {
            $total = 0;
            $verifiedItems = [];
            foreach ($data['items'] as $item) {
                $menu = MenuItem::lockForUpdate()->find($item['id']);
                if ($menu->remaining_stock < $item['qty']) throw new \Exception("Insufficient stock for {$menu->name}");
                $subtotal = $menu->price * $item['qty'];
                $total += $subtotal;
                $verifiedItems[] = ['id'=>$menu->id, 'name'=>$menu->name, 'category'=>$menu->category, 'qty'=>$item['qty'], 'price'=>$menu->price, 'subtotal'=>$subtotal];
            }

            $date = date('Ymd');
            $count = Order::whereDate('created_at', date('Y-m-d'))->count() + 1;
            $orderCode = 'RS-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $order = Order::create([
                'order_code' => $orderCode, 'floor_number' => $floor, 'room_number' => $data['room_number'],
                'guest_name' => $data['guest_name'] ?? '', 'special_notes' => $data['notes'] ?? '', 'total_amount' => $total, 'status' => 'PENDING'
            ]);

            foreach ($verifiedItems as $v) {
                OrderItem::create(['order_id' => $order->id, 'menu_item_id' => $v['id'], 'item_name' => $v['name'], 'qty' => $v['qty'], 'unit_price' => $v['price'], 'subtotal' => $v['subtotal']]);
                $menu = MenuItem::find($v['id']);
                $menu->decrement('remaining_stock', $v['qty']);
                if ($menu->remaining_stock <= 0) $menu->update(['is_available' => false]);
                StockLog::create(['menu_item_id' => $v['id'], 'change_qty' => -$v['qty'], 'reason' => "Order $orderCode", 'reference_id' => $order->id]);
            }

            StatusHistory::create(['order_id' => $order->id, 'new_status' => 'PENDING']);
            FolioCharge::create(['order_id' => $order->id, 'room_number' => $data['room_number'], 'amount' => $total, 'charge_date' => date('Y-m-d')]);

            DB::commit();
            return response()->json(['success' => true, 'order_id' => $order->id, 'total' => $total]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request)
    {
        $query = Order::with(['items', 'waiter', 'coffeeManager']);
        if ($request->has('status')) $query->whereIn('status', explode(',', $request->status));
        if ($request->has('floor') && $request->floor !== '') $query->where('floor_number', $request->floor);
        if ($request->has('has_cancel_request')) $query->whereNotNull('cancel_requested_at');
        if ($request->has('room')) $query->where('room_number', $request->room);
        
        // IDINAGDAG: updated_at desc para laging latest ang update sa taas
        return response()->json($query->orderBy('updated_at', 'desc')->get());
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required']);
        $order = Order::findOrFail($id);
        $old = $order->status;
        $updates = ['status' => $request->status];

        if ($request->status === 'PREPARING' && $request->has('estimated_minutes')) $updates['estimated_minutes'] = $request->estimated_minutes;
        if ($request->status === 'READY' && $request->has('manager_id')) $updates['coffee_manager_id'] = $request->manager_id;
        if ($request->status === 'DELIVERING' && $request->has('waiter_id')) { $updates['waiter_id'] = $request->waiter_id; $updates['assigned_at'] = now(); }
        if ($request->status === 'DELIVERED') {
            $updates['delivered_at'] = now();
            if ($request->payment_method) { $updates['payment_method'] = $request->payment_method; $updates['payment_status'] = 'PAID'; }
        }

        $order->update($updates);
        if ($request->status === 'CHARGED') $order->folioCharge()->update(['posted_to_frontdesk' => true, 'posted_at' => now()]);
        StatusHistory::create(['order_id' => $id, 'old_status' => $old, 'new_status' => $request->status, 'changed_by' => auth()->id()]);
        return response()->json(['success' => true]);
    }

    public function assignWaiter(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'DELIVERING', 'waiter_id' => $request->waiter_id, 'coffee_manager_id' => $request->manager_id, 'assigned_at' => now()]);
        StatusHistory::create(['order_id' => $id, 'old_status' => 'READY', 'new_status' => 'DELIVERING']);
        return response()->json(['success' => true, 'waiter_name' => $order->waiter->full_name]);
    }

    public function requestCancel(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        if ($order->status === 'PENDING') {
            $order->update(['status' => 'CANCELLED', 'cancel_reason' => $request->reason]);
            foreach ($order->items as $item) MenuItem::find($item->menu_item_id)->increment('remaining_stock', $item->qty);
            StatusHistory::create(['order_id' => $id, 'old_status' => 'PENDING', 'new_status' => 'CANCELLED']);
            return response()->json(['success' => true, 'message' => 'Cancelled successfully.']);
        } else if (in_array($order->status, ['PREPARING', 'READY', 'DELIVERING'])) {
            $order->update(['cancel_reason' => $request->reason, 'cancel_requested_at' => now()]);
            return response()->json(['success' => true, 'message' => 'Request sent to manager.']);
        }
        return response()->json(['success' => false, 'message' => 'Cannot cancel at this stage.']);
    }

    public function handleCancel(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        if ($request->action === 'approve') {
            $order->update(['status' => 'CANCELLED', 'cancel_requested_at' => null]);
            foreach ($order->items as $item) MenuItem::find($item->menu_item_id)->increment('remaining_stock', $item->qty);
            StatusHistory::create(['order_id' => $id, 'old_status' => $order->status, 'new_status' => 'CANCELLED', 'changed_by' => auth()->id()]);
            return response()->json(['success' => true, 'message' => 'Order cancelled.']);
        } else {
            $order->update(['cancel_reason' => null, 'cancel_requested_at' => null]);
            return response()->json(['success' => true, 'message' => 'Request rejected.']);
        }
    }

    public function voidOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        if (!in_array($order->status, ['DELIVERED', 'CHARGED'])) {
            return response()->json(['success' => false, 'message' => 'Only delivered/charged orders can be voided.']);
        }
        $order->update(['status' => 'VOIDED', 'void_reason' => $request->reason, 'voided_at' => now()]);
        if ($order->payment_method === 'CHARGE_TO_ROOM') $order->folioCharge()->update(['posted_to_frontdesk' => false]);
        foreach ($order->items as $item) MenuItem::find($item->menu_item_id)->increment('remaining_stock', $item->qty);
        StatusHistory::create(['order_id' => $id, 'old_status' => $order->status, 'new_status' => 'VOIDED', 'changed_by' => auth()->id()]);
        return response()->json(['success' => true, 'message' => 'Order voided and stock returned.']);
    }

    public function notifyArrival($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['waiter_arrived' => true, 'arrived_at' => now(), 'arrival_seen_by_guest' => false]);
        return response()->json(['success' => true]);
    }

    public function checkArrival(Request $request)
    {
        $ids = explode(',', $request->ids);
        $notifs = Order::whereIn('id', $ids)->where('waiter_arrived', true)->where('arrival_seen_by_guest', false)->get();
        return response()->json($notifs);
    }

    public function markArrivalSeen(Request $request)
    {
        Order::findOrFail($request->order_id)->update(['arrival_seen_by_guest' => true]);
        return response()->json(['success' => true]);
    }
}