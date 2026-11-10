<?php
require_once 'header.php';
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'Requisicion.php';

$db = (new Database())->getConnection();
$req = new Requisicion($db);
if (empty($_SESSION['csrf_eliminar_requisicion'])) $_SESSION['csrf_eliminar_requisicion'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_eliminar_requisicion'];
$mensaje = $_SESSION['msg'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['msg'], $_SESSION['error']);
$resumenSolicitantes = $req->getResumenPorSolicitante()->fetchAll(PDO::FETCH_ASSOC);
$solicitantesPorId = [];
foreach ($resumenSolicitantes as $solicitante) $solicitantesPorId[(int)$solicitante['id_usuario']] = $solicitante;

$idSolicitante = (int)($_GET['solicitante'] ?? 0);
if (!isset($solicitantesPorId[$idSolicitante])) $idSolicitante = 0;
$rows = $req->getAll($idSolicitante ?: null, true)->fetchAll(PDO::FETCH_ASSOC);
$solicitanteSeleccionado = $idSolicitante ? $solicitantesPorId[$idSolicitante] : null;
?>
<h2 class="page-title">Requisiciones por solicitante</h2>
<p class="page-subtitle">Consulta cuántas requisiciones ha creado cada solicitante y filtra el historial por persona.</p>
<?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<section class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div><h3>Actividad de solicitantes</h3><p style="color:#64756f;font-size:13px;margin-top:3px">Selecciona una persona para ver únicamente sus solicitudes.</p></div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;min-width:min(100%,420px)">
      <select name="solicitante" aria-label="Filtrar por solicitante">
        <option value="0">Todos los solicitantes</option>
        <?php foreach ($resumenSolicitantes as $s): ?>
          <option value="<?= (int)$s['id_usuario'] ?>" <?= $idSolicitante === (int)$s['id_usuario'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre_completo']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Filtrar</button>
    </form>
  </div>
  <div class="table-wrap"><table>
    <thead><tr><th>Solicitante</th><th>Usuario</th><th>Requisiciones realizadas</th><th>Acción</th></tr></thead>
    <tbody>
      <?php foreach ($resumenSolicitantes as $s): ?>
      <tr>
        <td><strong><?= htmlspecialchars($s['nombre_completo']) ?></strong></td>
        <td><?= htmlspecialchars($s['usuario']) ?></td>
        <td><span class="badge stock-disponible"><?= (int)$s['total_requisiciones'] ?></span></td>
        <td><a class="btn btn-outline" style="padding:5px 10px;font-size:12px" href="lista_requisiciones.php?solicitante=<?= (int)$s['id_usuario'] ?>">Ver requisiciones</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$resumenSolicitantes): ?><div class="empty-state">Todavía no hay solicitantes registrados.</div><?php endif; ?>
</section>

<section class="card">
  <h3><?= $solicitanteSeleccionado ? 'Requisiciones de ' . htmlspecialchars($solicitanteSeleccionado['nombre_completo']) : 'Todas las requisiciones' ?></h3>
  <p style="color:#64756f;font-size:13px;margin-top:3px;margin-bottom:12px">
    <?= $solicitanteSeleccionado ? (int)$solicitanteSeleccionado['total_requisiciones'] . ' requisiciones en total.' : count($rows) . ' requisiciones en total.' ?>
  </p>
  <div class="table-wrap"><table>
    <thead><tr><th>Folio</th><th>Fecha</th><th>Tipo</th><th>Solicitante</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['folio']) ?></td>
        <td><?= htmlspecialchars($r['fecha_solicitud']) ?></td>
        <td><?= htmlspecialchars($r['tipo_solicitud']) ?></td>
        <td><?= htmlspecialchars($r['nombre_usuario']) ?></td>
        <td><span class="badge badge-<?= strtolower($r['estado_solicitud']) == 'pendiente' ? 'pendiente' : (strtolower($r['estado_solicitud']) == 'autorizada' ? 'aprobado' : 'entregado') ?>"><?= ucfirst(strtolower($r['estado_solicitud'])) ?></span></td>
        <td>
          <a href="ver_requisicion.php?id=<?= (int)$r['id_solicitud'] ?>">Ver</a>
          <a href="imprimir_requisicion.php?id=<?= (int)$r['id_solicitud'] ?>" target="_blank" class="btn" style="padding:4px 8px;font-size:12px">Imprimir</a>
          <?php if ($r['estado_solicitud'] === 'PENDIENTE'): ?>
            <a href="autorizar_requisicion.php?id=<?= (int)$r['id_solicitud'] ?>" class="btn" style="padding:4px 8px;background:#27ae60;font-size:12px">Autorizar</a>
          <?php endif; ?>
          <form class="inline-form" action="eliminar_requisicion.php" method="POST" onsubmit="return confirm('¿Eliminar definitivamente esta requisición y sus partidas? Esta acción no se puede deshacer.')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_solicitud" value="<?= (int)$r['id_solicitud'] ?>">
            <button type="submit" class="btn btn-danger" style="padding:4px 8px;font-size:12px">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$rows): ?><div class="empty-state">No hay requisiciones para mostrar.</div><?php endif; ?>
</section>
<?php require_once 'footer.php'; ?>
