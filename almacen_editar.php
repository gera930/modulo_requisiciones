<?php
require_once 'header.php';
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'ClaseAlmacen.php';

$db = (new Database())->getConnection();
$alm = new ClaseAlmacen($db);
$id = (int)($_GET['id'] ?? 0);
$m = $alm->getById($id);

if (!$m) { header("Location: almacen.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alm->update($id, $_POST['descripcion'], $_POST['unidad_medida'], $_POST['existencia'], $_POST['stock_minimo'], $_POST['estado']);
    header("Location: almacen.php?msg=ok"); exit;
}
?>
<h2 class="page-title">Editar material</h2>
<p class="page-subtitle">Actualiza la información y las existencias de este producto.</p>
<form method="POST" class="card form-card">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
    <div><label>Código único</label><input type="text" value="<?= htmlspecialchars($m['codigo']) ?>" readonly><small class="field-hint">El código se asigna automáticamente y no cambia.</small></div>
    <div><label>Descripción</label><input type="text" name="descripcion" value="<?= htmlspecialchars($m['descripcion']) ?>" maxlength="150" required></div>
    <div><label>Unidad</label><input type="text" name="unidad_medida" value="<?= $m['unidad_medida'] ?>" required></div>
    <div><label>Existencia</label><input type="number" name="existencia" value="<?= $m['existencia'] ?>" min="0" step="0.01" required></div>
    <div><label>Stock Mínimo</label><input type="number" name="stock_minimo" value="<?= $m['stock_minimo'] ?>" min="0" step="0.01" required></div>
    <div><label>Estado</label><select name="estado"><option <?= $m['estado']=='ACTIVO'?'selected':'' ?>>ACTIVO</option><option <?= $m['estado']=='INACTIVO'?'selected':'' ?>>INACTIVO</option></select></div>
  </div>
  <div class="form-actions"><button type="submit" class="btn">Guardar cambios</button>
  <a href="almacen.php" class="btn btn-outline" style="margin-left:10px;">Volver</a>
  </div>
</form>
<?php require_once 'footer.php'; ?>
