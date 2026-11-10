<?php
require_once 'Database.php';
require_once 'Requisicion.php';

$db = (new Database())->getConnection();
$req = new Requisicion($db);
$id = (int)($_GET['id'] ?? 0);
$r = $req->getById($id);

if (!$r) {
    http_response_code(404);
    exit("Requisición no encontrada.");
}

$det = $req->getDetalles($id)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Imprimir - Requisición <?= htmlspecialchars($r['folio']) ?></title>
<style>
  @page { margin: 2cm; }
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #000; margin: 0; }
  .no-print { display: none; }
  .header-print { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #1b5e20; padding-bottom: 15px; }
  .header-print img { max-height: 110px; width: auto; }
  .header-print h1 { margin: 8px 0 4px; color: #1b5e20; font-size: 17px; text-transform: uppercase; }
  .header-print p { margin: 2px 0; color: #555; font-size: 11px; }
  .info-box { margin-bottom: 20px; font-size: 12px; }
  .info-box p { margin: 3px 0; }
  table { width: 100%; border-collapse: collapse; margin-top: 15px; }
  th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; font-size: 11px; }
  th { background: #1b5e20; color: #fff; font-weight: bold; }
  tr:nth-child(even) td { background: #f9f9f9; }
  .firmas { margin-top: 35px; }
  .firmas p { margin: 18px 0; }
  .linea { display: inline-block; width: 220px; border-bottom: 1px solid #000; }
  .footer-print { margin-top: 40px; border-top: 1px solid #999; padding-top: 10px; font-size: 9px; color: #666; text-align: center; }
  @media print {
    .no-print { display: none !important; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
  <div class="header-print">
    <img src="logo.png" alt="Logo UTRNG">
    <h1>Universidad Tecnológica de la Región Norte de Guerrero</h1>
    <p>Módulo de Requisición de Inventario v1.0.0</p>
    <p>Folio: <strong><?= htmlspecialchars($r['folio']) ?></strong></p>
  </div>

  <div class="info-box">
    <p><b>Fecha:</b> <?= $r['fecha_solicitud'] ?> &nbsp;&nbsp;|&nbsp;&nbsp; <b>Estado:</b> <?= ucfirst(strtolower($r['estado_solicitud'])) ?></p>
    <p><b>Solicitante:</b> <?= htmlspecialchars($r['nombre_usuario']) ?></p>
    <p><b>Departamento Destino:</b> <?= htmlspecialchars($r['nombre_depto_destino']) ?></p>
    <p><b>Tipo:</b> <?= htmlspecialchars($r['tipo_solicitud']) ?></p>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:30%;">Material</th>
        <th style="width:12%;">Código</th>
        <th style="width:16%;">Cant. Solicitada</th>
        <th style="width:16%;">Cant. Entregada</th>
        <th style="width:16%;">Origen</th>
        <th style="width:10%;">Justificación</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($det as $d): ?>
      <tr>
        <td><?= htmlspecialchars($d['material_nombre']) ?></td>
        <td><?= htmlspecialchars($d['material_codigo']) ?></td>
        <td><?= number_format($d['cantidad_solicitada'], 2) ?></td>
        <td><?= number_format($d['cantidad_entregada'] ?? 0, 2) ?></td>
        <td><?= htmlspecialchars($d['origen_suministro']) ?></td>
        <td><?= htmlspecialchars($d['justificacion'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="firmas">
    <p><b>Firma Solicitante:</b> <span class="linea"></span></p>
    <p><b>Firma Autoriza:</b> <span class="linea"></span></p>
    <p><b>Firma Recibe Material:</b> <span class="linea"></span></p>
  </div>

  <div class="footer-print">
    <?= APP_NAME ?> v<?= APP_VERSION ?> | Base de datos: <?= DB_NAME ?> | Impreso: <?= date('d/m/Y H:i') ?>
  </div>

  <div class="no-print" style="text-align:center; margin-top:30px;">
    <button onclick="window.print()" class="btn">Imprimir / Guardar PDF</button>
    <a href="ver_requisicion.php?id=<?= $r['id_solicitud'] ?>" class="btn btn-outline" style="margin-left:10px;">Volver al detalle</a>
  </div>

  <script>
    // Se abre el diálogo de impresión automáticamente al cargar la página
    window.onload = function() {
      window.print();
    };
  </script>
</body>
</html>
