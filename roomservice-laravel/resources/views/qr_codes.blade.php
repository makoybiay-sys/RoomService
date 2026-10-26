@extends('layouts.app')

@section('title', 'Room QR Codes — BLULANE')
@section('body-class', 'qr-body')

@section('styles')
<style>
    .qr-body { background: var(--bg); min-height: 100vh; padding-top: 60px; }
    .qr-topbar { position: fixed; top: 0; left: 0; right: 0; height: 60px; background: var(--card); border-bottom: 1px solid var(--border); z-index: 100; display: flex; align-items: center; padding: 0 20px; gap: 16px; }
    .qr-topbar-logo img { width: 44px; height: 44px; object-fit: contain; }
    .qr-topbar-title { flex: 1; min-width: 0; }
    .qr-topbar-title h1 { font-size: 16px; font-weight: 900; color: var(--text-strong); }
    .qr-topbar-title p { font-size: 11px; color: var(--muted); }
    .qr-topbar-actions { display: flex; gap: 8px; align-items: center; }
    .qr-container { max-width: 1400px; margin: 0 auto; padding: 24px; }
    .qr-filter-bar { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; box-shadow: var(--shadow); }
    .qr-filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 200px; flex: 1; }
    .qr-filter-group label { font-size: 10.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .qr-filter-group select { padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; background: var(--bg-alt); color: var(--text); outline: none; font-family: inherit; cursor: pointer; width: 100%; }
    .qr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
    .qr-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px 14px; text-align: center; cursor: pointer; transition: all 0.25s; box-shadow: var(--shadow); }
    .qr-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--primary); }
    .qr-card .qr-code-box { display: flex; justify-content: center; padding: 8px; background: #fff; border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
    .qr-card .qr-code-box img, .qr-card .qr-code-box canvas { display: block; border-radius: 6px; max-width: 100%; height: auto; }
    .qr-card .room-label { font-size: 17px; font-weight: 900; color: var(--text-strong); margin-bottom: 2px; }
    .qr-card .floor-label { font-size: 10.5px; color: var(--muted); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 10px; }
    .qr-card .qr-action { display: block; padding: 8px 12px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 11.5px; font-weight: 800; cursor: pointer; font-family: inherit; letter-spacing: 0.3px; width: 100%; }
    .qr-card .qr-action:hover { background: var(--primary-dark); }
    .qr-detail-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
    .qr-detail-overlay.active { display: flex; }
    .qr-detail-modal { background: var(--card); border: 1px solid var(--border); border-radius: 20px; width: 100%; max-width: 420px; padding: 28px; text-align: center; position: relative; animation: modalSlideIn 0.35s cubic-bezier(.2,.9,.3,1.2); color: var(--text); }
    @keyframes modalSlideIn { from { opacity: 0; transform: scale(0.9) translateY(30px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .qr-detail-close { position: absolute; top: 14px; right: 14px; width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border); background: var(--bg-alt); color: var(--text); font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; padding: 0; }
    .qr-detail-close:hover { background: var(--danger); border-color: var(--danger); color: #fff; transform: rotate(90deg); }
    .qr-detail-modal h2 { font-size: 20px; font-weight: 900; color: var(--text-strong); margin-bottom: 4px; }
    .qr-detail-modal .qr-detail-sub { font-size: 12px; color: var(--muted); margin-bottom: 20px; }
    .qr-detail-code { display: flex; justify-content: center; padding: 16px; background: #fff; border-radius: 12px; margin-bottom: 20px; }
    .qr-detail-link { display: block; padding: 12px 16px; background: var(--bg-alt); border: 1px solid var(--border); border-radius: 10px; font-family: 'Courier New', monospace; font-size: 11px; color: var(--primary); font-weight: 700; word-break: break-all; text-decoration: none; margin-bottom: 12px; transition: background 0.2s; }
    .qr-detail-link:hover { background: var(--primary-light); }
    .qr-detail-actions { display: flex; gap: 10px; }
    .qr-detail-actions button, .qr-detail-actions a { flex: 1; padding: 12px; border-radius: 10px; font-size: 12.5px; font-weight: 800; cursor: pointer; border: none; font-family: inherit; text-decoration: none; text-align: center; transition: background 0.2s; display: flex; align-items: center; justify-content: center; }
    .qr-detail-copy { background: var(--primary); color: #fff; }
    .qr-detail-copy:hover { background: var(--primary-dark); }
    .qr-detail-open { background: var(--bg-alt); color: var(--text); border: 1px solid var(--border); }
    .qr-detail-open:hover { background: var(--card-alt); }
    .qr-empty { text-align: center; padding: 60px 20px; color: var(--muted); font-size: 13px; font-style: italic; }
    
    @media print { .qr-topbar, .qr-filter-bar, .qr-card .qr-action, .qr-detail-overlay { display: none !important; } body { background: #fff; padding: 0; } .qr-container { padding: 0; max-width: 100%; } .qr-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; } .qr-card { box-shadow: none; border: 2px dashed #ccc; page-break-inside: avoid; } }
    @media (max-width: 600px) { .qr-container { padding: 16px 12px; } .qr-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } .qr-card { padding: 14px 10px; } .qr-card .room-label { font-size: 15px; } .qr-filter-group { min-width: 100%; } .qr-topbar { padding: 0 12px; gap: 10px; } .qr-topbar-title h1 { font-size: 14px; } .qr-topbar-title p { display: none; } .qr-topbar-logo img { width: 36px; height: 36px; } }
</style>
@endsection

@section('content')
<header class="qr-topbar">
    <a href="/" class="qr-topbar-logo">
        <img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE">
    </a>
    <div class="qr-topbar-title">
        <h1>Room QR Codes</h1>
        <p>Click a card to view QR code &amp; link</p>
    </div>
    <div class="qr-topbar-actions">
        <button class="theme-toggle" id="themeToggle">☾</button>
        <a href="/" style="padding:8px 14px;font-size:11.5px;background:var(--bg-alt);color:var(--text);border:1px solid var(--border);border-radius:8px;font-weight:700;text-decoration:none;">Back to Site</a>
    </div>
</header>

<div class="qr-container">
    <div class="qr-filter-bar">
        <div class="qr-filter-group">
            <label>Select Floor</label>
            <select id="qrFloorFilter">
                <option value="all">All Floors</option>
                @for ($f = 5; $f <= 16; $f++)
                    <option value="{{ $f }}">Floor {{ $f }}</option>
                @endfor
            </select>
        </div>
        <button onclick="window.print()" style="padding:11px 20px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;font-family:inherit;">Print All</button>
    </div>

    <div class="qr-grid" id="qrGrid"></div>
</div>

<div class="qr-detail-overlay" id="qrDetailModal">
    <div class="qr-detail-modal">
        <button class="qr-detail-close" onclick="closeQrDetail()">×</button>
        <h2 id="qrDetailRoom">Room —</h2>
        <p class="qr-detail-sub">Scan this QR code or use the link</p>
        <div class="qr-detail-code" id="qrDetailCode"></div>
        <a href="#" class="qr-detail-link" id="qrDetailLink" target="_blank">—</a>
        <div class="qr-detail-actions">
            <button class="qr-detail-copy" onclick="copyQrLink()">Copy Link</button>
            <a href="#" class="qr-detail-open" id="qrDetailOpen" target="_blank">Open Link</a>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
/* Gagamitin natin yung IP address ng server (localhost man o 192.168.x.x) */
const SERVER_HOST = window.location.host; // e.g. 127.0.0.1:8000
const BASE_URL = window.location.protocol + '//' + SERVER_HOST + '/';

const START_FLOOR = 5;
const END_FLOOR = 16;
const ROOMS_PER_FLOOR = 13; 

let currentRoom = null;

function getQrSize() {
    return window.innerWidth <= 600 ? 120 : 140;
}

function getAllRooms() {
    const rooms = [];
    for (let f = START_FLOOR; f <= END_FLOOR; f++) {
        for (let r = 1; r <= ROOMS_PER_FLOOR; r++) {
            const roomNum = f + String(r).padStart(2, '0');
            rooms.push({ floor: f, room: roomNum, label: 'Room ' + roomNum });
        }
    }
    return rooms;
}

function renderQrGrid(floorFilter = 'all') {
    const grid = document.getElementById('qrGrid');
    grid.innerHTML = '';

    let rooms = getAllRooms();
    if (floorFilter !== 'all') {
        rooms = rooms.filter(r => r.floor == floorFilter);
    }

    if (rooms.length === 0) {
        grid.innerHTML = '<div class="qr-empty">No rooms found.</div>';
        return;
    }

    const qrSize = getQrSize();

    rooms.forEach(r => {
        const scanUrl = BASE_URL + '?room=' + r.room;

        const card = document.createElement('div');
        card.className = 'qr-card';
        card.innerHTML = `
            <div class="qr-code-box" id="qr-${r.room}"></div>
            <div class="room-label">Room ${r.room}</div>
            <div class="floor-label">Floor ${r.floor}</div>
            <button class="qr-action">View QR</button>
        `;

        new QRCode(card.querySelector('#qr-' + r.room), {
            text: scanUrl, width: qrSize, height: qrSize,
            colorDark: '#1f2937', colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });

        card.querySelector('.qr-action').onclick = (e) => { e.stopPropagation(); openQrDetail(r); };
        card.onclick = () => openQrDetail(r);

        grid.appendChild(card);
    });
}

function openQrDetail(room) {
    currentRoom = room;
    const scanUrl = BASE_URL + '?room=' + room.room;
    const qrSize = getQrSize() + 40;

    document.getElementById('qrDetailRoom').textContent = 'Room ' + room.room;
    document.getElementById('qrDetailLink').textContent = scanUrl;
    document.getElementById('qrDetailLink').href = scanUrl;
    document.getElementById('qrDetailOpen').href = scanUrl;

    const codeBox = document.getElementById('qrDetailCode');
    codeBox.innerHTML = '';
    new QRCode(codeBox, {
        text: scanUrl, width: qrSize, height: qrSize,
        colorDark: '#1f2937', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });

    document.getElementById('qrDetailModal').classList.add('active');
}

function closeQrDetail() {
    document.getElementById('qrDetailModal').classList.remove('active');
    currentRoom = null;
}

function copyQrLink() {
    const link = document.getElementById('qrDetailLink').textContent;
    navigator.clipboard.writeText(link).then(() => {
        showToast('Link copied to clipboard', 'success');
    }).catch(() => { showToast('Failed to copy', 'error'); });
}

document.getElementById('qrFloorFilter').onchange = (e) => { renderQrGrid(e.target.value); };

function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg; t.className = 'toast show ' + type;
    clearTimeout(t._timer); t._timer = setTimeout(() => t.classList.remove('show'), 2800);
}

document.getElementById('qrDetailModal').addEventListener('click', (e) => {
    if (e.target.id === 'qrDetailModal') closeQrDetail();
});

renderQrGrid();

let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        renderQrGrid(document.getElementById('qrFloorFilter').value);
    }, 300);
});
</script>
@endsection