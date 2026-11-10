<?php
require_once 'header.php';
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'Usuario.php';

$db = (new Database())->getConnection();
$user = new Usuario($db);
$user->id_usuario = $_SESSION['usuario_id'];
$msg = ""; $err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $user->id_rol = $_POST['id_rol'];
    $user->id_departamento = $_POST['id_departamento'];
    $user->usuario = $_POST['usuario'];
    $user->contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $user->nombre_completo = $_POST['nombre_completo'];
    $user->cargo = $_POST['cargo'];
    $user->estado = $_POST['estado'];

    if ($user->existeUsuario($user->usuario)) {
        $err = "El usuario ya existe.";
    } elseif ($user->create()) {
        $msg = "Usuario creado.";
    } else {
        $err = "Error al crear.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'aprobar') {
    $idPendiente = (int)($_POST['id_usuario'] ?? 0);
    $aprobar = $db->prepare("UPDATE usuario SET estado = 'ACTIVO'
        WHERE id_usuario = ? AND estado = 'PENDIENTE'
        AND id_rol = (SELECT id_rol FROM rol WHERE nombre_rol = 'Solicitante' LIMIT 1)");
    $aprobar->execute([$idPendiente]);
    if ($aprobar->rowCount() > 0) $msg = 'Perfil aprobado. El solicitante ya puede iniciar sesión.';
    else $err = 'No se encontró una solicitud pendiente para aprobar.';
}

if (isset($_GET['eliminar'])) {
    if ($user->delete((int)$_GET['eliminar'])) {
        $msg = "Usuario eliminado.";
    } else {
        $err = "No se puede eliminar.";
    }
}

$roles = $db->query("SELECT * FROM rol")->fetchAll();
$deptos = $db->query("SELECT * FROM departamento")->fetchAll();
$rows = $user->getAll()->fetchAll();
$pendientes = array_values(array_filter($rows, function($u) { return $u['estado'] === 'PENDIENTE' && $u['nombre_rol'] === 'Solicitante'; }));
?>
<h2 class="page-title">Usuarios</h2>
<p class="page-subtitle">Administra perfiles y autoriza solicitudes de acceso con correo institucional.</p>
<?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= $err ?></div><?php endif; ?>

<section class="card">
  <h3>Solicitudes de acceso pendientes <span class="badge <?= $pendientes ? 'stock-bajo' : 'stock-disponible' ?>"><?= count($pendientes) ?></span></h3>
  <p style="color:#64756f;font-size:13px;margin-top:4px">Revisa el correo institucional antes de aprobar un perfil.</p>
  <?php if ($pendientes): ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo institucional</th><th>Departamento</th><th>Acción</th></tr></thead>
    <tbody><?php foreach ($pendientes as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['nombre_completo']) ?></td>
        <td><?= htmlspecialchars($p['usuario']) ?></td>
        <td><?= htmlspecialchars($p['correo_institucional'] ?? '') ?></td>
        <td><?= htmlspecialchars($p['nombre_departamento']) ?></td>
        <td><form method="POST" onsubmit="return confirm('¿Aprobar el acceso de este solicitante?')"><input type="hidden" name="accion" value="aprobar"><input type="hidden" name="id_usuario" value="<?= (int)$p['id_usuario'] ?>"><button class="btn" type="submit" style="padding:6px 12px;font-size:12px">Aprobar perfil</button></form></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?><div class="empty-state" style="margin-top:14px">No hay perfiles pendientes de autorización.</div><?php endif; ?>
</section>

<h3>Crear Usuario</h3>
<form method="POST">
  <input type="hidden" name="accion" value="crear">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
    <div><label>Rol</label><select name="id_rol" required><?php foreach ($roles as $r): ?><option value="<?= $r['id_rol'] ?>"><?= $r['nombre_rol'] ?></option><?php endforeach; ?></select></div>
    <div><label>Departamento</label><select name="id_departamento" required><?php foreach ($deptos as $d): ?><option value="<?= $d['id_departamento'] ?>"><?= $d['nombre_departamento'] ?></option><?php endforeach; ?></select></div>
    <div><label>Usuario</label><input type="text" name="usuario" required></div>
    <div><label>Contraseña</label><input type="password" name="contrasena" required></div>
    <div><label>Nombre completo</label><input type="text" name="nombre_completo" required></div>
    <div><label>Cargo</label><input type="text" name="cargo"></div>
    <div><label>Estado</label><select name="estado"><option>ACTIVO</option><option>INACTIVO</option></select></div>
  </div>
  <button type="submit" class="btn">Guardar</button>
</form>

<h3 style="margin-top:25px;">Lista de Usuarios</h3>
<div class="table-wrap"><table>
  <thead><tr><th>Usuario</th><th>Nombre</th><th>Correo institucional</th><th>Rol</th><th>Estado</th><th>Acción</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $u): ?>
    <tr>
      <td><?= htmlspecialchars($u['usuario']) ?></td>
      <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
      <td><?= htmlspecialchars($u['correo_institucional'] ?? '—') ?></td>
      <td><?= htmlspecialchars($u['nombre_rol']) ?></td>
      <td><span class="badge <?= $u['estado'] === 'ACTIVO' ? 'stock-disponible' : ($u['estado'] === 'PENDIENTE' ? 'stock-bajo' : 'stock-agotado') ?>"><?= htmlspecialchars($u['estado']) ?></span></td>
      <td><?php if ($u['id_usuario'] != $_SESSION['usuario_id']): ?><a href="usuarios.php?eliminar=<?= $u['id_usuario'] ?>" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php else: ?>—<?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
<?php require_once 'footer.php'; ?>
