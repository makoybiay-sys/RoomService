@extends('layouts.app')
@section('title', 'Coffee Club Manager — BLULANE')
@section('body-class', 'staff-body')
@section('styles')
<style>
    .staff-board { padding: 16px !important; max-width: 100% !important; margin: 0 auto !important; width: 100%; overflow-x: hidden; }
    .staff-columns { display: flex !important; gap: 12px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding: 0 16px 20px 16px !important; align-items: flex-start; justify-content: flex-start; }
    .staff-column { flex: 1 1 0; min-width: 240px; max-width: 100%; }
    .staff-columns::-webkit-scrollbar { height: 8px; }
    .staff-columns::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
    @media (max-width: 1200px) { .staff-column { flex: 0 0 260px; } }
    @keyframes fadeInTicket { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .staff-ticket { animation: fadeInTicket 0.3s ease-out; }
    .cancel-btn { width: 100%; border: none; border-radius: 9px; padding: 12px; font-size: 13px; font-weight: 800; cursor: pointer; transition: all 0.2s; font-family: inherit; margin-top: 8px; }
    .cancel-btn.approve { background: #10b981; color: #fff; }
    .cancel-btn.reject { background: var(--bg-alt); border: 1px solid var(--border); color: var(--danger); }
    .cancel-reason { background: var(--bg-alt); border: 1px solid var(--border); border-radius: 8px; padding: 10px; font-size: 11.5px; color: var(--text); margin-bottom: 10px; font-style: italic; }
    .btn-refresh { padding: 8px 14px; background: var(--bg-alt); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 11.5px; font-weight: 800; cursor: pointer; transition: all 0.2s; font-family: inherit; }
    .btn-refresh:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
    .modal-overlay.active { display: flex; }
    .modal-box { background: var(--card); border-radius: 16px; width: 100%; max-width: 400px; padding: 24px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .modal-input { width: 100%; padding: 14px; border-radius: 10px; border: 2px solid var(--border); font-size: 18px; font-weight: 800; text-align: center; font-family: inherit; margin-bottom: 16px; background: var(--bg-alt); color: var(--text); }
    .btn-confirm { width: 100%; padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 800; cursor: pointer; margin-bottom: 10px; }
    .btn-cancel-modal { background: transparent; border: none; color: var(--muted); font-size: 12px; font-weight: 600; text-decoration: underline; cursor: pointer; }
    .waiter-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; max-height: 250px; overflow-y: auto; text-align: left; }
    .waiter-item { padding: 12px 14px; background: var(--bg-alt); border: 1.5px solid var(--border); border-radius: 10px; cursor: pointer; transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; }
    .waiter-item.selected { border-color: var(--primary); background: var(--primary-light); }
    .waiter-name { font-size: 13.5px; font-weight: 800; }
    .waiter-id { font-size: 11px; color: var(--muted); font-family: monospace; }
</style>
@endsection
@section('content')
<header class="staff-topbar">
    <div class="staff-topbar-inner" style="max-width: 1600px; padding: 0 16px; margin: 0 auto;">
        <a href="/" class="staff-topbar-logo"><img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE"></a>
        <div class="staff-topbar-title"><h1>Coffee Club — POS</h1><p>{{ auth()->user()->full_name }}</p></div>
        <div class="staff-topbar-actions">
            <button class="btn-refresh" onclick="loadOrders(true)">Refresh</button>
            <button class="theme-toggle staff-theme-toggle" id="themeToggle">☾</button>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">@csrf<button type="submit" class="staff-logout-btn">Logout</button></form>
        </div>
    </div>
</header>
<main class="staff-board">
    <div class="staff-columns">
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>New Orders</h2><p class="col-sub">Waiting</p></div></div><span class="col-count" id="count-pending">0</span></div><div class="staff-column-body" id="pending"><div class="staff-empty"><p>No new orders</p></div></div></div>
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>Preparing</h2><p class="col-sub">In progress</p></div></div><span class="col-count" id="count-preparing">0</span></div><div class="staff-column-body" id="preparing"><div class="staff-empty"><p>Nothing preparing</p></div></div></div>
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>Ready to Assign</h2><p class="col-sub">Assign waiter</p></div></div><span class="col-count" id="count-ready">0</span></div><div class="staff-column-body" id="ready"><div class="staff-empty"><p>No orders ready</p></div></div></div>
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>Out for Delivery</h2><p class="col-sub">Being delivered</p></div></div><span class="col-count" id="count-delivering">0</span></div><div class="staff-column-body" id="delivering"><div class="staff-empty"><p>No deliveries</p></div></div></div>
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>Cancellations</h2><p class="col-sub">Needs approval</p></div></div><span class="col-count" id="count-cancel">0</span></div><div class="staff-column-body" id="cancellations"><div class="staff-empty"><p>No requests</p></div></div></div>
    </div>
</main>
<div class="modal-overlay" id="estimateModal"><div class="modal-box"><h2>Set Estimated Time</h2><p>Ilang minuto bago maluto?</p><input type="number" id="estimateMinutes" class="modal-input" min="1" max="120" value="15"><button class="btn-confirm" onclick="confirmPreparing()">Confirm & Start Preparing</button><button class="btn-cancel-modal" onclick="closeEstimateModal()">Cancel</button></div></div>
<div class="modal-overlay" id="waiterModal"><div class="modal-box" style="text-align: left;"><h2 style="text-align: center;">Assign Waiter</h2><div class="waiter-list" id="waiterList"></div><button class="btn-confirm" onclick="confirmAssignWaiter()">Assign Waiter</button><button class="btn-cancel-modal" onclick="closeWaiterModal()" style="display: block; margin: 0 auto;">Cancel</button></div></div>
@endsection

@section('scripts')
<script>
// JS COFFEE MANAGER
const CURRENT_MANAGER_ID = {{ auth()->user()->id }};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
let selectedOrderId = null, selectedWaiterId = null, currentWaiters = [], lastOrderHash = '';

async function loadOrders(forceRender = false) {
    try {
        const res = await fetch('/api/orders?status=PENDING,PREPARING,READY,DELIVERING');
        const orders = await res.json();
        const cancelRes = await fetch('/api/orders?has_cancel_request=1');
        const cancelOrders = await cancelRes.json();

        const hashString = orders.map(o => `${o.id}-${o.status}`).join('|') + '||' + cancelOrders.map(o => `${o.id}`).join('|');
        if (hashString === lastOrderHash && !forceRender) return;
        lastOrderHash = hashString;

        const cols = { PENDING: 'pending', PREPARING: 'preparing', READY: 'ready', DELIVERING: 'delivering' };
        Object.keys(cols).forEach(s => { document.getElementById(cols[s]).innerHTML = ''; document.getElementById('count-'+cols[s]).textContent = '0'; });
        document.getElementById('cancellations').innerHTML = ''; document.getElementById('count-cancel').textContent = cancelOrders.length;

        const groups = { PENDING: [], PREPARING: [], READY: [], DELIVERING: [] };
        orders.forEach(o => groups[o.status] && groups[o.status].push(o));
        const waitersRes = await fetch('/api/waiters'); currentWaiters = await waitersRes.json();

        Object.keys(cols).forEach(status => {
            const el = document.getElementById(cols[status]), list = groups[status];
            document.getElementById('count-'+cols[status]).textContent = list.length;
            if (list.length === 0) { const msgs = { PENDING: 'No new orders', PREPARING: 'Nothing preparing', READY: 'No orders ready', DELIVERING: 'No deliveries in progress' }; el.innerHTML = `<div class="staff-empty"><p>${msgs[status]}</p></div>`; return; }
            list.forEach(o => {
                const items = o.items.map(i => `<div class="ticket-item"><span class="ticket-qty">×${i.qty}</span><span class="ticket-name">${i.item_name}</span></div>`).join('');
                let btn = '';
                if (status === 'PENDING') btn = `<button class="ticket-btn ticket-btn-pending" onclick='openEstimateModal(${JSON.stringify(o).replace(/'/g, "&#39;")})'>Start Preparing</button>`;
                else if (status === 'PREPARING') btn = `<button class="ticket-btn ticket-btn-preparing" onclick="markReady(${o.id})">Mark as Ready</button>`;
                else if (status === 'READY') btn = `<button class="ticket-btn ticket-btn-ready" onclick="openWaiterModal(${o.id})">Assign Waiter</button>`;
                else if (status === 'DELIVERING') btn = o.waiter ? `<div class="ticket-info">Delivering by: <strong>${o.waiter.full_name}</strong></div>` : '';
                el.innerHTML += `<div class="staff-ticket ticket-${status.toLowerCase()}"><div class="ticket-header"><div class="ticket-room">Room ${o.room_number}</div><span class="ticket-floor">Floor ${o.floor_number}</span></div><div class="ticket-code">${o.order_code}</div><div class="ticket-items">${items}</div><div class="ticket-total">PHP ${parseFloat(o.total_amount).toFixed(2)}</div>${btn}</div>`;
            });
        });

        if (cancelOrders.length === 0) document.getElementById('cancellations').innerHTML = `<div class="staff-empty"><p>No cancellation requests</p></div>`;
        else {
            cancelOrders.forEach(o => {
                const items = o.items.map(i => `<div class="ticket-item"><span class="ticket-qty">×${i.qty}</span><span class="ticket-name">${i.item_name}</span></div>`).join('');
                document.getElementById('cancellations').innerHTML += `<div class="staff-ticket" style="border-left-color: var(--danger);"><div class="ticket-header"><div class="ticket-room">Room ${o.room_number}</div><span class="ticket-floor" style="background:var(--danger); color:#fff;">Cancel</span></div><div class="ticket-code">${o.order_code}</div><div class="cancel-reason">Reason: ${o.cancel_reason || 'No reason'}</div><div class="ticket-items">${items}</div><div class="ticket-total">PHP ${parseFloat(o.total_amount).toFixed(2)}</div><button class="cancel-btn approve" onclick="handleCancel(${o.id}, 'approve')">Approve Cancel</button><button class="cancel-btn reject" onclick="handleCancel(${o.id}, 'reject')">Reject</button></div>`;
            });
        }
    } catch (e) { console.error('Load orders error:', e); }
}

function openEstimateModal(order) {
    selectedOrderId = order.id;
    const categoryTimes = { 'Main Course': 20, 'Appetizer': 10, 'Dessert': 10, 'Beverage': 5 };
    let maxPrepTime = 5;
    order.items.forEach(item => { const t = categoryTimes[item.category] || 15; if (t > maxPrepTime) maxPrepTime = t; });
    document.getElementById('estimateMinutes').value = maxPrepTime;
    document.getElementById('estimateModal').classList.add('active');
}
function closeEstimateModal() { selectedOrderId = null; document.getElementById('estimateModal').classList.remove('active'); }

async function confirmPreparing() {
    if (!selectedOrderId) return;
    const mins = parseInt(document.getElementById('estimateMinutes').value);
    if (!mins || mins < 1) return alert('Please enter valid minutes');
    await fetch(`/api/orders/${selectedOrderId}/status`, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({ status: 'PREPARING', manager_id: CURRENT_MANAGER_ID, estimated_minutes: mins }) });
    closeEstimateModal(); loadOrders(true);
}

async function markReady(orderId) {
    await fetch(`/api/orders/${orderId}/status`, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({ status: 'READY' }) });
    loadOrders(true);
}

function openWaiterModal(orderId) {
    selectedOrderId = orderId; selectedWaiterId = null;
    const listEl = document.getElementById('waiterList'); listEl.innerHTML = '';
    if (currentWaiters.length === 0) listEl.innerHTML = '<p style="text-align:center; color:var(--muted);">Walang available na waiter.</p>';
    else {
        currentWaiters.forEach(w => {
            const item = document.createElement('div'); item.className = 'waiter-item';
            item.innerHTML = `<div><div class="waiter-name">${w.full_name}</div><div class="waiter-id">${w.employee_id}</div></div><div class="waiter-check" style="width:20px;height:20px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;"></div>`;
            item.onclick = () => selectWaiter(w.id, item); listEl.appendChild(item);
        });
    }
    document.getElementById('waiterModal').classList.add('active');
}
function selectWaiter(id, el) {
    selectedWaiterId = id;
    document.querySelectorAll('.waiter-item').forEach(item => { item.classList.remove('selected'); const check = item.querySelector('.waiter-check'); check.innerHTML = ''; check.style.background = 'transparent'; check.style.borderColor = 'var(--border)'; });
    el.classList.add('selected'); const check = el.querySelector('.waiter-check'); check.innerHTML = '✓'; check.style.background = 'var(--primary)'; check.style.borderColor = 'var(--primary)'; check.style.color = '#fff';
}
function closeWaiterModal() { selectedOrderId = null; selectedWaiterId = null; document.getElementById('waiterModal').classList.remove('active'); }

async function confirmAssignWaiter() {
    if (!selectedOrderId || !selectedWaiterId) return alert('Please select a waiter');
    await fetch(`/api/orders/${selectedOrderId}/assign`, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({ waiter_id: selectedWaiterId, manager_id: CURRENT_MANAGER_ID }) });
    closeWaiterModal(); loadOrders(true);
}

async function handleCancel(orderId, action) {
    if (!confirm(`Are you sure you want to ${action} this cancellation?`)) return;
    await fetch(`/api/orders/${orderId}/cancel-handle`, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({ action: action }) });
    loadOrders(true);
}

loadOrders(true); setInterval(() => { loadOrders(false); }, 5000);
</script>
@endsection