@extends('layouts.app')
@section('title', 'Waiter Dashboard — BLULANE')
@section('body-class', 'staff-body')
@section('styles')
<style>
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
    .modal-overlay.active { display: flex; }
    .modal-box { background: var(--card); border-radius: 16px; width: 100%; max-width: 400px; padding: 24px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .modal-box h2 { font-size: 18px; font-weight: 900; margin-bottom: 8px; }
    .btn-choice { display: block; width: 100%; padding: 14px; border-radius: 10px; border: 2px solid var(--border); background: var(--bg-alt); color: var(--text); font-size: 14px; font-weight: 800; margin-bottom: 12px; cursor: pointer; }
    .btn-choice:hover { border-color: var(--primary); }
    .btn-choice.cash { border-color: #10b981; color: #10b981; }
    .btn-choice.room { border-color: #3b82f6; color: #3b82f6; }
    .btn-cancel { background: transparent; border: none; color: var(--muted); text-decoration: underline; cursor: pointer; }
</style>
@endsection
@section('content')
<header class="staff-topbar">
    <div class="staff-topbar-inner">
        <a href="/" class="staff-topbar-logo"><img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE"></a>
        <div class="staff-topbar-title"><h1>Waiter</h1><p>{{ auth()->user()->full_name }}</p></div>
        <div class="staff-topbar-actions">
            <span class="live-badge"><span class="live-dot"></span> Live</span>
            <button class="theme-toggle staff-theme-toggle" id="themeToggle">☾</button>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">@csrf<button type="submit" class="staff-logout-btn">Logout</button></form>
        </div>
    </div>
</header>
<main class="staff-board">
    <div class="staff-single-column">
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>My Deliveries</h2><p class="col-sub">Orders assigned to you</p></div></div><span class="col-count" id="count-delivering">0</span></div><div class="staff-column-body" id="delivering"><div class="staff-empty"><p>No deliveries assigned</p></div></div></div>
    </div>
</main>
<div class="modal-overlay" id="paymentModal">
    <div class="modal-box">
        <h2>Confirm Delivery</h2>
        <p>Pumili ng paraan ng bayad para ma-finalize ang order.</p>
        <button class="btn-choice cash" onclick="confirmDelivery('CASH')">💵 Cash Payment</button>
        <button class="btn-choice room" onclick="confirmDelivery('CHARGE_TO_ROOM')">🏨 Charge to Room</button>
        <button class="btn-cancel" onclick="closePaymentModal()">Cancel</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
const CURRENT_WAITER_ID = {{ auth()->user()->id }};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
let selectedOrderId = null;

async function loadOrders() {
    try {
        const res = await fetch(`/api/orders?status=DELIVERING`);
        let allOrders = await res.json();
        let orders = allOrders.filter(o => o.waiter_id == CURRENT_WAITER_ID);
        const el = document.getElementById('delivering');
        el.innerHTML = ''; document.getElementById('count-delivering').textContent = orders.length;
        if (orders.length === 0) return el.innerHTML = `<div class="staff-empty"><p>No deliveries assigned</p></div>`;
        orders.forEach(o => {
            const items = o.items.map(i => `<div class="ticket-item"><span class="ticket-qty">×${i.qty}</span><span class="ticket-name">${i.item_name}</span></div>`).join('');
            const hasArrived = o.waiter_arrived == 1;
            const arrivedHtml = hasArrived ? `<div class="ticket-arrived">✓ Arrival notified</div>` : `<button class="ticket-btn ticket-btn-arrived" onclick="notifyArrival(${o.id})">Notify Arrival</button>`;
            el.innerHTML += `<div class="staff-ticket ticket-delivering"><div class="ticket-header"><div class="ticket-room">Room ${o.room_number}</div><span class="ticket-floor">Floor ${o.floor_number}</span></div><div class="ticket-code">${o.order_code}</div><div class="ticket-items">${items}</div><div class="ticket-total">PHP ${parseFloat(o.total_amount).toFixed(2)}</div>${arrivedHtml}<button class="ticket-btn ticket-btn-delivering" onclick="openPaymentModal(${o.id})" style="margin-top:8px;">Mark Delivered & Pay</button></div>`;
        });
    } catch (e) { console.error(e); }
}

async function notifyArrival(id) {
    await fetch(`/api/orders/${id}/notify`, { method: 'POST', headers: {'X-CSRF-TOKEN': csrfToken} });
    loadOrders();
}

function openPaymentModal(orderId) { selectedOrderId = orderId; document.getElementById('paymentModal').classList.add('active'); }
function closePaymentModal() { selectedOrderId = null; document.getElementById('paymentModal').classList.remove('active'); }

async function confirmDelivery(paymentMethod) {
    if (!selectedOrderId) return;
    await fetch(`/api/orders/${selectedOrderId}/status`, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({ status: 'DELIVERED', payment_method: paymentMethod }) });
    await fetch(`/api/orders/${selectedOrderId}/status`, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({ status: 'CHARGED' }) });
    closePaymentModal(); loadOrders();
}

loadOrders(); setInterval(loadOrders, 5000);
</script>
@endsection