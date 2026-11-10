<?php require_once 'header.php';
require_once 'Database.php';
require_once 'Requisicion.php';

$db = (new Database())->getConnection();
$req = new Requisicion($db);
$id = (int)($_GET['id'] ?? 0);
$r = $req->getById($id);

if (!$r) { header("Location: index.php"); exit; }
if (!esAdmin() && $r['id_usuario_solicita'] != $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso para ver esta requisición.";
    header("Location: index.php"); exit;
}

$det = $req->getDetalles($id)->fetchAll(PDO::FETCH_ASSOC);
?>
<h2 class="page-title">Detalle de Requisición</h2>
<div class="card">
  <h3>Requisición <?= $r['folio'] ?></h3>
  <p><b>Fecha:</b> <?= $r['fecha_solicitud'] ?> | <b>Tipo:</b> <?= $r['tipo_solicitud'] ?> | <b>Estado:</b> <?= ucfirst(strtolower($r['estado_solicitud'])) ?></p>
  <p><b>Solicitante:</b> <?= htmlspecialchars($r['nombre_usuario']) ?> | <b>Destino:</b> <?= htmlspecialchars($r['nombre_depto_destino']) ?></p>

  <table>
    <thead><tr><th>Material</th><th>Cód.</th><th>Cant. Solicitada</th><th>Cant. Entregada</th><th>Origen</th><th>Justificación</th></tr></thead>
    <tbody>
      <?php foreach ($det as $d): ?>
      <tr>
        <td><?= htmlspecialchars($d['material_nombre']) ?></td>
        <td><?= $d['material_codigo'] ?></td>
        <td><?= number_format($d['cantidad_solicitada'], 2) ?></td>
        <td><?= number_format($d['cantidad_entregada'], 2) ?></td>
        <td><?= $d['origen_suministro'] ?></td>
        <td><?= htmlspecialchars($d['justificacion']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (esAdmin() && $r['estado_solicitud'] === 'PENDIENTE'): ?>
    <p><a href="autorizar_requisicion.php?id=<?= $r['id_solicitud'] ?>" class="btn">Autorizar esta requisición</a></p>
  <?php endif; ?>
  <a href="imprimir_requisicion.php?id=<?= $r['id_solicitud'] ?>" target="_blank" class="btn" style="margin-left:10px;">Imprimir</a>
  <a href="javascript:history.back()" class="btn btn-outline" style="margin-left:10px;">Volver</a>
</div>
<?php require_once 'footer.php'; ?>