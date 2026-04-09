<?php
session_start();
if (!isset($_SESSION['gov_user'])) {
    header("Location: gov_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollution Monitoring Dashboard</title>

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <style>
    /* ── Reset & Base ─────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: #0f172a;
      color: #e2e8f0;
      min-height: 100vh;
    }

    /* ── Top Nav ──────────────────────────────────────────────── */
    .topnav {
      background: #1e293b;
      border-bottom: 1px solid #334155;
      padding: 0 24px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .topnav .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.15rem;
      font-weight: 700;
      color: #38bdf8;
    }
    .topnav .brand span { font-size: 1.4rem; }
    .nav-actions { display: flex; align-items: center; gap: 14px; }
    .refresh-badge {
      font-size: 0.78rem;
      color: #94a3b8;
      background: #0f172a;
      border: 1px solid #334155;
      border-radius: 20px;
      padding: 4px 12px;
    }
    .refresh-badge b { color: #38bdf8; }
    .btn-nav {
      background: #334155;
      color: #e2e8f0;
      border: none;
      padding: 7px 16px;
      border-radius: 8px;
      font-size: 0.85rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-nav:hover { background: #475569; }
    .btn-nav.danger { background: #7f1d1d; color: #fca5a5; }
    .btn-nav.danger:hover { background: #991b1b; }

    /* ── Layout ───────────────────────────────────────────────── */
    .page { max-width: 1400px; margin: 0 auto; padding: 24px 20px 48px; }

    /* ── Stat Cards ───────────────────────────────────────────── */
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }
    .stat-card {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 12px;
      padding: 20px 22px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .stat-card .label { font-size: 0.78rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
    .stat-card .value { font-size: 2rem; font-weight: 700; line-height: 1; }
    .stat-card .sub   { font-size: 0.78rem; color: #64748b; }
    .stat-card.safe   { border-color: #166534; }
    .stat-card.safe   .value { color: #4ade80; }
    .stat-card.warning { border-color: #854d0e; }
    .stat-card.warning .value { color: #facc15; }
    .stat-card.critical { border-color: #7f1d1d; }
    .stat-card.critical .value { color: #f87171; }
    .stat-card.total  { border-color: #1e40af; }
    .stat-card.total  .value { color: #60a5fa; }

    /* ── Section Titles ───────────────────────────────────────── */
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .section-title {
      font-size: 1rem;
      font-weight: 600;
      color: #cbd5e1;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .section-title .dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #38bdf8;
      display: inline-block;
    }

    /* ── Filters ──────────────────────────────────────────────── */
    .filters {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }
    .filters select,
    .filters input[type="text"],
    .filters input[type="number"] {
      background: #1e293b;
      border: 1px solid #334155;
      color: #e2e8f0;
      border-radius: 8px;
      padding: 7px 12px;
      font-size: 0.85rem;
      outline: none;
      transition: border-color 0.2s;
    }
    .filters select:focus,
    .filters input:focus { border-color: #38bdf8; }
    .filters label { font-size: 0.82rem; color: #94a3b8; }
    .btn-filter {
      background: #0369a1;
      color: #fff;
      border: none;
      padding: 7px 16px;
      border-radius: 8px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-filter:hover { background: #0284c7; }
    .btn-reset {
      background: #334155;
      color: #e2e8f0;
      border: none;
      padding: 7px 14px;
      border-radius: 8px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-reset:hover { background: #475569; }

    /* ── Panel ────────────────────────────────────────────────── */
    .panel {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 14px;
      padding: 20px 22px;
      margin-bottom: 24px;
    }

    /* ── Data Table ───────────────────────────────────────────── */
    .table-wrap { overflow-x: auto; }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
    }
    thead th {
      background: #0f172a;
      color: #94a3b8;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: .06em;
      padding: 10px 14px;
      text-align: left;
      white-space: nowrap;
    }
    tbody tr {
      border-bottom: 1px solid #1e293b;
      transition: background 0.15s;
    }
    tbody tr:hover { background: #0f172a; }
    tbody td {
      padding: 11px 14px;
      vertical-align: middle;
      color: #cbd5e1;
    }

    /* ── Status Badges ────────────────────────────────────────── */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      white-space: nowrap;
    }
    .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
    .badge.safe     { background: #14532d; color: #4ade80; }
    .badge.safe::before     { background: #4ade80; }
    .badge.warning  { background: #422006; color: #facc15; }
    .badge.warning::before  { background: #facc15; }
    .badge.critical { background: #450a0a; color: #f87171; }
    .badge.critical::before { background: #f87171; box-shadow: 0 0 6px #f87171; }
    .badge.no_data  { background: #1e293b; color: #64748b; border: 1px solid #334155; }
    .badge.no_data::before  { background: #64748b; }

    /* ── Pollution Value ──────────────────────────────────────── */
    .pval { font-weight: 700; font-size: 0.95rem; }
    .pval.safe     { color: #4ade80; }
    .pval.warning  { color: #facc15; }
    .pval.critical { color: #f87171; }
    .pval.no_data  { color: #64748b; }

    /* ── Alerts Section ───────────────────────────────────────── */
    .alert-list { display: flex; flex-direction: column; gap: 10px; }
    .alert-item {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 14px 16px;
      border-radius: 10px;
      border-left: 4px solid transparent;
    }
    .alert-item.critical { background: #1c0a0a; border-color: #ef4444; }
    .alert-item.warning  { background: #1c1200; border-color: #eab308; }
    .alert-icon { font-size: 1.3rem; flex-shrink: 0; margin-top: 1px; }
    .alert-body { flex: 1; }
    .alert-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; }
    .alert-item.critical .alert-title { color: #fca5a5; }
    .alert-item.warning  .alert-title { color: #fde68a; }
    .alert-meta { font-size: 0.78rem; color: #64748b; }
    .alert-pval { font-size: 0.82rem; font-weight: 600; margin-top: 3px; }
    .alert-item.critical .alert-pval { color: #f87171; }
    .alert-item.warning  .alert-pval { color: #facc15; }
    .no-alerts {
      text-align: center;
      padding: 32px;
      color: #4ade80;
      font-size: 0.95rem;
    }
    .no-alerts .icon { font-size: 2.5rem; display: block; margin-bottom: 8px; }

    /* ── Chart ────────────────────────────────────────────────── */
    .chart-container { position: relative; height: 280px; }

    /* ── Two-column grid ──────────────────────────────────────── */
    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 24px;
    }
    @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }

    /* ── Loading / Empty ──────────────────────────────────────── */
    .loading {
      text-align: center;
      padding: 40px;
      color: #475569;
      font-size: 0.9rem;
    }
    .spinner {
      display: inline-block;
      width: 22px; height: 22px;
      border: 3px solid #334155;
      border-top-color: #38bdf8;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      vertical-align: middle;
      margin-right: 8px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Toast ────────────────────────────────────────────────── */
    #toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 10px;
      padding: 12px 20px;
      font-size: 0.85rem;
      color: #e2e8f0;
      box-shadow: 0 8px 24px rgba(0,0,0,0.4);
      opacity: 0;
      transform: translateY(12px);
      transition: opacity 0.3s, transform 0.3s;
      z-index: 999;
      max-width: 320px;
    }
    #toast.show { opacity: 1; transform: translateY(0); }
    #toast.success { border-color: #166534; color: #4ade80; }
    #toast.error   { border-color: #7f1d1d; color: #f87171; }

    /* ── Responsive ───────────────────────────────────────────── */
    @media (max-width: 600px) {
      .topnav { padding: 0 14px; }
      .page   { padding: 16px 12px 40px; }
      .stat-grid { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>

<!-- ── Top Navigation ──────────────────────────────────────────── -->
<nav class="topnav">
  <div class="brand">
    <span>🌿</span> Pollution Monitor
  </div>
  <div class="nav-actions">
    <span class="refresh-badge">Auto-refresh in <b id="countdown">30</b>s</span>
    <button class="btn-nav" onclick="refreshAll()">⟳ Refresh Now</button>
    <a href="register.php" class="btn-nav">📋 Vehicles</a>
    <a href="challan.php" class="btn-nav">💳 Challans</a>
    <a href="logout.php" class="btn-nav danger">⏻ Logout</a>
  </div>
</nav>

<!-- ── Page Body ───────────────────────────────────────────────── -->
<div class="page">

  <!-- Stat Summary Cards -->
  <div class="stat-grid" id="stat-grid">
    <div class="stat-card total">
      <span class="label">Total Vehicles</span>
      <span class="value" id="stat-total">—</span>
      <span class="sub">registered fleet</span>
    </div>
    <div class="stat-card safe">
      <span class="label">Safe</span>
      <span class="value" id="stat-safe">—</span>
      <span class="sub">below 150 AQI</span>
    </div>
    <div class="stat-card warning">
      <span class="label">Warning</span>
      <span class="value" id="stat-warning">—</span>
      <span class="sub">150 – 249 AQI</span>
    </div>
    <div class="stat-card critical">
      <span class="label">Critical</span>
      <span class="value" id="stat-critical">—</span>
      <span class="sub">250+ AQI</span>
    </div>
  </div>

  <!-- ── Filters ──────────────────────────────────────────────── -->
  <div class="panel" style="margin-bottom:20px; padding:16px 20px;">
    <div class="filters">
      <label>Status:</label>
      <select id="filter-status">
        <option value="all">All Vehicles</option>
        <option value="critical">Critical Only</option>
        <option value="warning">Warning Only</option>
        <option value="safe">Safe Only</option>
      </select>

      <label>Search:</label>
      <input type="text" id="filter-search" placeholder="Vehicle no. or owner…" style="width:200px;">

      <label>History (days):</label>
      <input type="number" id="filter-days" value="7" min="1" max="365" style="width:80px;">

      <button class="btn-filter" onclick="applyFilters()">Apply</button>
      <button class="btn-reset"  onclick="resetFilters()">Reset</button>
    </div>
  </div>

  <!-- ── Vehicle Status Table ─────────────────────────────────── -->
  <div class="panel">
    <div class="section-header">
      <span class="section-title"><span class="dot"></span> Vehicle Pollution Status</span>
      <span style="font-size:0.78rem; color:#64748b;" id="table-meta">Loading…</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Vehicle No.</th>
            <th>Owner</th>
            <th>Type</th>
            <th>Pollution (AQI)</th>
            <th>Violations</th>
            <th>Last Reading</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="vehicle-tbody">
          <tr><td colspan="8" class="loading"><span class="spinner"></span>Loading vehicle data…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Two-column: Alerts + Chart ───────────────────────────── -->
  <div class="two-col">

    <!-- Active Alerts -->
    <div class="panel">
      <div class="section-header">
        <span class="section-title"><span class="dot" style="background:#ef4444;"></span> Active Alerts</span>
        <span style="font-size:0.78rem; color:#64748b;" id="alert-meta"></span>
      </div>
      <div class="alert-list" id="alert-list">
        <div class="loading"><span class="spinner"></span>Loading alerts…</div>
      </div>
    </div>

    <!-- Pollution Distribution Chart -->
    <div class="panel">
      <div class="section-header">
        <span class="section-title"><span class="dot" style="background:#a78bfa;"></span> Fleet Status Distribution</span>
      </div>
      <div class="chart-container">
        <canvas id="statusChart"></canvas>
      </div>
    </div>

  </div>

  <!-- ── Violation History ─────────────────────────────────────── -->
  <div class="panel">
    <div class="section-header">
      <span class="section-title"><span class="dot" style="background:#fb923c;"></span> Violation History</span>
      <span style="font-size:0.78rem; color:#64748b;" id="history-meta"></span>
    </div>
    <div class="chart-container" style="height:300px;">
      <canvas id="historyChart"></canvas>
    </div>
    <div style="margin-top:16px;">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Vehicle No.</th>
              <th>Owner</th>
              <th>Pollution (AQI)</th>
              <th>Violations</th>
              <th>Date / Time</th>
            </tr>
          </thead>
          <tbody id="history-tbody">
            <tr><td colspan="5" class="loading"><span class="spinner"></span>Loading history…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /.page -->

<!-- Toast notification -->
<div id="toast"></div>

<script>
/* ── State ──────────────────────────────────────────────────────── */
let statusChart  = null;
let historyChart = null;
let countdownVal = 30;
let countdownTimer = null;
let allVehicles  = [];

const THRESHOLD_WARNING  = 150;
const THRESHOLD_CRITICAL = 250;

/* ── Helpers ────────────────────────────────────────────────────── */
function statusOf(val) {
  if (val === null || val === undefined) return 'no_data';
  if (val >= THRESHOLD_CRITICAL) return 'critical';
  if (val >= THRESHOLD_WARNING)  return 'warning';
  return 'safe';
}

function statusLabel(s) {
  return { safe: '✅ Safe', warning: '⚠️ Warning', critical: '🔴 Critical', no_data: '— No Data' }[s] || s;
}

function fmtDate(str) {
  if (!str) return '—';
  const d = new Date(str.replace(' ', 'T'));
  return isNaN(d) ? str : d.toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' });
}

function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show ' + type;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.className = ''; }, 3500);
}

/* ── Countdown ──────────────────────────────────────────────────── */
function startCountdown() {
  clearInterval(countdownTimer);
  countdownVal = 30;
  document.getElementById('countdown').textContent = countdownVal;
  countdownTimer = setInterval(() => {
    countdownVal--;
    document.getElementById('countdown').textContent = countdownVal;
    if (countdownVal <= 0) {
      refreshAll();
    }
  }, 1000);
}

/* ── Filters ────────────────────────────────────────────────────── */
function getFilters() {
  return {
    status: document.getElementById('filter-status').value,
    search: document.getElementById('filter-search').value.trim().toLowerCase(),
    days:   parseInt(document.getElementById('filter-days').value) || 7,
  };
}

function applyFilters() {
  renderVehicleTable(allVehicles);
  loadHistory();
}

function resetFilters() {
  document.getElementById('filter-status').value = 'all';
  document.getElementById('filter-search').value = '';
  document.getElementById('filter-days').value   = '7';
  applyFilters();
}

/* ── Vehicle Status ─────────────────────────────────────────────── */
async function loadVehicleStatus() {
  try {
    const res  = await fetch('api/get_vehicle_status.php?status=all&_=' + Date.now());
    const data = await res.json();
    if (data.status !== 'success') throw new Error(data.message);
    allVehicles = data.vehicles;
    renderVehicleTable(allVehicles);
    renderStatCards(allVehicles);
    renderStatusChart(allVehicles);
    renderAlerts(allVehicles);
  } catch (e) {
    document.getElementById('vehicle-tbody').innerHTML =
      `<tr><td colspan="8" class="loading" style="color:#f87171;">⚠ Failed to load vehicle data: ${e.message}</td></tr>`;
    showToast('Failed to load vehicle data', 'error');
  }
}

function renderStatCards(vehicles) {
  const counts = { safe: 0, warning: 0, critical: 0, no_data: 0 };
  vehicles.forEach(v => counts[v.status] = (counts[v.status] || 0) + 1);
  document.getElementById('stat-total').textContent   = vehicles.length;
  document.getElementById('stat-safe').textContent    = counts.safe;
  document.getElementById('stat-warning').textContent = counts.warning;
  document.getElementById('stat-critical').textContent= counts.critical;
}

function renderVehicleTable(vehicles) {
  const f = getFilters();

  const filtered = vehicles.filter(v => {
    if (f.status !== 'all' && v.status !== f.status) return false;
    if (f.search) {
      const hay = (v.vehicle_number + ' ' + v.owner_name).toLowerCase();
      if (!hay.includes(f.search)) return false;
    }
    return true;
  });

  const meta = document.getElementById('table-meta');
  meta.textContent = `Showing ${filtered.length} of ${vehicles.length} vehicles`;

  if (filtered.length === 0) {
    document.getElementById('vehicle-tbody').innerHTML =
      `<tr><td colspan="8" class="loading">No vehicles match the current filters.</td></tr>`;
    return;
  }

  const rows = filtered.map((v, i) => {
    const pval = v.current_pollution_value !== null
      ? `<span class="pval ${v.status}">${v.current_pollution_value.toFixed(1)}</span>`
      : `<span class="pval no_data">—</span>`;
    return `
      <tr>
        <td style="color:#475569;">${i + 1}</td>
        <td style="font-weight:600; color:#e2e8f0;">${esc(v.vehicle_number)}</td>
        <td>${esc(v.owner_name)}</td>
        <td style="color:#94a3b8;">${esc(v.vehicle_type || '—')}</td>
        <td>${pval}</td>
        <td>
          <span style="font-weight:600; color:${v.violation_count > 0 ? '#f87171' : '#4ade80'};">
            ${v.violation_count}
          </span>
        </td>
        <td style="color:#64748b; font-size:0.8rem;">${fmtDate(v.last_reading_time)}</td>
        <td><span class="badge ${v.status}">${statusLabel(v.status)}</span></td>
      </tr>`;
  }).join('');

  document.getElementById('vehicle-tbody').innerHTML = rows;
}

/* ── Alerts ─────────────────────────────────────────────────────── */
function renderAlerts(vehicles) {
  const alerts = vehicles
    .filter(v => v.status === 'critical' || v.status === 'warning')
    .sort((a, b) => {
      // critical first, then by pollution value desc
      if (a.status !== b.status) return a.status === 'critical' ? -1 : 1;
      return (b.current_pollution_value || 0) - (a.current_pollution_value || 0);
    });

  const meta = document.getElementById('alert-meta');
  meta.textContent = `${alerts.filter(a => a.status === 'critical').length} critical · ${alerts.filter(a => a.status === 'warning').length} warning`;

  if (alerts.length === 0) {
    document.getElementById('alert-list').innerHTML =
      `<div class="no-alerts"><span class="icon">✅</span>All vehicles within safe limits</div>`;
    return;
  }

  const items = alerts.map(v => {
    const icon = v.status === 'critical' ? '🚨' : '⚠️';
    const msg  = v.status === 'critical'
      ? `Pollution critically high — immediate action required`
      : `Pollution above safe threshold — monitor closely`;
    return `
      <div class="alert-item ${v.status}">
        <span class="alert-icon">${icon}</span>
        <div class="alert-body">
          <div class="alert-title">${esc(v.vehicle_number)} — ${esc(v.owner_name)}</div>
          <div class="alert-meta">${msg} · ${fmtDate(v.last_reading_time)}</div>
          <div class="alert-pval">AQI: ${v.current_pollution_value !== null ? v.current_pollution_value.toFixed(1) : '—'} · Violations: ${v.violation_count}</div>
        </div>
      </div>`;
  }).join('');

  document.getElementById('alert-list').innerHTML = items;
}

/* ── Status Doughnut Chart ──────────────────────────────────────── */
function renderStatusChart(vehicles) {
  const counts = { safe: 0, warning: 0, critical: 0, no_data: 0 };
  vehicles.forEach(v => counts[v.status] = (counts[v.status] || 0) + 1);

  const ctx = document.getElementById('statusChart').getContext('2d');
  if (statusChart) statusChart.destroy();

  statusChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Safe', 'Warning', 'Critical', 'No Data'],
      datasets: [{
        data: [counts.safe, counts.warning, counts.critical, counts.no_data],
        backgroundColor: ['#166534', '#854d0e', '#7f1d1d', '#1e293b'],
        borderColor:     ['#4ade80', '#facc15', '#f87171', '#475569'],
        borderWidth: 2,
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#94a3b8', padding: 16, font: { size: 12 } }
        },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.label}: ${ctx.parsed} vehicle${ctx.parsed !== 1 ? 's' : ''}`
          }
        }
      }
    }
  });
}

/* ── Violation History ──────────────────────────────────────────── */
async function loadHistory() {
  const f = getFilters();
  const url = `api/get_violation_history.php?days=${f.days}&_=${Date.now()}`;

  try {
    const res  = await fetch(url);
    const data = await res.json();
    if (data.status !== 'success') throw new Error(data.message);

    renderHistoryTable(data.history);
    renderHistoryChart(data.history, f.days);
    document.getElementById('history-meta').textContent =
      `${data.count} record${data.count !== 1 ? 's' : ''} in the last ${f.days} day${f.days !== 1 ? 's' : ''}`;
  } catch (e) {
    document.getElementById('history-tbody').innerHTML =
      `<tr><td colspan="5" class="loading" style="color:#f87171;">⚠ Failed to load history: ${e.message}</td></tr>`;
  }
}

function renderHistoryTable(history) {
  if (!history || history.length === 0) {
    document.getElementById('history-tbody').innerHTML =
      `<tr><td colspan="5" class="loading">No violation history in this period.</td></tr>`;
    return;
  }

  // Apply search filter
  const search = document.getElementById('filter-search').value.trim().toLowerCase();
  const filtered = search
    ? history.filter(h => (h.vehicle_number + ' ' + h.owner_name).toLowerCase().includes(search))
    : history;

  const rows = filtered.map(h => {
    const s = statusOf(h.pollution_value);
    return `
      <tr>
        <td style="font-weight:600; color:#e2e8f0;">${esc(h.vehicle_number)}</td>
        <td>${esc(h.owner_name)}</td>
        <td><span class="pval ${s}">${h.pollution_value.toFixed(1)}</span></td>
        <td style="color:#f87171; font-weight:600;">${h.violation_count}</td>
        <td style="color:#64748b; font-size:0.8rem;">${fmtDate(h.violation_date)}</td>
      </tr>`;
  }).join('');

  document.getElementById('history-tbody').innerHTML = rows || `<tr><td colspan="5" class="loading">No results match your search.</td></tr>`;
}

function renderHistoryChart(history, days) {
  // Build a daily aggregation: date → max pollution value
  const buckets = {};
  history.forEach(h => {
    const day = h.violation_date ? h.violation_date.substring(0, 10) : null;
    if (!day) return;
    if (!buckets[day] || h.pollution_value > buckets[day]) {
      buckets[day] = h.pollution_value;
    }
  });

  // Fill in all days in range (even if no data)
  const labels = [];
  const values = [];
  const colors = [];
  for (let i = days - 1; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().substring(0, 10);
    const label = d.toLocaleDateString('en-IN', { month: 'short', day: 'numeric' });
    labels.push(label);
    const val = buckets[key] ?? null;
    values.push(val);
    colors.push(
      val === null ? '#334155'
      : val >= THRESHOLD_CRITICAL ? '#ef4444'
      : val >= THRESHOLD_WARNING  ? '#eab308'
      : '#22c55e'
    );
  }

  const ctx = document.getElementById('historyChart').getContext('2d');
  if (historyChart) historyChart.destroy();

  historyChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Peak Pollution (AQI)',
        data: values,
        backgroundColor: colors,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ctx.parsed.y !== null
              ? ` AQI: ${ctx.parsed.y.toFixed(1)}`
              : ' No data'
          }
        }
      },
      scales: {
        x: {
          ticks: { color: '#64748b', font: { size: 11 } },
          grid:  { color: '#1e293b' }
        },
        y: {
          beginAtZero: true,
          ticks: { color: '#64748b', font: { size: 11 } },
          grid:  { color: '#1e293b' },
          title: { display: true, text: 'AQI Value', color: '#475569', font: { size: 11 } }
        }
      }
    }
  });
}

/* ── XSS helper ─────────────────────────────────────────────────── */
function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* ── Master Refresh ─────────────────────────────────────────────── */
async function refreshAll() {
  startCountdown();
  await Promise.all([loadVehicleStatus(), loadHistory()]);
  showToast('Dashboard refreshed', 'success');
}

/* ── Boot ───────────────────────────────────────────────────────── */
refreshAll();
</script>
</body>
</html>
