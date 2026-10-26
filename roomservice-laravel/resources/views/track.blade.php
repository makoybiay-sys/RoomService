@extends('layouts.app')

@section('title', 'My Orders — BLULANE')
@section('body-class', '')

@section('styles')
<style>
    .track-page { max-width: 720px; margin: 0 auto; padding: 32px 20px 60px; }
    .track-page-header { text-align: center; margin-bottom: 32px; }
    .track-page-header h1 { font-size: 32px; font-weight: 900; color: var(--text-strong); letter-spacing: -0.5px; margin-bottom: 4px; }
    .track-page-header p { font-size: 13px; color: var(--muted); font-weight: 500; }
    .track-card { background: var(--card); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 16px; box-shadow: var(--shadow); }
    .track-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
    .track-order-code { font-family: 'Courier New', monospace; font-size: 13px; font-weight: 700; color: var(--primary); letter-spacing: 0.5px; margin-bottom: 4px; }
    .track-room { font-size: 14px; font-weight: 700; color: var(--text-strong); }
    .track-notes { background: var(--primary-light); border-left: 3px solid var(--primary); border-radius: 8px; padding: 10px 14px; font-size: 12.5px; color: var(--text); margin-bottom: 20px; }
    .track-subtitle { font-size: 12px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 16px; }
    .track-headline { font-size: 22px; font-weight: 900; color: var(--text-strong); line-height: 1.2; margin-bottom: 6px; }
    .track-eta { font-size: 12.5px; color: var(--muted); margin-bottom: 20px; font-weight: 500; }
    .stack-timeline { display: flex; justify-content: space-between; position: relative; padding: 0 10px; margin-bottom: 8px; }
    .stack-timeline::before { content: ''; position: absolute; top: 16px; left: 22px; right: 22px; height: 3px; background: var(--border); border-radius: 2px; z-index: 0; }
    .stack-timeline::after { content: ''; position: absolute; top: 16px; left: 22px; height: 3px; background: linear-gradient(90deg, var(--primary), var(--primary-dark)); border-radius: 2px; z-index: 1; transition: width 0.6s ease; width: var(--progress, 0%); }
    .stack-step { display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; z-index: 2; flex: 1; min-width: 0; }
    .stack-step .marker { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; background: var(--card); border: 3px solid var(--border); color: var(--muted); margin-bottom: 10px; transition: all 0.3s; }
    .stack-step.done .marker, .stack-step.current .marker { background: var(--primary); border-color: var(--primary); color: #fff; }
    .stack-step.current .marker { box-shadow: 0 0 0 5px rgba(230, 58, 26, 0.18); animation: pulseStep 1.8s ease-in-out infinite; }
    @keyframes pulseStep { 0%, 100% { box-shadow: 0 0 0 5px rgba(230, 58, 26, 0.18); } 50% { box-shadow: 0 0 0 10px rgba(230, 58, 26, 0.05); } }
    .step-label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 2px; }
    .stack-step.done .step-label, .stack-step.current .step-label { color: var(--text-strong); }
    .track-item-row { display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; color: var(--text); gap: 12px; padding: 6px 0; border-bottom: 1px dashed var(--border); }
    .track-item-row:last-child { border-bottom: none; }
    .qty-tag { display: inline-block; background: var(--primary-light); color: var(--primary); padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 800; margin-left: 6px; }
    .track-total { display: flex; justify-content: space-between; align-items: center; padding: 16px 0 8px; font-size: 18px; font-weight: 900; color: var(--text-strong); }
    .view-receipt-btn { width: 100%; background: var(--primary); color: #fff; border: none; padding: 14px; border-radius: 10px; font-size: 13px; font-weight: 800; text-transform: uppercase; cursor: pointer; margin-top: 10px; }
    .track-empty { text-align: center; padding: 48px 24px; color: var(--muted); font-size: 13.5px; background: var(--card); border: 1px dashed var(--border); border-radius: 14px; font-weight: 500; }
    .track-list-item { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.2s; margin-bottom: 10px; }
    .track-list-item:hover { border-color: var(--primary); transform: translateY(-1px); }
    .track-list-item.current { border-color: var(--primary); background: var(--primary-light); }
    .track-list-code { font-family: 'Courier New', monospace; font-size: 12.5px; font-weight: 800; color: var(--primary); margin-bottom: 3px; }
    .track-list-meta { font-size: 12px; color: var(--muted); font-weight: 600; }
</style>
@endsection

@section('content')
<header class="site-header">
    <div class="site-header-inner">
        <a href="/" class="site-logo"><img src="{{ asset('assets/uploads/image-logo/BLULANE.png') }}" alt="BLULANE"></a>
        <nav class="site-nav">
            <a href="/">Order Food</a>
            <a href="/track" class="active" id="navMyOrders">My Orders</a>
        </nav>
        <button class="theme-toggle" id="themeToggle" title="Toggle theme">☾</button>
    </div>
</header>

<div class="track-page">
    <div class="track-page-header">
        <h1>My Orders</h1>
        <p>Live status updates every 4 seconds</p>
    </div>

    <div id="trackContent"><div class="track-empty">Loading order details...</div></div>

    <h2 class="track-subtitle" style="margin: 32px 0 14px;">All My Orders</h2>
    <div id="allOrdersContent"><div class="track-empty">Loading...</div></div>
</div>

<!-- RECEIPT MODAL -->
<div class="receipt-modal-overlay" id="receiptModal" style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 16px;">
    <div class="receipt-modal" style="background: var(--card); border-radius: 20px; width: 100%; max-width: 420px; max-height: 92vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4); position: relative;">
        <button class="receipt-modal-close" onclick="closeReceiptModal()" style="position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(255, 255, 255, 0.25); color: #fff; font-size: 20px; cursor: pointer; z-index: 10;">×</button>
        <div class="receipt-modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; padding: 24px; text-align: center; border-radius: 20px 20px 0 0;">
            <h2 style="font-size: 18px; font-weight: 900; margin-bottom: 3px;">Order Receipt</h2>
        </div>
        <div class="receipt-modal-body" id="receiptModalBody" style="padding: 22px; display: flex; flex-direction: column; gap: 16px;">
            <div class="receipt-loading">Loading receipt...</div>
        </div>
        <div style="padding: 0 22px 22px;">
            <button class="receipt-modal-btn" onclick="closeReceiptModal()" style="width: 100%; padding: 13px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-size: 12.5px; font-weight: 800; text-transform: uppercase; cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/track.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const roomFromUrl = params.get('room');
        const roomFromStorage = localStorage.getItem('rs_current_room');
        const currentRoom = roomFromUrl || roomFromStorage;

        if (currentRoom) {
            document.getElementById('navMyOrders').href = '/track?room=' + currentRoom;
        }
    });
</script>
@endsection