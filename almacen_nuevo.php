<?php
require_once 'header.php';
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'ClaseAlmacen.php';

$db = (new Database())->getConnection();
$alm = new ClaseAlmacen($db);
$msg = ""; $err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($alm->create($_POST['descripcion'], $_POST['unidad_medida'], $_POST['existencia'], $_POST['stock_minimo'], $_POST['estado'])) {
            header("Location: almacen.php?msg=ok"); exit;
        } else {
            $err = "Error al guardar el material.";
        }
    } catch (Exception $e) {
        $msg_error = $e->getMessage();
        if (strpos($msg_error, "Duplicate entry") !== false) {
            $err = "Ya existe un material con ese código. Por favor, use un código diferente.";
        } else {
            $err = "Error al guardar: " . $msg_error;
        }
    }
}
?>
<h2 class="page-title">Agregar material</h2>
<p class="page-subtitle">Completa la información del producto. Su código único se asignará automáticamente al guardarlo.</p>
<?php if ($err): ?><div class="alert alert-error"><?= $err ?></div><?php endif; ?>
<form method="POST" class="card form-card">
  <div class="auto-code-note"><span class="note-icon">✦</span><div><strong>Código automático</strong><br><small>Se genera con la fecha de alta y un número único.</small></div></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
    <div class="form-wide"><label>Descripción del material</label><input type="text" name="descripcion" placeholder="Ej. Hojas blancas tamaño carta" maxlength="150" required></div>
    <div><label>Unidad</label><input type="text" name="unidad_medida" value="Pieza" required></div>
    <div><label>Existencia</label><input type="number" name="existencia" value="0" min="0" step="0.01" required></div>
    <div><label>Stock Mínimo</label><input type="number" name="stock_minimo" value="0" min="0" step="0.01" required></div>
    <div><label>Estado</label><select name="estado"><option>ACTIVO</option><option>INACTIVO</option></select></div>
  </div>
  <div class="form-actions"><button type="submit" class="btn">Guardar material</button>
  <a href="almacen.php" class="btn btn-outline" style="margin-left:10px;">Volver</a>
  </div>
</form>
<?php require_once 'footer.php'; ?>
