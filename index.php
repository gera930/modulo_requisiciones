<?php
require_once 'header.php';
require_once 'Database.php';
require_once 'Requisicion.php';
require_once 'ClaseAlmacen.php';
require_once 'Usuario.php';

$db = (new Database())->getConnection();
$req = new Requisicion($db);
$almacen = (new ClaseAlmacen($db))->getInventarioResumen()->fetchAll(PDO::FETCH_ASSOC);
$id_user = $_SESSION['usuario_id'];
$admin = esAdmin();
$pendientesAcceso = $admin ? (new Usuario($db))->contarPendientes() : 0;
$pend = $req->contarPorEstado('PENDIENTE', $id_user, $admin);
$aut = $req->contarPorEstado('AUTORIZADA', $id_user, $admin);
$ent = $req->contarPorEstado('ENTREGADA', $id_user, $admin);
$agotados = 0; $bajoStock = 0;
foreach ($almacen as $material) {
    if ($material['nivel_stock'] === 'AGOTADO') $agotados++;
    if ($material['nivel_stock'] === 'BAJO') $bajoStock++;
}
?>
<h2 class="page-title">Panel de control</h2>
<p class="page-subtitle">Hola, <?= htmlspecialchars($_SESSION['nombre_completo']) ?>. Aquí tienes el estado del inventario y tus requisiciones.</p>
<?php if ($pendientesAcceso > 0): ?>
  <div class="alert alert-warning" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><span><strong><?= $pendientesAcceso ?></strong> perfil<?= $pendientesAcceso === 1 ? '' : 'es' ?> espera<?= $pendientesAcceso === 1 ? '' : 'n' ?> autorización.</span><a class="btn" href="usuarios.php">Revisar solicitudes</a></div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><span>Requisiciones pendientes</span><strong><?= (int)$pend ?></strong></div>
  <div class="stat-card"><span>Productos por agotarse</span><strong style="color:#a15c00"><?= $bajoStock ?></strong></div>
  <div class="stat-card"><span>Productos agotados</span><strong style="color:#b42318"><?= $agotados ?></strong></div>
</div>

<section class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div><h3>Existencias de almacén</h3><p style="color:#64756f;font-size:13px;margin-top:3px">Consulta rápida del stock disponible y de los materiales que requieren atención.</p></div>
    <?php if ($admin): ?><a href="almacen.php" class="btn btn-outline">Administrar almacén</a><?php endif; ?>
  </div>
  <?php if ($almacen): ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Código</th><th>Producto</th><th>Unidad</th><th>Existencia</th><th>Stock mínimo</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($almacen as $m): $nivel = strtolower($m['nivel_stock']); ?>
      <tr>
        <td><?= htmlspecialchars($m['codigo']) ?></td><td><strong><?= htmlspecialchars($m['descripcion']) ?></strong></td>
        <td><?= htmlspecialchars($m['unidad_medida']) ?></td>
        <td class="stock-cell <?= $nivel === 'agotado' ? 'stock-agotado' : ($nivel === 'bajo' ? 'stock-bajo' : '') ?>"><?= number_format((float)$m['existencia'], 2) ?></td>
        <td><?= number_format((float)$m['stock_minimo'], 2) ?></td>
        <td><span class="badge stock-<?= $nivel ?>"><?= $nivel === 'bajo' ? 'STOCK BAJO' : htmlspecialchars($m['nivel_stock']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <div class="empty-state" style="margin-top:16px"><strong>Aún no hay materiales registrados.</strong><br>El administrador puede agregarlos desde la sección Almacén.</div>
  <?php endif; ?>
</section>

<section class="card">
  <h3 style="margin-bottom:14px">Accesos rápidos</h3>
  <a href="nueva_requisicion.php" class="btn">+ Nueva requisición</a>
  <?php if ($admin): ?>
    <a href="lista_requisiciones.php" class="btn btn-outline" style="margin-left:8px">Todas las requisiciones</a>
    <a href="usuarios.php" class="btn btn-outline" style="margin-left:8px">Usuarios</a>
    <a href="respaldo.php" class="btn btn-outline" style="margin-left:8px">Respaldo BD</a>
  <?php else: ?>
    <a href="mis_requisiciones.php" class="btn btn-outline" style="margin-left:8px">Mis requisiciones</a>
  <?php endif; ?>
</section>
<?php require_once 'footer.php'; ?>
