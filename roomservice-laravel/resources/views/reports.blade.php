@extends('layouts.app')

@section('title', 'Daily Report — BLULANE')
@section('body-class', 'admin-body')

@section('styles')
<style>
    .report-container { max-width: 900px; margin: 40px auto; padding: 24px; background: var(--card); border-radius: 16px; box-shadow: var(--shadow); }
    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border); padding-bottom: 16px; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
    .report-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .report-table th, .report-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
    .report-table th { background: var(--bg-alt); font-size: 11px; text-transform: uppercase; color: var(--primary); }
    .report-table tr:hover { background: var(--bg-alt); }
    .grand-total { background: var(--primary-light); font-weight: 900; color: var(--primary); }
</style>
@endsection

@section('content')
<div class="report-container">
    <div class="report-header">
        <div>
            <h2>Daily Food Charge Report</h2>
            <p style="color: var(--muted); font-size: 13px;">Food charges to be posted to room folios</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="date" id="reportDate" class="form-control" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-alt); color: var(--text);" onchange="loadReport()">
            <button class="btn btn-primary" onclick="window.print()">Print Report</button>
        </div>
    </div>

    <h3 style="font-size: 14px; text-transform: uppercase; color: var(--muted); margin-bottom: 12px;">Food Charges Per Room</h3>
    <table class="report-table">
        <thead><tr><th>Floor</th><th>Room</th><th>Orders</th><th>Total Charges</th></tr></thead>
        <tbody id="reportBody"></tbody>
    </table>

    <h3 style="font-size: 14px; text-transform: uppercase; color: var(--muted); margin-bottom: 12px; margin-top: 32px;">Top Selling Dishes</h3>
    <table class="report-table">
        <thead><tr><th>Dish Name</th><th>Qty Sold</th><th>Total Sales</th></tr></thead>
        <tbody id="topDishesBody"></tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
async function loadReport() {
    const date = document.getElementById('reportDate').value || new Date().toISOString().split('T')[0];
    const reportBody = document.getElementById('reportBody');
    const topDishesBody = document.getElementById('topDishesBody');
    
    reportBody.innerHTML = '<tr><td colspan="4">Loading...</td></tr>';
    topDishesBody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';

    try {
        // Placeholder API call. Kailangan ng bagong route para dito.
        const res = await fetch(`/api/reports?date=${date}`);
        const data = await res.json();

        if (data.rooms && data.rooms.length > 0) {
            let grandTotal = 0;
            let html = data.rooms.map(r => {
                grandTotal += parseFloat(r.total_amount);
                return `<tr><td>Floor ${r.floor_number}</td><td>Room ${r.room_number}</td><td>${r.order_count}</td><td>PHP ${parseFloat(r.total_amount).toFixed(2)}</td></tr>`;
            }).join('');
            html += `<tr class="grand-total"><td colspan="3" style="text-align:right;">GRAND TOTAL</td><td>PHP ${grandTotal.toFixed(2)}</td></tr>`;
            reportBody.innerHTML = html;
        } else {
            reportBody.innerHTML = '<tr><td colspan="4">No charges for this date.</td></tr>';
        }

        if (data.top && data.top.length > 0) {
            topDishesBody.innerHTML = data.top.map(t => `
                <tr><td>${t.item_name}</td><td>${t.total_qty}</td><td>PHP ${parseFloat(t.total_sales).toFixed(2)}</td></tr>
            `).join('');
        } else {
            topDishesBody.innerHTML = '<tr><td colspan="3">No sales for this date.</td></tr>';
        }

    } catch (e) {
        console.error(e);
        reportBody.innerHTML = '<tr><td colspan="4" style="color:red;">Failed to load.</td></tr>';
        topDishesBody.innerHTML = '<tr><td colspan="3" style="color:red;">Failed to load.</td></tr>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('reportDate').value = new Date().toISOString().split('T')[0];
    loadReport();
});
</script>
@endsection