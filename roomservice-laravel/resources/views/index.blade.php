@extends('layouts.app')

@section('title', 'Room Service — BLULANE')
@section('body-class', '')

@section('content')
<header class="site-header">
    <div class="site-header-inner">
        <a href="/" class="site-logo">
            <img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE">
        </a>
        <nav class="site-nav">
            <a href="/" class="active">Order Food</a>
            <a href="/track" id="navMyOrders">My Orders</a>
        </nav>
        <button class="theme-toggle" id="themeToggle" title="Toggle theme">☾</button>
    </div>
</header>

<section class="hero-section">
    <div class="hero-video-container">
        <video autoplay muted loop playsinline webkit-playsinline preload="auto" disablepictureinpicture class="hero-video" id="bannerVideo" poster="{{ asset('assets/uploads/image-logo/BLULANE.png') }}">
            <source src="{{ asset('assets/uploads/image-logo/SWISS_VID.mp4') }}" type="video/mp4">
        </video>
        <div class="hero-video-overlay"></div>
        <div class="hero-content">
            <span class="hero-tag">In-Room Dining</span>
            <h1 class="hero-title">Freshly Prepared Meals</h1>
            <p class="hero-subtitle">Delivered directly to your room · Available 24/7</p>
        </div>
    </div>
</section>

<section class="coffee-club-section">
    <div class="coffee-club-container">
        <div class="coffee-club-image">
            <img src="{{ asset('assets/uploads/image-logo/coffeeclub.webp') }}" alt="Coffee Club">
        </div>
        <div class="coffee-club-content">
            <span class="coffee-club-tag">Featured Area</span>
            <h2>Coffee Club</h2>
            <p class="coffee-club-desc">
                Visit our in-house Coffee Club for a cozy ambiance and freshly brewed coffee.
                Order from your room and enjoy the perfect blend at any time of the day.
            </p>
            <div class="coffee-club-info">
                <div class="info-item"><span class="info-label">Location</span><span class="info-value">Ground Floor Lobby</span></div>
                <div class="info-item"><span class="info-label">Open Hours</span><span class="info-value">Open 24/7</span></div>
            </div>
        </div>
    </div>
</section>

<div class="main-layout">
    <aside class="category-nav">
        <nav class="category-list" id="categoryNav">
            <button class="active" data-cat="Main Course">Main Dish</button>
            <button data-cat="Appetizer">Appetizers</button>
            <button data-cat="Dessert">Desserts</button>
            <button data-cat="Beverage">Coffee &amp; Drinks</button>
        </nav>
    </aside>

    <main class="menu-content">
        <div class="menu-header">
            <h2 id="menuTitle">Main Dish</h2>
            <span class="menu-count" id="menuCount">0 items</span>
        </div>
        <div class="menu-grid" id="menuGrid"></div>
    </main>

    <aside class="cart-panel" id="cartPanel">
        <div class="cart-panel-header" id="cartPanelHeader">
            <h3>My Order</h3>
            <span class="cart-count" id="cartCount">0</span>
        </div>
        <div class="cart-panel-body">
            <div class="form-row" style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>Floor *</label>
                    <select id="floorSelect" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--card-alt); color: var(--text); outline: none; font-family: inherit; font-size: 13px;">
                        <option value="">Select Floor</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Room Number *</label>
                    <select id="roomNumber" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--card-alt); color: var(--text); outline: none; font-family: inherit; font-size: 13px;">
                        <option value="">Select Room</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Guest Name</label><input type="text" id="guestName" placeholder="Optional"></div>
            
            <div class="cart-items" id="cartItems"><div class="cart-empty">No items yet</div></div>

            <div class="form-group"><label>Notes</label><input type="text" id="notes" placeholder="e.g. less spicy"></div>

            <div class="cart-summary">
                <div class="row"><span>Subtotal</span><span id="subtotal">PHP 0.00</span></div>
                <div class="row"><span>Delivery</span><span>Free</span></div>
                <div class="row total"><span>Total</span><span id="grandTotal">PHP 0.00</span></div>
            </div>
        </div>
        <div class="cart-panel-footer"><button class="checkout-btn" id="checkoutBtn" disabled>Check Out</button></div>
    </aside>
</div>

<div class="modal-overlay" id="waitModal">
    <div class="modal" style="background:var(--card);padding:30px;border-radius:20px;text-align:center; min-width: 260px;">
        <div class="spinner" style="width: 40px;height: 40px;border: 4px solid var(--border);border-top-color: var(--primary);border-radius: 50%;animation: spin 1s linear infinite;margin: 0 auto 16px;"></div>
        <style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
        <h2 style="color:var(--text-strong); font-size:18px;">Placing Order...</h2>
    </div>
</div>
<div class="toast" id="toast"></div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/island.js') }}"></script>
<script src="{{ asset('assets/js/notification.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>
<script>
// FLOOR AND ROOM DROPDOWN LOGIC
(function() {
    const floorSelect = document.getElementById('floorSelect');
    const roomSelect = document.getElementById('roomNumber');
    
    for (let f = 5; f <= 16; f++) {
        const opt = document.createElement('option');
        opt.value = f;
        opt.textContent = 'Floor ' + f;
        floorSelect.appendChild(opt);
    }

    function updateRooms(floor) {
        roomSelect.innerHTML = '<option value="">Select Room</option>';
        if (!floor) return;
        for (let r = 1; r <= 13; r++) {
            const roomNum = floor + String(r).padStart(2, '0');
            const opt = document.createElement('option');
            opt.value = roomNum;
            opt.textContent = 'Room ' + roomNum;
            roomSelect.appendChild(opt);
        }
    }

    floorSelect.addEventListener('change', (e) => {
        updateRooms(e.target.value);
        roomSelect.value = ''; 
    });

    function autoSelectRoom(roomFromUrl) {
        if (roomFromUrl && /^\d{3,4}$/.test(roomFromUrl)) {
            let floorStr = roomFromUrl.substring(0, roomFromUrl.length - 2);
            floorSelect.value = floorStr;
            updateRooms(floorStr);
            roomSelect.value = roomFromUrl;
            floorSelect.disabled = true;
            roomSelect.disabled = true;
            floorSelect.style.opacity = '0.7';
            roomSelect.style.opacity = '0.7';
            localStorage.setItem('rs_current_room', roomFromUrl);
            return true;
        }
        return false;
    }

    const params = new URLSearchParams(window.location.search);
    const roomFromQr = params.get('room');
    
    if (roomFromQr) autoSelectRoom(roomFromQr);
    else {
        const savedRoom = localStorage.getItem('rs_current_room');
        if (savedRoom) autoSelectRoom(savedRoom);
    }
})();

(function() {
    const room = localStorage.getItem('rs_current_room');
    if (room) document.getElementById('navMyOrders').href = '/track?room=' + room;
})();

(function() {
    const video = document.getElementById('bannerVideo');
    if (!video) return;
    function tryPlay() {
        const p = video.play();
        if (p && typeof p.catch === 'function') p.catch(() => setTimeout(tryPlay, 1000));
    }
    video.addEventListener('loadeddata', tryPlay);
    video.addEventListener('canplay', tryPlay);
    video.addEventListener('ended', () => { video.currentTime = 0; tryPlay(); });
    tryPlay();
})();
</script>
@endsection