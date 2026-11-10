<?php
require_once 'config.php';
require_once 'Database.php';
require_once 'Usuario.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }

$db = (new Database())->getConnection();
$user = new Usuario($db);
$departamentos = $db->query("SELECT id_departamento, nombre_departamento FROM departamento ORDER BY nombre_departamento")->fetchAll(PDO::FETCH_ASSOC);
$errores = [];
$creado = false;
$valores = ['nombre_completo' => '', 'usuario' => '', 'correo_institucional' => '', 'cargo' => '', 'id_departamento' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($valores as $campo => $valor) $valores[$campo] = trim($_POST[$campo] ?? $valor);
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar_contrasena'] ?? '';
    $correo = strtolower($valores['correo_institucional']);

    if ($valores['nombre_completo'] === '') $errores[] = 'Escribe tu nombre completo.';
    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $valores['usuario'])) $errores[] = 'El usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strtolower(substr(strrchr($correo, '@') ?: '', 1)) !== strtolower(INSTITUTIONAL_EMAIL_DOMAIN)) {
        $errores[] = 'Usa tu correo institucional con dominio @' . INSTITUTIONAL_EMAIL_DOMAIN . '.';
    }
    if (!isset($departamentosPorId)) {
        $departamentosPorId = [];
        foreach ($departamentos as $departamento) $departamentosPorId[(string)$departamento['id_departamento']] = true;
    }
    if (!isset($departamentosPorId[$valores['id_departamento']])) $errores[] = 'Selecciona un departamento válido.';
    if (strlen($contrasena) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($contrasena !== $confirmar) $errores[] = 'Las contraseñas no coinciden.';

    if (!$errores && $user->existeUsuario($valores['usuario'])) $errores[] = 'Ese nombre de usuario ya está registrado.';
    if (!$errores && $user->existeCorreoInstitucional($correo)) $errores[] = 'Ese correo institucional ya tiene una cuenta o una solicitud pendiente.';

    if (!$errores) {
        try {
            $creado = $user->crearSolicitudRegistro(
                (int)$valores['id_departamento'],
                $valores['usuario'],
                password_hash($contrasena, PASSWORD_DEFAULT),
                $valores['nombre_completo'],
                $valores['cargo'],
                $correo
            );
            if (!$creado) $errores[] = 'No se pudo enviar la solicitud. Verifica que exista el rol de solicitante.';
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) == 1062) $errores[] = 'El usuario o el correo institucional ya están registrados.';
            else {
                error_log('Error al registrar perfil: ' . $e->getMessage());
                $errores[] = 'Ocurrió un error al enviar la solicitud. Inténtalo de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#124936"><title>Crear perfil - <?= htmlspecialchars(APP_NAME) ?></title>
<style>
  *{box-sizing:border-box}body{min-height:100vh;margin:0;padding:24px;display:grid;place-items:center;font-family:'Segoe UI',Tahoma,sans-serif;color:#172b25;background:radial-gradient(ellipse at 12% 14%,rgba(54,143,94,.72),transparent 38%),radial-gradient(ellipse at 90% 88%,rgba(32,112,101,.55),transparent 35%),linear-gradient(135deg,#103d30,#0d352a)}
  .shell{width:min(760px,100%);padding:34px 42px;border:1px solid rgba(255,255,255,.22);border-radius:22px;background:linear-gradient(145deg,#f5fbf7,#e2f1e8);box-shadow:0 24px 70px rgba(5,28,20,.3)}
  .brand{display:flex;align-items:center;gap:17px;padding-bottom:20px;margin-bottom:23px;border-bottom:1px solid #d4e5da}.brand img{width:66px;height:66px;object-fit:contain;filter:brightness(0) saturate(100%) invert(27%) sepia(23%) saturate(1250%) hue-rotate(103deg) brightness(91%)}.brand small{display:block;color:#507064;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase}.brand strong{display:block;color:#164c37;font-size:17px;margin-top:4px}
  h1{margin:0;color:#183a2d;font-size:27px}.intro{margin:6px 0 22px;color:#62756b;font-size:14px;line-height:1.5}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px 18px}.wide{grid-column:1/-1}label{display:block;margin-bottom:6px;color:#354b41;font-size:13px;font-weight:700}input,select{width:100%;height:44px;padding:0 12px;border:1px solid #cddfd3;border-radius:9px;background:#fff;color:#172b25;font:inherit}input:focus,select:focus{outline:3px solid rgba(42,147,105,.14);border-color:#2a9369}.hint{display:block;margin-top:5px;color:#71847a;font-size:11px}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:23px;padding-top:18px;border-top:1px solid #d4e5da}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:43px;padding:0 17px;border:1px solid #176b4b;border-radius:9px;background:linear-gradient(110deg,#208258,#176b4b);color:#fff;text-decoration:none;font:inherit;font-size:14px;font-weight:700;cursor:pointer}.btn.secondary{background:transparent;color:#176b4b}.notice{margin:0 0 16px;padding:13px 15px;border-radius:9px;font-size:13px;line-height:1.5}.notice.error{border:1px solid #efc4bd;background:#fff2ef;color:#a62c22}.notice.success{border:1px solid #bcdcc7;background:#e9f6ee;color:#1c6544}.notice ul{padding-left:20px;margin:5px 0 0}.done{text-align:center;padding:14px 10px 6px}.done h1{font-size:25px}.done p{color:#536a5e;line-height:1.6}.done .btn{margin-top:12px}
  @media(max-width:600px){body{padding:13px}.shell{padding:23px 19px;border-radius:17px}.grid{grid-template-columns:1fr;gap:13px}.wide{grid-column:auto}.brand{padding-bottom:15px;margin-bottom:18px}.brand img{width:54px;height:54px}.brand strong{font-size:15px}h1{font-size:24px}.actions .btn{flex:1}}
</style>
</head>
<body>
  <main class="shell">
    <div class="brand"><img src="logo_login.png" alt=""><div><small>Requisiciones e inventario</small><strong>Registro de solicitantes</strong></div></div>
    <?php if ($creado): ?>
      <div class="done"><h1>Solicitud enviada</h1><p>Tu perfil quedó pendiente de autorización. La administración revisará tu correo institucional y activará la cuenta cuando apruebe el acceso.</p><a class="btn" href="login.php?registro=pendiente">Volver al inicio de sesión</a></div>
    <?php else: ?>
      <h1>Crear perfil</h1><p class="intro">Completa tus datos institucionales. El acceso se habilitará después de la aprobación de administración.</p>
      <?php if ($errores): ?><div class="notice error" role="alert"><strong>Revisa estos datos:</strong><ul><?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="POST" autocomplete="on">
        <div class="grid">
          <div class="wide"><label for="nombre_completo">Nombre completo</label><input id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($valores['nombre_completo']) ?>" maxlength="150" autocomplete="name" required></div>
          <div><label for="usuario">Usuario</label><input id="usuario" name="usuario" value="<?= htmlspecialchars($valores['usuario']) ?>" minlength="3" maxlength="50" autocomplete="username" required></div>
          <div><label for="correo_institucional">Correo institucional</label><input id="correo_institucional" type="email" name="correo_institucional" value="<?= htmlspecialchars($valores['correo_institucional']) ?>" maxlength="190" autocomplete="email" placeholder="tu.usuario@utrng.edu.mx" required><small class="hint">Debe terminar en @<?= htmlspecialchars(INSTITUTIONAL_EMAIL_DOMAIN) ?></small></div>
          <div><label for="id_departamento">Departamento</label><select id="id_departamento" name="id_departamento" required><option value="">Selecciona tu departamento</option><?php foreach ($departamentos as $d): ?><option value="<?= (int)$d['id_departamento'] ?>" <?= $valores['id_departamento'] == $d['id_departamento'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nombre_departamento']) ?></option><?php endforeach; ?></select></div>
          <div><label for="cargo">Cargo</label><input id="cargo" name="cargo" value="<?= htmlspecialchars($valores['cargo']) ?>" maxlength="100" autocomplete="organization-title"><small class="hint">Opcional</small></div>
          <div><label for="contrasena">Contraseña</label><input id="contrasena" type="password" name="contrasena" minlength="8" autocomplete="new-password" required></div>
          <div><label for="confirmar_contrasena">Confirmar contraseña</label><input id="confirmar_contrasena" type="password" name="confirmar_contrasena" minlength="8" autocomplete="new-password" required></div>
        </div>
        <div class="actions"><button class="btn" type="submit">Enviar solicitud</button><a class="btn secondary" href="login.php">Cancelar</a></div>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
