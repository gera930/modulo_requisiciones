<?php require_once 'header.php';
require_once 'Database.php';
require_once 'Requisicion.php';

$db = (new Database())->getConnection();
$req = new Requisicion($db);
$rows = $req->getAll($_SESSION['usuario_id'], false)->fetchAll(PDO::FETCH_ASSOC);
?>
<h2 class="page-title">Mis Requisiciones</h2>
<div class="card">
  <table>
    <thead><tr><th>Folio</th><th>Fecha</th><th>Tipo</th><th>Estado</th><th>Ver</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= $r['folio'] ?></td>
        <td><?= $r['fecha_solicitud'] ?></td>
        <td><?= $r['tipo_solicitud'] ?></td>
        <td><span class="badge badge-<?= strtolower($r['estado_solicitud']) == 'pendiente' ? 'pendiente' : (strtolower($r['estado_solicitud']) == 'autorizada' ? 'aprobado' : 'entregado') ?>"><?= ucfirst(strtolower($r['estado_solicitud'])) ?></span></td>
        <td><a href="ver_requisicion.php?id=<?= $r['id_solicitud'] ?>">Ver detalle</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($rows)): ?>
    <p>Aún no tienes requisiciones creadas.</p>
  <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>