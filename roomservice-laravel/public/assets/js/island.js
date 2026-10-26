const CURRENT_ROOM_KEY = 'rs_current_room';
const POLL_MS = 5000;
const TIMER_MS = 1000;

const STATUS_META = {
    PENDING:    { label: 'Waiting',    color: '#f59e0b', done: false },
    PREPARING:  { label: 'Preparing',  color: '#3b82f6', done: false },
    READY:      { label: 'Ready',      color: '#10b981', done: false },
    DELIVERING: { label: 'Delivering', color: '#8b5cf6', done: false },
    DELIVERED:  { label: 'Delivered',  color: '#06b6d4', done: false },
    CHARGED:    { label: 'Completed',  color: '#10b981', done: true  },
    CANCELLED:  { label: 'Cancelled',  color: '#ef4444', done: true  },
    VOIDED:     { label: 'Voided',     color: '#ef4444', done: true  }
};

function getCurrentRoom() { return localStorage.getItem(CURRENT_ROOM_KEY) || null; }
function getStorageKey() { 
    const room = getCurrentRoom();
    return room ? 'rs_active_orders_' + room : null;
}

function getActiveOrders() {
    const key = getStorageKey();
    if (!key) return [];
    try { return JSON.parse(localStorage.getItem(key)) || []; } catch { return []; }
}

function saveActiveOrders(arr) { 
    const key = getStorageKey();
    if (key) localStorage.setItem(key, JSON.stringify(arr)); 
}

function addActiveOrder(id, room, etaMinutes) {
    if (room) localStorage.setItem(CURRENT_ROOM_KEY, room);
    const list = getActiveOrders();
    if (!list.find(o => o.id === id)) {
        const eta = etaMinutes || 15; 
        list.unshift({ id, room, startedAt: Date.now(), etaMins: eta });
        saveActiveOrders(list);
    }
}

function removeActiveOrder(id) { saveActiveOrders(getActiveOrders().filter(o => o.id !== id)); }

function fmtTime(seconds) {
    if (seconds <= 0) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return m + ':' + String(s).padStart(2, '0');
}

let islandEl = null;
let modalEl = null; 
let timerInterval = null;
let currentOrdersData = [];

const islandStyles = `
    .island-wrapper { position: fixed; top: 70px; left: 50%; transform: translateX(-50%); z-index: 99999; display: flex; justify-content: center; width: 100%; pointer-events: none; }
    .island-pill { background: var(--card); border: 2px solid var(--primary); border-radius: 40px; padding: 8px 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); cursor: pointer; pointer-events: auto; animation: slideDown 0.5s cubic-bezier(0.2, 0.9, 0.3, 1.2); user-select: none; }
    .island-pill:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
    .hourglass-icon { width: 24px; height: 24px; border: 2.5px solid var(--primary); border-radius: 50%; border-top-color: transparent; animation: spinHourglass 2s linear infinite; }
    @keyframes spinHourglass { 100% { transform: rotate(360deg); } }
    .island-time { font-size: 16px; font-weight: 900; color: var(--text-strong); font-family: 'Courier New', monospace; }
    .island-text { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .island-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 100000; display: none; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.2s ease; }
    .island-modal-overlay.active { display: flex; opacity: 1; }
    .island-modal-content { background: var(--card); border-radius: 20px; width: 100%; max-width: 400px; max-height: 85vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); position: relative; animation: scaleUp 0.3s cubic-bezier(0.2, 0.9, 0.3, 1.2); }
    @keyframes scaleUp { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
    .island-modal-close { position: absolute; top: 16px; right: 16px; background: var(--bg-alt); border: none; width: 32px; height: 32px; border-radius: 50%; color: var(--text); font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .order-item-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border); }
    .order-item-row:last-child { border-bottom: none; }
    @media (max-width: 600px) { .island-wrapper { top: 60px; } .island-pill { padding: 6px 12px; gap: 8px; } .island-time { font-size: 14px; } .island-text { font-size: 10px; } .hourglass-icon { width: 20px; height: 20px; } .island-modal-content { padding: 18px; max-height: 90vh; } }
`;

function ensureIsland() {
    if (islandEl && document.body.contains(islandEl)) return islandEl;

    if (!document.getElementById('island-styles')) {
        const style = document.createElement('style');
        style.id = 'island-styles';
        style.textContent = islandStyles;
        document.head.appendChild(style);
    }

    islandEl = document.createElement('div');
    islandEl.className = 'island-wrapper';
    islandEl.innerHTML = `
        <div class="island-pill" id="islandPillBtn">
            <div class="hourglass-icon"></div>
            <div>
                <div class="island-text" id="islandText">Preparing</div>
                <div class="island-time" id="islandTime">0:00</div>
            </div>
        </div>
    `;
    document.body.appendChild(islandEl);

    document.getElementById('islandPillBtn').addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation(); openIslandModal();
    });

    if (!modalEl || !document.body.contains(modalEl)) {
        modalEl = document.createElement('div');
        modalEl.className = 'island-modal-overlay';
        modalEl.id = 'islandModalOverlay';
        modalEl.innerHTML = `
            <div class="island-modal-content" onclick="event.stopPropagation()">
                <button class="island-modal-close" onclick="closeIslandModal(event)">×</button>
                <h2 style="font-size: 20px; font-weight: 900; color: var(--text-strong); margin-bottom: 4px;">Live Order Status</h2>
                <p style="font-size: 12px; color: var(--muted); margin-bottom: 20px;">Your order is being tracked</p>
                <div id="islandModalBody"></div>
            </div>
        `;
        modalEl.addEventListener('click', (e) => { if (e.target === modalEl) closeIslandModal(); });
        document.body.appendChild(modalEl);
    }

    startLiveTimer();
    return islandEl;
}

function startLiveTimer() {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(updateTimers, TIMER_MS);
}

function updateTimers() {
    if (!islandEl || !document.body.contains(islandEl)) return;
    let maxEtaSecs = 0;
    currentOrdersData.forEach(o => {
        if (o.estimated_minutes && o.status === 'PREPARING') {
            const prepStartedAt = new Date(o.updated_at).getTime();
            const elapsedSecs = Math.floor((Date.now() - prepStartedAt) / 1000);
            const remaining = Math.max((o.estimated_minutes * 60) - elapsedSecs, 0);
            if (remaining > maxEtaSecs) maxEtaSecs = remaining;
        }
    });

    const timeEl = islandEl.querySelector('#islandTime');
    if (timeEl) timeEl.textContent = fmtTime(maxEtaSecs);
    
    const modalBody = document.getElementById('islandModalBody');
    if (modalBody && modalEl && modalEl.classList.contains('active')) renderModalContent();
}

async function openIslandModal() {
    if (currentOrdersData.length === 0) return;
    renderModalContent();
    if (modalEl) modalEl.classList.add('active');
}

function closeIslandModal(e) {
    if (e) e.stopPropagation();
    if (modalEl) modalEl.classList.remove('active');
}

function renderModalContent() {
    const modalBody = document.getElementById('islandModalBody');
    if (!modalBody) return;
    if (currentOrdersData.length === 0) {
        modalBody.innerHTML = '<p style="text-align:center; color:var(--muted);">Loading...</p>';
        return;
    }

    modalBody.innerHTML = currentOrdersData.map(o => {
        const meta = STATUS_META[o.status] || STATUS_META.PENDING;
        let timerText = '...';
        if (o.estimated_minutes && o.status === 'PREPARING') {
            const prepStartedAt = new Date(o.updated_at).getTime();
            const elapsedSecs = Math.floor((Date.now() - prepStartedAt) / 1000);
            const remaining = Math.max((o.estimated_minutes * 60) - elapsedSecs, 0);
            timerText = fmtTime(remaining);
        }

        const items = o.items.map(i => `<div class="order-item-row"><span style="font-size:13px; font-weight:600; color:var(--text);">${i.item_name} × ${i.qty}</span></div>`).join('');

        return `
            <div style="background:var(--bg-alt); border-radius:12px; padding:16px; margin-bottom:12px; border:1px solid var(--border);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <span style="font-size:14px; font-weight:900; color:var(--text);">Room ${o.room_number}</span>
                    <span style="background:${meta.color}; color:#fff; padding:4px 8px; border-radius:6px; font-size:10px; font-weight:800;">${meta.label}</span>
                </div>
                <div style="font-size:11px; color:var(--muted); margin-bottom:12px; font-family:monospace;">${o.order_code}</div>
                <div style="margin-bottom:12px;">${items}</div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:12px; border-top:1px dashed var(--border);">
                    <span style="font-size:12px; font-weight:700; color:var(--text);">Estimated Time</span>
                    <span style="font-size:14px; font-weight:900; color:var(--primary);">${timerText}</span>
                </div>
            </div>
        `;
    }).join('');
}

async function refreshIsland() {
    const currentRoom = getCurrentRoom();
    let active = getActiveOrders();

    if (!currentRoom || active.length === 0) {
        if (islandEl) { islandEl.remove(); islandEl = null; }
        if (modalEl) { modalEl.remove(); modalEl = null; }
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        return;
    }

    const ids = active.map(o => o.id);
    let orders = [];
    try { 
        const res = await fetch(`/api/orders`); 
        let allOrders = await res.json();
        orders = allOrders.filter(o => ids.includes(String(o.id)) && String(o.room_number) === String(currentRoom));
    } catch (e) { return; }

    const completed = orders.filter(o => STATUS_META[o.status] && STATUS_META[o.status].done);
    if (completed.length > 0) {
        completed.forEach(o => removeActiveOrder(o.id));
        orders = orders.filter(o => !(STATUS_META[o.status] && STATUS_META[o.status].done));
    }

    if (orders.length === 0) {
        if (islandEl) { islandEl.remove(); islandEl = null; }
        if (modalEl) { modalEl.remove(); modalEl = null; }
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        return;
    }

    currentOrdersData = orders;
    ensureIsland();

    const labelEl = islandEl.querySelector('#islandText');
    if (labelEl) {
        const statusOrder = ['PENDING', 'PREPARING', 'READY', 'DELIVERING'];
        let currentStatus = 'PENDING';
        orders.forEach(o => {
            const idx = statusOrder.indexOf(o.status);
            const currentIdx = statusOrder.indexOf(currentStatus);
            if (idx > currentIdx) currentStatus = o.status;
        });
        labelEl.textContent = STATUS_META[currentStatus].label;
    }

    if (!timerInterval) startLiveTimer();
}

document.addEventListener('DOMContentLoaded', () => {
    refreshIsland();
    setInterval(refreshIsland, POLL_MS);
});