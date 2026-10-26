@extends('layouts.app')

@section('title', 'Front Desk — BLULANE')
@section('body-class', 'staff-body')

@section('styles')
<style>
    .fd-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--border); padding-bottom: 10px; }
    .fd-tab { background: transparent; border: none; padding: 10px 20px; font-size: 14px; font-weight: 800; color: var(--muted); cursor: pointer; border-radius: 8px; transition: all 0.2s; font-family: inherit; }
    .fd-tab.active { background: var(--primary); color: #fff; }
    .fd-content { display: none; }
    .fd-content.active { display: block; }
    
    .charges-table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); }
    .charges-table th, .charges-table td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border); }
    .charges-table th { background: var(--bg-alt); font-size: 11px; text-transform: uppercase; color: var(--primary); }
    .charges-table tr:hover { background: var(--bg-alt); }
    .charges-table .empty-state { text-align: center; color: var(--muted); padding: 40px; }
    
    .viewonly-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; background: rgba(59,130,246,0.12); color: #3b82f6; font-size: 10px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; border-radius: 20px; border: 1px solid rgba(59,130,246,0.25); }
</style>
@endsection

@section('content')
<header class="staff-topbar">
    <div class="staff-topbar-inner">
        <a href="/" class="staff-topbar-logo"><img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE"></a>
        <div class="staff-topbar-title">
            <h1>Front Desk</h1>
            <p><strong>{{ auth()->user()->full_name }}</strong> · {{ auth()->user()->employee_id }}</p>
        </div>
        <div class="staff-topbar-actions">
            <span class="viewonly-badge">👁 View Only</span>
            <button class="theme-toggle staff-theme-toggle" id="themeToggle">☾</button>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="staff-logout-btn">Logout</button>
            </form>
        </div>
    </div>
</header>

<main class="staff-board">
    <div class="fd-tabs">
        <button class="fd-tab active" onclick="switchTab('live', this)">Live Orders</button>
        <button class="fd-tab" onclick="switchTab('charges', this)">Unpaid Room Folios</button>
    </div>

    <!-- TAB 1: LIVE ORDERS -->
    <div id="tab-live" class="fd-content active">
        <div class="staff-columns">
            <div class="staff-column" data-status="PENDING">
                <div class="staff-column-header"><div class="col-header-left"><div><h2>New Orders</h2><p class="col-sub">Received</p></div></div><span class="col-count" id="count-pending">0</span></div>
                <div class="staff-column-body" id="pending"><div class="staff-empty"><p>No new orders</p></div></div>
            </div>
            <div class="staff-column" data-status="READY">
                <div class="staff-column-header"><div class="col-header-left"><div><h2>Ready</h2><p class="col-sub">Waiting for pickup</p></div></div><span class="col-count" id="count-ready">0</span></div>
                <div class="staff-column-body" id="ready"><div class="staff-empty"><p>No ready orders</p></div></div>
            </div>
            <div class="staff-column" data-status="DELIVERING">
                <div class="staff-column-header"><div class="col-header-left"><div><h2>Delivering</h2><p class="col-sub">Out for delivery</p></div></div><span class="col-count" id="count-delivering">0</span></div>
                <div class="staff-column-body" id="delivering"><div class="staff-empty"><p>No deliveries</p></div></div>
            </div>
        </div>
    </div>

    <!-- TAB 2: UNPAID ROOM FOLIOS -->
    <div id="tab-charges" class="fd-content">
        <div class="admin-page-header" style="margin-bottom: 20px;">
            <div><h2>Unpaid Room Folios</h2><p>Mga order na naka-charge sa kwarto at hindi pa bayad.</p></div>
            <button class="btn btn-primary" onclick="loadRoomCharges()">Refresh Data</button>
        </div>
        
        <table class="charges-table">
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Total Orders</th>
                    <th>Total Unpaid Amount</th>
                </tr>
            </thead>
            <tbody id="chargesBody">
                <tr><td colspan="3" class="empty-state">Click "Refresh Data" or switch tabs to load.</td></tr>
            </tbody>
        </table>
    </div>
</main>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

function switchTab(tabName, btnElement) {
    document.querySelectorAll('.fd-tab').forEach(btn => btn.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
    document.querySelectorAll('.fd-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
    
    if (tabName === 'live') loadOrders();
    else if (tabName === 'charges') loadRoomCharges();
}

async function loadOrders() {
    try {
        const res = await fetch('/api/orders?status=PENDING,READY,DELIVERING');
        const orders = await res.json();
        const cols = { PENDING: 'pending', READY: 'ready', DELIVERING: 'delivering' };
        
        Object.keys(cols).forEach(status => {
            document.getElementById(cols[status]).innerHTML = '';
            document.getElementById('count-' + cols[status]).textContent = '0';
        });

        const groups = { PENDING: [], READY: [], DELIVERING: [] };
        orders.forEach(o => groups[o.status] && groups[o.status].push(o));

        Object.keys(cols).forEach(status => {
            const el = document.getElementById(cols[status]);
            const list = groups[status];
            document.getElementById('count-' + cols[status]).textContent = list.length;
            
            if (list.length === 0) {
                el.innerHTML = `<div class="staff-empty"><p>No orders</p></div>`;
                return;
            }

            list.forEach(o => {
                const items = o.items.map(i => `<div class="ticket-item"><span class="ticket-qty">×${i.qty}</span><span class="ticket-name">${i.item_name}</span></div>`).join('');
                let info = '';
                if (status === 'DELIVERING' && o.waiter) info = `<div class="ticket-info">Delivering by: <strong>${o.waiter.full_name}</strong></div>`;
                
                el.innerHTML += `
                    <div class="staff-ticket ticket-${status.toLowerCase()}">
                        <div class="ticket-header">
                            <div class="ticket-room">Room ${o.room_number}</div>
                            <span class="ticket-floor">Floor ${o.floor_number}</span>
                        </div>
                        <div class="ticket-code">${o.order_code}</div>
                        ${info}
                        <div class="ticket-items">${items}</div>
                        <div class="ticket-total">PHP ${parseFloat(o.total_amount).toFixed(2)}</div>
                    </div>`;
            });
        });
    } catch (e) { console.error(e); }
}

async function loadRoomCharges() {
    const tbody = document.getElementById('chargesBody');
    tbody.innerHTML = '<tr><td colspan="3" class="empty-state">Loading unpaid charges...</td></tr>';

    try {
        const res = await fetch('/api/unpaid-folios');
        const rooms = await res.json();

        if (rooms.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="empty-state">Walang unpaid room charges sa ngayon.</td></tr>';
            return;
        }

        let html = '';
        rooms.forEach((room, index) => {
            html += `
                <tr>
                    <td><strong>Room ${room.room_number}</strong></td>
                    <td>${room.total_orders} order(s)</td>
                    <td class="charges-amount" style="font-weight:900; color:var(--primary);">PHP ${parseFloat(room.total_charge).toFixed(2)}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="3" class="empty-state" style="color: red;">Failed to load data.</td></tr>';
    }
}

// Initial Load at Auto-refresh
loadOrders();
setInterval(() => { 
    if (document.getElementById('tab-live').classList.contains('active')) loadOrders(); 
}, 5000);
</script>
@endsection