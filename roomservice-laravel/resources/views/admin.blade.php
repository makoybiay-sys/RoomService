@extends('layouts.app')

@section('title', 'Admin Panel — BLULANE')
@section('body-class', 'admin-body')

@section('content')
<header class="admin-topbar">
    <div class="admin-topbar-inner">
        <button class="admin-burger" id="adminBurger"><span></span><span></span><span></span></button>
        <a href="/" class="admin-topbar-logo"><img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE"></a>
        <div class="admin-topbar-title">
            <h1>Admin Panel</h1>
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

<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-header"><h2>Menu</h2><button class="admin-sidebar-close" id="adminSidebarClose">×</button></div>
    <nav class="admin-sidebar-nav">
        <button class="admin-nav-item active" data-tab="menu"><span class="nav-label">Menu Items</span><span class="nav-arrow">›</span></button>
        <button class="admin-nav-item" data-tab="orders"><span class="nav-label">All Orders</span><span class="nav-arrow">›</span></button>
        <button class="admin-nav-item" data-tab="rooms"><span class="nav-label">Rooms by Floor</span><span class="nav-arrow">›</span></button>
        <button class="admin-nav-item" data-tab="staff"><span class="nav-label">Staff Accounts</span><span class="nav-arrow">›</span></button>
        <button class="admin-nav-item" data-tab="theme"><span class="nav-label">Theme Editor</span><span class="nav-arrow">›</span></button>
        <div class="admin-sidebar-divider"></div>
        <a href="/kitchen" class="admin-nav-item admin-nav-link"><span class="nav-label">Kitchen</span><span class="nav-arrow">›</span></a>
        <a href="/waiter" class="admin-nav-item admin-nav-link"><span class="nav-label">Waiter</span><span class="nav-arrow">›</span></a>
        <a href="/" class="admin-nav-item admin-nav-link"><span class="nav-label">Guest View</span><span class="nav-arrow">›</span></a>
    </nav>
</aside>
<div class="admin-backdrop" id="adminBackdrop"></div>

<main class="admin-main">
    <!-- MENU TAB -->
    <div id="tab-menu" class="admin-tab-content active">
        <div class="admin-page-header">
            <div><h2>Menu Items</h2><p>Manage food items, prices, and stock</p></div>
            <div class="admin-page-actions">
                <button class="btn btn-outline" onclick="resetAllStock()">Reset Stock</button>
                <button class="btn btn-primary" onclick="openCreateModal()">Add New Item</button>
            </div>
        </div>
        <div class="admin-card admin-table-card">
            <div class="admin-table-scroll">
                <table class="data-table">
                    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Daily</th><th>Remaining</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="menuRows"><tr><td colspan="8" class="empty-state">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ORDERS TAB -->
    <div id="tab-orders" class="admin-tab-content">
        <div class="admin-page-header"><div><h2>All Orders</h2><p>View and filter all orders</p></div></div>
        <div class="admin-card admin-filter-card">
            <div class="admin-filter-row">
                <div class="admin-filter-group"><label>Status</label><select id="orderFilterStatus" onchange="loadOrders()"><option value="">All Statuses</option><option>PENDING</option><option>PREPARING</option><option>READY</option><option>DELIVERING</option><option>DELIVERED</option><option value="CHARGED">COMPLETED</option><option>CANCELLED</option><option>VOIDED</option></select></div>
            </div>
        </div>
        <div class="admin-card admin-table-card">
            <div class="admin-table-scroll">
                <table class="data-table">
                    <thead><tr><th>Order</th><th>Room</th><th>Guest</th><th>Items</th><th>Waiter</th><th>Total</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody id="ordersRows"><tr><td colspan="8" class="empty-state">Loading orders...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- STAFF TAB -->
    <div id="tab-staff" class="admin-tab-content">
        <div class="admin-page-header"><div><h2>Staff Accounts</h2><p>Manage staff login accounts</p></div><div class="admin-page-actions"><button class="btn btn-primary" onclick="openStaffModal()">Add Staff</button></div></div>
        <div class="admin-card admin-table-card">
            <div class="admin-table-scroll">
                <table class="data-table">
                    <thead><tr><th>Employee ID</th><th>Full Name</th><th>Username</th><th>Role</th><th>Actions</th></tr></thead>
                    <tbody id="staffRows"><tr><td colspan="5" class="empty-state">Loading staff...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- THEME TAB -->
    <div id="tab-theme" class="admin-tab-content">
        <div class="admin-page-header"><div><h2>Theme Editor</h2><p>Customize colors for the entire site</p></div></div>
        <div class="admin-card theme-editor">
            <h3>Preset Themes</h3>
            <div class="preset-themes">
                <button class="preset-btn" style="background:linear-gradient(135deg,#ff7849,#e63a1a);" onclick="applyPreset('#e63a1a','#c22f13','#ffebe3','#ff7849')">Orange Red</button>
                <button class="preset-btn" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);" onclick="applyPreset('#3b82f6','#1d4ed8','#dbeafe','#60a5fa')">Ocean Blue</button>
                <button class="preset-btn" style="background:linear-gradient(135deg,#10b981,#047857);" onclick="applyPreset('#10b981','#047857','#d1fae5','#34d399')">Emerald</button>
            </div>
            <h3>Custom Colors</h3>
            <div class="theme-grid">
                <div class="color-field"><label>Primary Color</label><input type="color" id="colorPrimary"><input type="text" id="hexPrimary"></div>
            </div>
            <div class="theme-editor-actions"><button class="btn btn-primary" onclick="saveCustomTheme()">Save Theme</button></div>
        </div>
    </div>
</main>

<!-- MODAL: MENU ITEM -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal modal-form-animated">
        <button class="modal-close-btn" onclick="closeModal()">×</button>
        <h2 id="modalTitle">Add New Item</h2>
        <form id="menuForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">
            <div class="form-group"><label>Item Name *</label><input type="text" name="name" id="fName" required></div>
            <div class="form-group"><label>Description</label><input type="text" name="description" id="fDescription"></div>
            <div class="form-row">
                <div class="form-group"><label>Category *</label><select name="category" id="fCategory" required><option>Main Course</option><option>Appetizer</option><option>Dessert</option><option>Beverage</option></select></div>
                <div class="form-group"><label>Price (PHP) *</label><input type="number" name="price" id="fPrice" step="0.01" required></div>
            </div>
            <div class="form-group"><label>Daily Stock *</label><input type="number" name="daily_stock" id="fStock" required></div>
            <div class="form-group"><label>Food Image</label><input type="file" name="image" id="fImage" accept="image/*" onchange="previewImage(event)"><img id="imagePreview" class="image-preview" alt="Preview"></div>
            <div class="modal-actions"><button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-primary">Save Item</button></div>
        </form>
    </div>
</div>

<!-- MODAL: STAFF -->
<div class="modal-overlay" id="staffModalOverlay">
    <div class="modal modal-form-animated">
        <button class="modal-close-btn" onclick="closeStaffModal()">×</button>
        <h2 id="staffModalTitle">Add Staff Account</h2>
        <form id="staffForm">
            <input type="hidden" name="action" id="staffAction" value="create">
            <input type="hidden" name="id" id="staffId" value="">
            <div class="form-group"><label>Employee ID *</label><input type="text" name="employee_id" id="sEmployeeId" required></div>
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" id="sFullName" required></div>
            <div class="form-row">
                <div class="form-group"><label>Username *</label><input type="text" name="username" id="sUsername" required></div>
                <div class="form-group"><label>Role *</label><select name="role" id="sRole" required><option value="KITCHEN">Kitchen</option><option value="WAITER">Waiter</option><option value="FRONT_DESK">Front Desk</option><option value="COFFEE_MANAGER">Coffee Club Manager</option><option value="FNB_MANAGER">F&B Manager</option><option value="ADMIN">Admin</option></select></div>
            </div>
            <div class="form-group"><label>Password <span id="pwdHint">*</span></label><input type="text" name="password" id="sPassword"></div>
            <div class="modal-actions"><button type="button" class="btn btn-outline" onclick="closeStaffModal()">Cancel</button><button type="submit" class="btn btn-primary">Save Staff</button></div>
        </form>
    </div>
</div>
<div class="toast" id="toast"></div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/admin.js') }}"></script>
@endsection