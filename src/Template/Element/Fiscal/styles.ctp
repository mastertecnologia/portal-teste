<?php
/**
 * Estilos comuns do módulo Fiscal (tema escuro PGM).
 */
?>
<style>
.fpm-wrap { font-family:'DM Sans',system-ui,sans-serif; }
.fpm-topbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:16px 20px 12px; border-bottom:1px solid rgba(255,255,255,.08); }
.fpm-h1 { font-size:19px; font-weight:600; color:#e6edf3; margin:0; }
.fpm-h1 i { color:#1D9E75; margin-right:8px; }
.fpm-actions { display:flex; gap:8px; flex-wrap:wrap; }
.fpm-filters { display:flex; gap:10px; flex-wrap:wrap; padding:12px 20px; border-bottom:1px solid rgba(255,255,255,.06); align-items:flex-end; }
.fpm-filters label { font-size:11px; color:#7d8590; text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:4px; }
.fpm-filters input, .fpm-filters select {
    background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9;
    padding:6px 10px; font-size:13px; min-height:34px;
}
.fpm-table-wrap { padding:16px 20px 24px; overflow-x:auto; }
.fpm-table { width:100%; border-collapse:collapse; font-size:13px; }
.fpm-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:8px 10px; text-align:left; border-bottom:1px solid rgba(255,255,255,.07); }
.fpm-table td { padding:10px; border-bottom:1px solid rgba(255,255,255,.05); color:#c9d1d9; vertical-align:middle; }
.fpm-table tr:hover td { background:rgba(255,255,255,.03); }
.fpm-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.fpm-badge.ok { background:rgba(29,158,117,.18); color:#5cdbc0; }
.fpm-badge.warn { background:rgba(255,204,0,.15); color:#ffc107; }
.fpm-badge.muted { background:rgba(255,255,255,.08); color:#9ca3af; }
.fpm-empty { text-align:center; padding:40px; color:#484f58; }
.fpm-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin-bottom:16px; }
.fpm-card-title { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin:0 0 14px; }
.fpm-row { display:flex; gap:14px; flex-wrap:wrap; }
.fpm-field { flex:1; min-width:200px; }
.fpm-field label { font-size:12px; color:#7d8590; font-weight:600; display:block; margin-bottom:4px; }
.fpm-field .form-control { background:#0d1117; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#e6edf3; }
.fpm-field .form-control:focus { border-color:rgba(29,158,117,.45); box-shadow:0 0 0 3px rgba(29,158,117,.12); outline:none; }
.fpm-footer { display:flex; gap:10px; justify-content:flex-end; padding:8px 0 28px; flex-wrap:wrap; }
.fpm-items-table { width:100%; border-collapse:collapse; font-size:12.5px; margin-top:8px; }
.fpm-items-table th { color:#7d8590; font-size:10px; text-transform:uppercase; padding:6px 4px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fpm-items-table td { padding:6px 4px; vertical-align:top; }
.fpm-items-table input, .fpm-items-table textarea, .fpm-items-table select {
    width:100%; background:#0d1117; border:1px solid rgba(255,255,255,.08); border-radius:5px; color:#e6edf3; padding:5px 8px; font-size:12px;
}
.fpm-items-table textarea.fpm-serial-area { min-height:52px; resize:vertical; font-family:ui-monospace,monospace; }
.fpm-muted { color:#7d8590; font-size:12px; }
.fpm-nav-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; padding:16px 20px; }
.fpm-nav-card {
    display:block; padding:16px; border-radius:10px; border:1px solid rgba(255,255,255,.08); background:#161b22;
    color:#e6edf3; text-decoration:none; transition:border-color .15s, background .15s;
}
.fpm-nav-card:hover { border-color:rgba(29,158,117,.45); background:#1c2128; color:#fff; text-decoration:none; }
.fpm-nav-card i { color:#1D9E75; margin-right:8px; }
.fpm-homolog-wrap { padding:0 20px 12px; display:flex; flex-direction:column; gap:8px; }
.fpm-alert { border-radius:8px; padding:10px 14px; font-size:13px; line-height:1.45; }
.fpm-alert-warn { background:rgba(255,204,0,.12); border:1px solid rgba(255,204,0,.35); color:#e3b341; }
.fpm-alert-info { background:rgba(88,166,255,.10); border:1px solid rgba(88,166,255,.28); color:#79b8ff; }
.fpm-alert-danger { background:rgba(248,81,73,.12); border:1px solid rgba(248,81,73,.4); color:#ff8b87; }
.fpm-kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:0; border-bottom:1px solid rgba(255,255,255,.06); }
.fpm-kpi { padding:14px 16px; border-right:1px solid rgba(255,255,255,.06); }
.fpm-kpi:last-child { border-right:none; }
.fpm-kpi-l { font-size:10px; text-transform:uppercase; color:#7d8590; letter-spacing:.06em; }
.fpm-kpi-v { font-size:20px; font-weight:700; color:#e6edf3; margin-top:4px; }
.fpm-check-prod { display:flex; align-items:flex-start; gap:10px; font-size:13px; color:#c9d1d9; cursor:pointer; text-align:left; max-width:520px; }
.fpm-check-prod input { margin-top:3px; flex-shrink:0; }
.fpm-emitir-prod { margin-left:auto; display:flex; flex-direction:column; align-items:flex-end; gap:10px; }
</style>
