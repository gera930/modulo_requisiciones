<?php
require_once 'session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?></title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  :root { --green:#176b4b; --green-dark:#104c38; --ink:#172b25; --muted:#64756f; --line:#e4ebe7; --surface:#fff; --bg:#f2f6f8; --red:#b42318; --amber:#a15c00; --blue:#2864a5; --teal:#087e8b; }
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--ink); line-height:1.5; }
  .sidebar {
    width: 240px; background: linear-gradient(180deg,#164f3b,#103d30); color: #fff;
    position: fixed; height: 100vh; padding-top: 20px;
  }
  .sidebar h2 {
    padding: 0 20px 20px; font-size: 17px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff;
  }
  .sidebar ul { list-style: none; padding: 0; }
  .sidebar li { padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
  .sidebar a { color: #d6e8df; text-decoration: none; display:block; margin:-12px -20px; padding:12px 20px; border-radius:0 8px 8px 0; }
  .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.12); color: #fff; }
  .sidebar .user {
    padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 13px; text-align: center;
  }
  .sidebar .user b { display: block; color: #fff; margin-bottom: 4px; }
  .sidebar .user small { color: rgba(255,255,255,0.7); }
  .topbar {
    margin-left: 240px; background: linear-gradient(90deg,#fff 0%,#f3faf7 70%,#eff7fb 100%); padding: 15px 28px;
    border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;
  }
  .topbar h1 { font-size: 19px; color: var(--green-dark); }
  .topbar-actions { display:flex;align-items:center;justify-content:flex-end;min-width:160px;padding-left:22px;border-left:1px solid var(--line); }
  .topbar .btn-logout {
    display:inline-flex;align-items:center;gap:9px;padding:9px 14px;background:#fff5f4;color:#a62c22;
    border:1px solid #f0d0cc;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;transition:.15s ease;
  }
  .topbar .btn-logout:hover { background:#ffe8e5;border-color:#e9b7b1;color:#8e2118; }
  .topbar .btn-logout svg { width:17px;height:17px; }
  .main { margin-left: 240px; padding: 30px; min-height: calc(100vh - 60px); max-width:1600px; }
  .page-title { font-size: 27px; margin-bottom: 6px; color: var(--ink); letter-spacing:-.4px; }
  .page-subtitle { color:var(--muted); margin-bottom:22px; }
  .card { background: var(--surface); padding: 22px; border: 1px solid var(--line); margin-bottom: 22px; border-radius: 14px; box-shadow:0 5px 18px rgba(20,55,40,.035); }
  .btn {
    background: linear-gradient(135deg,#1c8058,#176b4b); color: #fff; padding: 10px 17px; border: none;
    border-radius: 8px; cursor: pointer; font-size: 14px; font-weight:600; text-decoration: none; display: inline-block; transition:.15s ease;
  }
  .btn:hover { background: var(--green-dark); transform:translateY(-1px); }
  .btn-outline {
    background: transparent; border: 1px solid var(--green); color: var(--green);
  }
  .btn-outline:hover { background: #1b5e20; color: #fff; }
  .btn-danger { background: linear-gradient(135deg,#d4483e,#b42318); }
  .btn-danger:hover { background: #b71c1c; }
  .table-wrap { width:100%; overflow-x:auto; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  th, td { border-bottom: 1px solid var(--line); padding: 12px 14px; text-align: left; font-size: 13px; }
  th { background: #f4f8f6; color: #52665e; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
  tr:hover td { background:#f9fcfa; }
  input, select, textarea { width:100%; padding:10px 12px; border:1px solid #d5e0da; border-radius:8px; color:var(--ink); background:#fff; font:inherit; }
  input:focus,select:focus,textarea:focus { outline:3px solid rgba(23,107,75,.13); border-color:var(--green); }
  label { display:block; font-size:13px; font-weight:600; color:#40534b; margin-bottom:6px; }
  .stat-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin:18px 0 22px; }
  .stat-card { background:linear-gradient(145deg,#fff,#f7fbf9); border:1px solid var(--line); border-left:4px solid var(--green); border-radius:12px; padding:17px 19px; }
  .stat-card:nth-child(2) { border-left-color:#e0a12b; background:linear-gradient(145deg,#fff,#fffaf0); }
  .stat-card:nth-child(3) { border-left-color:#dc6258; background:linear-gradient(145deg,#fff,#fff5f4); }
  .stat-card span { display:block; font-size:12px; color:var(--muted); font-weight:600; }
  .stat-card strong { display:block; font-size:27px; margin-top:4px; }
  .stock-disponible { color:var(--green); background:#e6f4ec; }
  .stock-bajo { color:var(--amber); background:#fff2d8; }
  .stock-agotado { color:var(--red); background:#ffebe9; }
  .stock-cell { font-weight:700; }
  .badge { display:inline-block; }
  .badge {
    padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700;
  }
  .badge-pendiente { background: #fff3e0; color: #ef6c00; }
  .badge-aprobado { background: #e8f5e9; color: #1b5e20; }
  .badge-entregado { background: #e3f2fd; color: #1565c0; }
  .alert { padding: 11px 15px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
  .alert-success { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
  .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
  .alert-warning { background:#fff7e6;color:#8a5400;border:1px solid #f0d99d; }
  .empty-state { padding:28px; text-align:center; background:#f7faf8; border:1px dashed #cbd9d1; border-radius:10px; color:var(--muted); }
  .form-card { border-top:4px solid var(--teal); }
  .auto-code-note { display:flex;align-items:center;gap:13px;padding:14px 16px;margin-bottom:22px;border-radius:10px;color:#155c67;background:linear-gradient(100deg,#e3f5f2,#eaf4fb); }
  .auto-code-note small,.field-hint { color:#5b747a;font-size:12px; }
  .note-icon { width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:#c4e9e3;color:#087e8b;font-size:20px; }
  .form-wide { grid-column:1 / -1; }
  .form-actions { margin-top:22px;padding-top:17px;border-top:1px solid var(--line); }
  .inline-form { display:inline-block;margin:0;vertical-align:middle; }
  .stock-notice { margin-top:7px;padding:8px 10px;border-radius:7px;font-size:11px;line-height:1.4; }
  .stock-notice[hidden] { display:none; }
  .stock-notice-warning { color:#8a3c15;background:#fff0e3;border:1px solid #f3caa7; }
  .stock-notice-ok { color:#24664c;background:#eaf6ef;border:1px solid #cce6d5; }
  input[readonly] { background:#f1f5f7;color:#527078;font-weight:700; }
  .alert-success { background:linear-gradient(90deg,#e8f7ee,#eef9f7); border-color:#bfe3cd; }
  .alert-error { background:linear-gradient(90deg,#fff0ef,#fff7f2); border-color:#f2c5bf; }
  @media(max-width:850px) { .sidebar{width:190px}.topbar,.main{margin-left:190px}.main{padding:20px} }
  @media(max-width:620px) { .sidebar{position:static;width:auto;height:auto;padding:10px}.sidebar .logo-sidebar,.sidebar .user,.sidebar h2{display:none}.sidebar ul{display:flex;overflow:auto}.sidebar li{padding:7px 10px;white-space:nowrap}.sidebar a{margin:0;padding:8px 10px;border-radius:7px}.topbar,.main{margin-left:0}.topbar{padding:12px 16px}.topbar h1{font-size:15px}.topbar-actions{min-width:0;padding-left:12px}.topbar .btn-logout{padding:8px 10px;font-size:12px;gap:6px}.topbar .btn-logout svg{width:15px;height:15px}.main{padding:17px}.stat-grid{grid-template-columns:1fr} }
</style>
</head>
<body>
  <aside class="sidebar">
    <div class="logo-sidebar" style="text-align:center; padding: 0 15px 20px;">
      <img src="logo.png" alt="Logo UTRNG" style="width: 100%; height: auto; max-width: 180px; margin-bottom: 10px;">
    </div>
    <h2>Módulo de Requisición</h2>
    <nav>
      <ul>
        <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Panel de Control</a></li>
        <li><a href="nueva_requisicion.php" class="<?= basename($_SERVER['PHP_SELF']) == 'nueva_requisicion.php' ? 'active' : '' ?>">Nueva Requisición</a></li>
        <?php if (esAdmin()): ?>
          <li><a href="lista_requisiciones.php" class="<?= basename($_SERVER['PHP_SELF']) == 'lista_requisiciones.php' ? 'active' : '' ?>">Todas las Requisiciones</a></li>
          <li><a href="almacen.php" class="<?= basename($_SERVER['PHP_SELF']) == 'almacen.php' ? 'active' : '' ?>">Almacén</a></li>
          <li><a href="usuarios.php" class="<?= basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : '' ?>">Usuarios</a></li>
        <?php else: ?>
          <li><a href="mis_requisiciones.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mis_requisiciones.php' ? 'active' : '' ?>">Mis Requisiciones</a></li>
        <?php endif; ?>
      </ul>
    </nav>
    <div class="user">
      <b><?= htmlspecialchars($_SESSION['nombre_completo']) ?></b>
      <small><?= htmlspecialchars($_SESSION['nombre_rol']) ?></small>
    </div>
  </aside>

  <div class="topbar">
    <h1><?= APP_NAME ?></h1>
    <div class="topbar-actions">
      <a href="logout.php" class="btn-logout" aria-label="Cerrar sesión">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3m9-8h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Cerrar sesión</span>
      </a>
    </div>
  </div>

  <div class="main">
