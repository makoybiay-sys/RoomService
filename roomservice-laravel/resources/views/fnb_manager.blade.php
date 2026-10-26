@extends('layouts.app')

@section('title', 'F&B Manager — BLULANE')
@section('body-class', 'admin-body')

@section('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* --- MGA DAGDAG NA CSS PARA SA LAYOUT --- */
    .admin-main { padding: 24px; }
    .admin-tab-content { display: none; animation: fadeIn 0.3s ease; }
    .admin-tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    .fnb-container { max-width: 1400px; margin: 0 auto; padding: 24px; }
    .fnb-section { background: var(--card); border-radius: 16px; border: 1px solid var(--border); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow); }
    .fnb-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .fnb-header h2 { font-size: 22px; font-weight: 900; color: var(--text-strong); margin-bottom: 4px; }
    .fnb-header p { font-size: 13px; color: var(--muted); }
    
    .filter-group { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .filter-item { display: flex; flex-direction: column; gap: 6px; min-width: 200px; }
    .filter-item label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; }
    .filter-item select { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-alt); color: var(--text); font-family: inherit; outline: none; }
    
    .table-wrapper { overflow-x: auto; border-radius: 12px; border: 1px solid var(--border); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { padding: 14px; text-align: left; background: var(--bg-alt); font-size: 11px; text-transform: uppercase; color: var(--primary); border-bottom: 1px solid var(--border); }
    .data-table td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 13px; }
    .data-table tr:last-child td { border-bottom: none; }
    
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; color: #fff; text-transform: uppercase; white-space: nowrap; }
    .btn-void { padding: 6px 12px; border-radius: 6px; border: 1px solid var(--danger); background: rgba(239,68,68,0.1); color: var(--danger); font-size: 11px; font-weight: 800; cursor: pointer; transition: all 0.2s; }
    .btn-void:hover { background: var(--danger); color: #fff; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--card); border-radius: 14px; padding: 20px; border: 1px solid var(--border); border-left-width: 4px; }
    .stat-card p { font-size: 11px; color: var(--muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-card h3 { font-size: 28px; font-weight: 900; margin-top: 6px; }
    
    .chart-container { width: 100%; height: 350px; position: relative; }
</style>
@endsection

@section('content')
<!-- TOPBAR -->
<header class="admin-topbar">
    <div class="admin-topbar-inner">
        <button class="admin-burger" id="adminBurger"><span></span><span></span><span></span></button>
        <a href="/" class="admin-topbar-logo"><img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE"></a>
        <div class="admin-topbar-title">
            <h1>F&B Manager</h1>
            <p><strong>{{ auth()->user()->full_name }}</strong> · {{ auth()->user()->employee_id }}</p>
        </div>
        <div class="admin-topbar-actions">
            <button class="theme-toggle admin-theme-toggle" id="themeToggle">☾</button>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="staff-logout-btn">Logout</button>
            </form>
        </div>
    </div>
</header>

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-header">
        <h2>Menu</h2>
        <button class="admin-sidebar-close" id="adminSidebarClose">×</button>
    </div>
    <nav class="admin-sidebar-nav">
        <button class="admin-nav-item active" data-tab="orders"><span class="nav-label">All Orders</span><span class="nav-arrow">›</span></button>
        <button class="admin-nav-item" data-tab="menu"><span class="nav-label">Menu Items</span><span class="nav-arrow">›</span></button>
        <button class="admin-nav-item" data-tab="staff"><span class="nav-label">Staff Accounts</span><span class="nav-arrow">›</span></button>
        <button class="admin-nav-item" data-tab="settings"><span class="nav-label">Settings</span><span class="nav-arrow">›</span></button>
        
        <div class="admin-sidebar-divider"></div>
        <a href="/" class="admin-nav-item admin-nav-link"><span class="nav-label">Back to Guest Site</span><span class="nav-arrow">›</span></a>
    </nav>
</aside>
<div class="admin-backdrop" id="adminBackdrop"></div>

<main class="fnb-container">
    <!-- ==========================================
         TAB 1: ALL ORDERS & PROFIT (Default)
         ========================================== -->
    <div id="tab-orders" class="admin-tab-content active">
        <div class="fnb-section">
            <div class="fnb-header">
                <div><h2>All Orders</h2><p>View and manage all orders</p></div>
            </div>
            <div class="filter-group">
                <div class="filter-item">
                    <label>Status</label>
                    <select id="orderFilterStatus" onchange="loadOrders()">
                        <option value="">All Statuses</option>
                        <option>PENDING</option><option>PREPARING</option><option>READY</option>
                        <option>DELIVERING</option><option>DELIVERED</option>
                        <option value="CHARGED">COMPLETED</option><option>CANCELLED</option><option>VOIDED</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label>Payment Method</label>
                    <select id="orderFilterPayment" onchange="loadOrders()">
                        <option value="">All Payments</option>
                        <option value="CASH">Cash</option>
                        <option value="CHARGE_TO_ROOM">Charge to Room</option>
                    </select>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Order</th><th>Room</th><th>Guest</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead>
                    <tbody id="ordersRows"><tr><td colspan="9" style="text-align:center; color:var(--muted); font-style:italic;">Loading orders...</td></tr></tbody>
                </table>
            </div>
        </div>

        <div class="fnb-section">
            <div class="fnb-header">
                <div><h2>Profit & Revenue</h2><p>Revenue breakdown based on completion date</p></div>
                <div class="filter-item"><label>Time Period</label><select id="profitFilter" onchange="loadProfitData()"><option value="today">Today</option><option value="week">This Week</option><option value="month">This Month</option></select></div>
            </div>
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: var(--primary);"><p>Total Revenue</p><h3 style="color: var(--primary);" id="statTotal">PHP 0.00</h3></div>
                <div class="stat-card" style="border-left-color: #10b981;"><p>Cash on Hand</p><h3 style="color: #10b981;" id="statCash">PHP 0.00</h3></div>
                <div class="stat-card" style="border-left-color: #3b82f6;"><p>Charge to Room</p><h3 style="color: #3b82f6;" id="statCharge">PHP 0.00</h3></div>
            </div>
            <div class="chart-container"><canvas id="profitChart"></canvas></div>
        </div>
    </div>

    <!-- ==========================================
         TAB 2: MENU ITEMS
         ========================================== -->
    <div id="tab-menu" class="admin-tab-content">
        <div class="admin-page-header" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div><h2 style="font-size: 22px; font-weight: 900;">Menu Items</h2><p>Manage food items, prices, and stock</p></div>
            <div><button class="btn btn-outline" onclick="resetAllStock()">Reset Stock</button></div>
        </div>
        <div class="admin-card admin-table-card" style="background: var(--card); border-radius: 14px; border: 1px solid var(--border); overflow: hidden;">
            <div class="admin-table-scroll" style="overflow-x: auto;">
                <table class="data-table">
                    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Daily</th><th>Remaining</th><th>Status</th></tr></thead>
                    <tbody id="menuRows"><tr><td colspan="7" style="text-align:center; padding:20px;">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==========================================
         TAB 3: STAFF ACCOUNTS
         ========================================== -->
    <div id="tab-staff" class="admin-tab-content">
        <div class="admin-page-header" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div><h2 style="font-size: 22px; font-weight: 900;">Staff Accounts</h2><p>Manage staff login accounts</p></div>
        </div>
        <div class="admin-card admin-table-card" style="background: var(--card); border-radius: 14px; border: 1px solid var(--border); overflow: hidden;">
            <div class="admin-table-scroll" style="overflow-x: auto;">
                <table class="data-table">
                    <thead><tr><th>Employee ID</th><th>Full Name</th><th>Username</th><th>Role</th><th>Last Login</th><th>Status</th></tr></thead>
                    <tbody id="staffRows"><tr><td colspan="6" style="text-align:center; padding:20px;">Loading staff...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==========================================
         TAB 4: SETTINGS (THEME)
         ========================================== -->
    <div id="tab-settings" class="admin-tab-content">
        <div class="admin-page-header" style="margin-bottom: 20px;">
            <div><h2 style="font-size: 22px; font-weight: 900;">Settings</h2><p>Customize site appearance and theme</p></div>
        </div>
        <div class="admin-card theme-editor" style="padding: 20px; background: var(--card); border-radius: 14px; border: 1px solid var(--border);">
            <h3>Preset Themes</h3>
            <div class="preset-themes" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
                <button class="preset-btn" style="background:linear-gradient(135deg,#ff7849,#e63a1a); padding:10px 20px; border-radius:8px; color:#fff; border:none; cursor:pointer;" onclick="applyPreset('#e63a1a','#c22f13','#ffebe3','#ff7849')">Orange Red</button>
                <button class="preset-btn" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8); padding:10px 20px; border-radius:8px; color:#fff; border:none; cursor:pointer;" onclick="applyPreset('#3b82f6','#1d4ed8','#dbeafe','#60a5fa')">Ocean Blue</button>
                <button class="preset-btn" style="background:linear-gradient(135deg,#10b981,#047857); padding:10px 20px; border-radius:8px; color:#fff; border:none; cursor:pointer;" onclick="applyPreset('#10b981','#047857','#d1fae5','#34d399')">Emerald</button>
            </div>
            <div class="theme-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="color-field"><label>Primary Color</label><input type="color" id="colorPrimary"><input type="text" id="hexPrimary"></div>
                <div class="color-field"><label>Primary Dark</label><input type="color" id="colorPrimaryDark"><input type="text" id="hexPrimaryDark"></div>
            </div>
            <div class="theme-editor-actions" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="saveCustomTheme()">Save Settings</button>
                <button class="btn btn-outline" onclick="resetTheme()">Reset to Default</button>
            </div>
        </div>
    </div>
</main>
<div class="toast" id="toast"></div>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
let profitChartInstance = null;

// ==========================================
// SIDEBAR & TABS LOGIC
// ==========================================
const adminBurger = document.getElementById('adminBurger');
const adminSidebar = document.getElementById('adminSidebar');
const adminBackdrop = document.getElementById('adminBackdrop');
const adminSidebarClose = document.getElementById('adminSidebarClose');

if (adminBurger) {
    adminBurger.addEventListener('click', () => { adminSidebar.classList.toggle('open'); adminBackdrop.classList.toggle('show'); });
    adminBackdrop.addEventListener('click', () => { adminSidebar.classList.remove('open'); adminBackdrop.classList.remove('show'); });
    adminSidebarClose.addEventListener('click', () => { adminSidebar.classList.remove('open'); adminBackdrop.classList.remove('show'); });
}

document.querySelectorAll('.admin-nav-item[data-tab]').forEach(btn => {
    btn.onclick = () => {
        document.querySelectorAll('.admin-nav-item').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const tab = btn.dataset.tab;
        document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');

        if (tab === 'orders') { loadOrders(); loadProfitData(); }
        if (tab === 'menu') loadMenu();
        if (tab === 'staff') loadStaff();
        if (tab === 'settings') loadThemeIntoEditor();

        if (window.innerWidth < 900) { adminSidebar.classList.remove('open'); adminBackdrop.classList.remove('show'); }
    };
});

// ==========================================
// LOAD ORDERS
// ==========================================
async function loadOrders() {
    const status = document.getElementById('orderFilterStatus').value;
    const pay = document.getElementById('orderFilterPayment').value;
    let url = '/api/orders?';
    if (status) url += 'status=' + encodeURIComponent(status) + '&';
    if (pay) url += 'payment=' + encodeURIComponent(pay);

    const res = await fetch(url);
    const orders = await res.json();
    const rows = document.getElementById('ordersRows');

    if (orders.length === 0) { 
        rows.innerHTML = '<tr><td colspan="9" style="text-align:center; color:var(--muted); font-style:italic;">No orders found.</td></tr>'; 
        return; 
    }

    const colorMap = { PENDING: '#f59e0b', PREPARING: '#3b82f6', READY: '#10b981', DELIVERING: '#8b5cf6', DELIVERED: '#06b6d4', CHARGED: '#6b7280', CANCELLED: '#ef4444', VOIDED: '#000000' };

    rows.innerHTML = orders.map(o => {
        const items = o.items.map(i => `${i.item_name} x${i.qty}`).join(', ');
        let payBadge = '—';
        if (o.payment_method === 'CASH') payBadge = '<span class="status-badge" style="background:#10b981;">CASH</span>';
        if (o.payment_method === 'CHARGE_TO_ROOM') payBadge = '<span class="status-badge" style="background:#3b82f6;">ROOM</span>';
        let statusLabel = o.status === 'CHARGED' ? 'COMPLETED' : o.status;

        let actionHtml = '—';
        if (o.status === 'CHARGED' || o.status === 'DELIVERED') {
            actionHtml = `<button class="btn-void" onclick="voidOrder(${o.id})">Void</button>`;
        }

        return `
            <tr>
                <td><strong>${o.order_code}</strong></td>
                <td>${o.room_number}</td>
                <td>${o.guest_name || '—'}</td>
                <td><small>${items}</small></td>
                <td>PHP ${parseFloat(o.total_amount).toFixed(2)}</td>
                <td>${payBadge}</td>
                <td><span class="status-badge" style="background:${colorMap[o.status] || '#999'};">${statusLabel}</span></td>
                <td><small>${new Date(o.created_at).toLocaleString()}</small></td>
                <td>${actionHtml}</td>
            </tr>`;
    }).join('');
}

async function voidOrder(id) {
    const reason = prompt("Please enter the reason for voiding this completed order:");
    if (!reason || reason.trim() === '') return;

    try {
        const res = await fetch(`/api/orders/${id}/void`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify({ reason: reason })
        });
        const data = await res.json();
        if (data.success) { showToast('Order voided successfully.', 'success'); loadOrders(); loadProfitData(); } 
        else { showToast(data.message, 'error'); }
    } catch (e) { showToast('Error processing void', 'error'); }
}

// ==========================================
// LOAD PROFIT DATA
// ==========================================
async function loadProfitData() {
    const filter = document.getElementById('profitFilter').value;
    try {
        const res = await fetch('/api/revenue-stats?filter=' + filter);
        const data = await res.json();

        document.getElementById('statTotal').textContent = 'PHP ' + parseFloat(data.totals.grand).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('statCash').textContent = 'PHP ' + parseFloat(data.totals.cash).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('statCharge').textContent = 'PHP ' + parseFloat(data.totals.charge).toLocaleString('en-US', {minimumFractionDigits: 2});

        const labels = data.chart.map(item => item.date);
        const cashData = data.chart.map(item => item.cash);
        const chargeData = data.chart.map(item => item.charge);
        const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#e63a1a';
        const textColor = getComputedStyle(document.body).color;

        if (profitChartInstance) profitChartInstance.destroy();

        const ctx = document.getElementById('profitChart').getContext('2d');
        profitChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels.length > 0 ? labels : ['No Data'],
                datasets: [
                    { label: 'Cash on Hand', data: cashData.length > 0 ? cashData : [0], backgroundColor: '#10b981', borderRadius: 6, barPercentage: 0.6 },
                    { label: 'Charge to Room', data: chargeData.length > 0 ? chargeData : [0], backgroundColor: primaryColor, borderRadius: 6, barPercentage: 0.6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeOutQuart' },
                plugins: { legend: { position: 'top', labels: { font: { weight: 'bold' }, color: textColor } } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(value) { return 'PHP ' + value.toLocaleString(); }, color: textColor } },
                    x: { ticks: { color: textColor } }
                }
            }
        });
    } catch (e) { console.error('Failed to load profit data:', e); }
}

// ==========================================
// LOAD MENU (Para sa Menu Items Tab)
// ==========================================
async function loadMenu() {
    const res = await fetch('/api/menu');
    const items = await res.json();
    const rows = document.getElementById('menuRows');
    if (items.length === 0) { rows.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">No items yet.</td></tr>'; return; }
    
    rows.innerHTML = items.map(i => `
        <tr>
            <td>${i.image_path ? `<img src="/${i.image_path}" style="width:40px; height:40px; object-fit:cover; border-radius:6px;">` : `<div style="width:40px; height:40px; background:var(--bg-alt); border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:9px;">No Img</div>`}</td>
            <td><strong>${i.name}</strong></td>
            <td>${i.category}</td>
            <td>PHP ${parseFloat(i.price).toFixed(2)}</td>
            <td>${i.daily_stock}</td>
            <td>${i.remaining_stock}</td>
            <td><span class="status-badge" style="background:${i.is_available == 1 && i.remaining_stock > 0 ? '#10b981' : '#ef4444'};">${i.is_available == 1 && i.remaining_stock > 0 ? 'Available' : 'Unavailable'}</span></td>
        </tr>`).join('');
}

async function resetAllStock() {
    if (!confirm('Reset all remaining stock?')) return;
    await fetch('/api/menu/reset-stock', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } });
    loadMenu();
}

// ==========================================
// LOAD STAFF
// ==========================================
async function loadStaff() {
    const res = await fetch('/api/staff');
    const staff = await res.json();
    const rows = document.getElementById('staffRows');
    if (staff.length === 0) { rows.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px;">No staff accounts yet.</td></tr>'; return; }
    
    const roleColor = { ADMIN: '#8b5cf6', FNB_MANAGER: '#f59e0b', FRONT_DESK: '#3b82f6', COFFEE_MANAGER: '#92400e', KITCHEN: '#0ea5e9', WAITER: '#10b981' };
    
    rows.innerHTML = staff.map(s => `
        <tr>
            <td><strong>${s.employee_id}</strong></td>
            <td>${s.full_name}</td>
            <td>${s.username}</td>
            <td><span class="status-badge" style="background:${roleColor[s.role] || '#999'};">${s.role}</span></td>
            <td><small>${s.last_login ? new Date(s.last_login).toLocaleString() : 'Never'}</small></td>
            <td>${s.is_active == 1 ? '<span class="status-badge" style="background:#10b981;">Active</span>' : '<span class="status-badge" style="background:#ef4444;">Inactive</span>'}</td>
        </tr>`).join('');
}

// ==========================================
// THEME SETTINGS
// ==========================================
function applyPreset(primary, dark, light, accent) {
    const theme = { primary, primaryDark: dark, primaryLight: light, accent };
    if (typeof saveTheme === 'function') saveTheme(theme);
    if (typeof applyTheme === 'function') applyTheme(theme);
    showToast('Preset applied!', 'success');
}
function saveCustomTheme() {
    const theme = { primary: document.getElementById('hexPrimary').value, primaryDark: document.getElementById('hexPrimaryDark').value };
    if (typeof saveTheme === 'function') saveTheme(theme);
    if (typeof applyTheme === 'function') applyTheme(theme);
    showToast('Settings saved!', 'success');
}
function resetTheme() {
    const def = { primary:'#e63a1a', primaryDark:'#c22f13', primaryLight:'#ffebe3', accent:'#ff7849' };
    if (typeof saveTheme === 'function') saveTheme(def);
    if (typeof applyTheme === 'function') applyTheme(def);
    showToast('Reset to default', 'success');
}
function loadThemeIntoEditor() {
    if (typeof getTheme !== 'function') return;
    const theme = getTheme();
    document.getElementById('colorPrimary').value = theme.primary;
    document.getElementById('hexPrimary').value = theme.primary;
    document.getElementById('colorPrimaryDark').value = theme.primaryDark;
    document.getElementById('hexPrimaryDark').value = theme.primaryDark;
}

// ==========================================
// INITIAL LOAD & LIVE POLLING
// ==========================================
function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg; t.className = 'toast show ' + type;
    clearTimeout(t._timer); t._timer = setTimeout(() => t.classList.remove('show'), 2800);
}

document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
    loadProfitData();
    
    setInterval(() => {
        const activeTab = document.querySelector('.admin-tab-content.active');
        if (activeTab && activeTab.id === 'tab-orders') {
            loadOrders();
            loadProfitData();
        }
    }, 10000);
});
</script>
@endsection