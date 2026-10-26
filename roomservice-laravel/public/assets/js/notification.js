(function() {
    'use strict';

    const NOTIF_POLL = 3000;
    const CURRENT_ROOM_KEY = 'rs_current_room';
    let notifEl = null;
    let notifOrders = [];
    let lastShownIds = [];
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function getActiveOrderIds() {
        try {
            const room = localStorage.getItem(CURRENT_ROOM_KEY);
            if (!room) return [];
            const list = JSON.parse(localStorage.getItem('rs_active_orders_' + room)) || [];
            return list.map(o => o.id);
        } catch (e) { return []; }
    }

    function getCurrentRoom() {
        return localStorage.getItem(CURRENT_ROOM_KEY) || null;
    }

    function removeNotifEl() {
        if (notifEl && notifEl.parentNode) {
            notifEl.classList.remove('show');
            setTimeout(() => {
                if (notifEl && notifEl.parentNode) {
                    notifEl.parentNode.removeChild(notifEl);
                    notifEl = null;
                }
            }, 400);
        }
    }

    function ensureNotifIsland() {
        if (notifEl && document.body.contains(notifEl)) return notifEl;

        notifEl = document.createElement('div');
        notifEl.id = 'dynNotifIsland';
        notifEl.className = 'dyn-notif-island';
        
        notifEl.style.position = 'fixed';
        notifEl.style.top = '-120px';
        notifEl.style.left = '50%';
        notifEl.style.transform = 'translateX(-50%)';
        notifEl.style.zIndex = '1600';
        notifEl.style.width = 'min(420px, calc(100vw - 32px))';
        notifEl.style.transition = 'top 0.5s cubic-bezier(.2, .9, .3, 1.4)';

        notifEl.innerHTML = `
            <div class="dyn-notif-inner" style="display:flex;align-items:center;gap:10px;background:linear-gradient(135deg,#10b981,#047857);color:#fff;padding:12px 14px;border-radius:18px;box-shadow:0 20px 50px rgba(16,185,129,0.4);cursor:pointer;position:relative;">
                <div class="dyn-notif-icon" style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:900;flex-shrink:0;">!</div>
                <div class="dyn-notif-content" style="flex:1;min-width:0;">
                    <div class="dyn-notif-title" style="font-size:12.5px;font-weight:900;margin-bottom:2px;">Waiter Arrived</div>
                    <div class="dyn-notif-sub" id="dynNotifSub" style="font-size:11px;color:rgba(255,255,255,0.9);">Room —</div>
                </div>
                <button class="dyn-notif-close" id="dynNotifClose" style="width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">×</button>
            </div>
        `;

        document.body.appendChild(notifEl);

        const closeBtn = notifEl.querySelector('#dynNotifClose');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => { e.stopPropagation(); dismissNotif(); });
        }

        notifEl.addEventListener('click', () => {
            if (notifOrders.length > 0) {
                window.location.href = '/track?order_id=' + notifOrders[0].id + '&room=' + getCurrentRoom();
            }
        });

        return notifEl;
    }

    function dismissNotif() {
        if (!notifEl) return;
        notifOrders.forEach(o => {
            fetch('/api/arrival/seen', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ order_id: o.id })
            }).catch(() => {});
        });
        notifOrders = [];
        lastShownIds = [];
        removeNotifEl();
    }

    async function refreshNotifIsland() {
        try {
            const currentRoom = getCurrentRoom();
            const ids = getActiveOrderIds();

            if (!currentRoom || ids.length === 0) {
                if (notifEl) removeNotifEl();
                return;
            }

            const res = await fetch('/api/arrival/check?ids=' + ids.join(','));
            let notifs = await res.json();

            if (!Array.isArray(notifs) || notifs.length === 0) {
                if (notifEl) notifEl.classList.remove('show');
                return;
            }

            notifs = notifs.filter(n => String(n.room_number) === String(currentRoom));

            if (notifs.length === 0) {
                if (notifEl) notifEl.classList.remove('show');
                return;
            }

            notifOrders = notifs;
            ensureNotifIsland();

            const subEl = notifEl.querySelector('#dynNotifSub');
            if (subEl) {
                subEl.textContent = 'Room ' + notifs[0].room_number;
            }

            setTimeout(() => { if (notifEl) notifEl.classList.add('show'); }, 50);

            const currentIds = notifs.map(n => n.id).join(',');
            const prevIds = lastShownIds.join(',');
            if (currentIds !== prevIds) {
                playPing();
                lastShownIds = notifs.map(n => n.id);
            }

        } catch (e) { /* silent */ }
    }

    let lastPingTime = 0;
    function playPing() {
        const now = Date.now();
        if (now - lastPingTime < 5000) return;
        lastPingTime = now;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = 880; osc.type = 'sine';
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.4);
        } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else { init(); }

    function init() {
        try {
            refreshNotifIsland();
            setInterval(refreshNotifIsland, NOTIF_POLL);
        } catch (e) { console.error('Notif island init failed:', e); }
    }
})();