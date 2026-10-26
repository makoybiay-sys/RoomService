@extends('layouts.app')
@section('title', 'Kitchen Dashboard — BLULANE')
@section('body-class', 'staff-body')
@section('content')
<header class="staff-topbar">
    <div class="staff-topbar-inner">
        <a href="/" class="staff-topbar-logo"><img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE"></a>
        <div class="staff-topbar-title"><div><h1>Kitchen Dashboard</h1><p>Signed in as <strong>{{ auth()->user()->full_name }}</strong></p></div></div>
        <div class="staff-topbar-actions">
            <span class="live-badge"><span class="live-dot"></span> Live</span>
            <button class="theme-toggle staff-theme-toggle" id="themeToggle">☾</button>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">@csrf<button type="submit" class="staff-logout-btn">Logout</button></form>
        </div>
    </div>
</header>
<main class="staff-board">
    <div class="staff-columns">
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>Pending</h2><p class="col-sub">New orders</p></div></div><span class="col-count" id="count-pending">0</span></div><div class="staff-column-body" id="pending"><div class="staff-empty"><p>No pending orders</p></div></div></div>
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>Preparing</h2><p class="col-sub">Currently cooking</p></div></div><span class="col-count" id="count-preparing">0</span></div><div class="staff-column-body" id="preparing"><div class="staff-empty"><p>Nothing cooking</p></div></div></div>
        <div class="staff-column"><div class="staff-column-header"><div class="col-header-left"><div><h2>Ready</h2><p class="col-sub">Awaiting pickup</p></div></div><span class="col-count" id="count-ready">0</span></div><div class="staff-column-body" id="ready"><div class="staff-empty"><p>No ready orders</p></div></div></div>
    </div>
</main>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
let lastHash = '';

async function loadOrders() {
    try {
        const res = await fetch('/api/orders?status=PENDING,PREPARING,READY');
        const orders = await res.json();
        
        const currentHash = orders.map(o => `${o.id}-${o.status}`).join('|');
        if (currentHash === lastHash) return;
        lastHash = currentHash;

        const cols = { PENDING: 'pending', PREPARING: 'preparing', READY: 'ready' };
        Object.keys(cols).forEach(s => { document.getElementById(cols[s]).innerHTML = ''; document.getElementById('count-'+cols[s]).textContent = '0'; });
        const groups = { PENDING: [], PREPARING: [], READY: [] };
        orders.forEach(o => groups[o.status] && groups[o.status].push(o));
        
        Object.keys(cols).forEach(status => {
            const el = document.getElementById(cols[status]);
            const list = groups[status];
            document.getElementById('count-'+cols[status]).textContent = list.length;
            if (list.length === 0) { const msgs = { PENDING: 'No pending orders', PREPARING: 'Nothing cooking', READY: 'No ready orders' }; el.innerHTML = `<div class="staff-empty"><p>${msgs[status]}</p></div>`; return; }
            
            list.forEach(o => {
                const items = o.items.map(i => `<div class="ticket-item"><span class="ticket-qty">×${i.qty}</span><span class="ticket-name">${i.item_name}</span></div>`).join('');
                const next = { PENDING: 'PREPARING', PREPARING: 'READY', READY: 'DELIVERING' }[status];
                const label = { PENDING: 'Start Preparing', PREPARING: 'Mark Ready', READY: 'Send to Waiter' }[status];
                el.innerHTML += `<div class="staff-ticket ticket-${status.toLowerCase()}"><div class="ticket-header"><div class="ticket-room">Room ${o.room_number}</div><span class="ticket-floor">Floor ${o.floor_number}</span></div><div class="ticket-code">${o.order_code}</div><div class="ticket-items">${items}</div><button class="ticket-btn ticket-btn-${status.toLowerCase()}" onclick="advance(${o.id}, '${next}')">${label}</button></div>`;
            });
        });
    } catch (e) { console.error(e); }
}

async function advance(id, status) {
    await fetch(`/api/orders/${id}/status`, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({ status: status }) });
    loadOrders();
}

loadOrders(); setInterval(loadOrders, 5000);
</script>
@endsection