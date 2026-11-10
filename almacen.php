<?php
require_once 'header.php';
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'ClaseAlmacen.php';

$db = (new Database())->getConnection();
$alm = new ClaseAlmacen($db);
$avisos = [
    'ok' => ['success', 'Operación realizada correctamente.'],
    'deleted' => ['success', 'El material se eliminó del almacén.'],
    'inactivated' => ['success', 'Este material ya aparece en requisiciones anteriores. Se desactivó para conservar el historial y ya no estará disponible para nuevas solicitudes.'],
    'not_found' => ['error', 'No se encontró el material solicitado.'],
    'delete_error' => ['error', 'No se pudo retirar el material. Intenta de nuevo o contacta al administrador del sistema.'],
    'invalid_action' => ['error', 'La acción solicitada no es válida.']
];
$aviso = $avisos[$_GET['msg'] ?? ''] ?? null;

$rows = $alm->getAll()->fetchAll();
?>
<h2 class="page-title">Inventario de almacén</h2>
<p class="page-subtitle">Controla existencias y detecta materiales que necesitan reabastecimiento.</p>

<?php if ($aviso): ?><div class="alert alert-<?= $aviso[0] ?>"><?= htmlspecialchars($aviso[1]) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
  <a href="almacen_nuevo.php" class="btn">+ Nuevo Material</a>
</div>

<div class="table-wrap"><table>
  <thead>
    <tr>
      <th>Código</th>
      <th>Descripción</th>
      <th>Unidad</th>
      <th>Existencia</th>
      <th>Stock Mínimo</th>
      <th>Estado</th>
      <th>Acción</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $m): ?>
    <tr>
      <td><?= htmlspecialchars($m['codigo']) ?></td>
      <td><?= htmlspecialchars($m['descripcion']) ?></td>
      <td><?= htmlspecialchars($m['unidad_medida']) ?></td>
      <td><?= number_format($m['existencia'], 2) ?></td>
      <td><?= number_format($m['stock_minimo'], 2) ?></td>
      <td><span class="badge <?= (float)$m['existencia'] <= 0 ? 'stock-agotado' : ((float)$m['existencia'] <= (float)$m['stock_minimo'] ? 'stock-bajo' : 'stock-disponible') ?>"><?= (float)$m['existencia'] <= 0 ? 'AGOTADO' : ((float)$m['existencia'] <= (float)$m['stock_minimo'] ? 'STOCK BAJO' : 'DISPONIBLE') ?></span></td>
      <td>
        <a href="almacen_editar.php?id=<?= $m['id_material'] ?>" class="btn btn-outline" style="padding:5px 10px;font-size:12px;">Editar</a>
        <a href="almacen_accion.php?eliminar=<?= $m['id_material'] ?>" class="btn btn-danger" style="padding:5px 10px;font-size:12px;" onclick="return confirm('¿Eliminar este material? Si ya aparece en requisiciones, se desactivará para conservar el historial.')">Eliminar</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>

<?php if (empty($rows)): ?>
<div class="empty-state">No hay materiales registrados. Agrega el primero para comenzar a controlar existencias.</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
</div>
</body>
</html>
