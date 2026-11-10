<?php
require_once 'config.php';
require_once 'Database.php';
require_once 'Usuario.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$error = "";
$aviso = ($_GET['registro'] ?? '') === 'pendiente' ? 'Tu solicitud fue recibida. La administración revisará y autorizará tu perfil antes de habilitar el acceso.' : '';
$usuarioIngresado = '';
$db = (new Database())->getConnection();
$usuario = new Usuario($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioIngresado = trim($_POST['usuario'] ?? '');
    if ($usuario->login($usuarioIngresado, $_POST['contrasena'] ?? '')) {
        $_SESSION['usuario_id'] = $usuario->id_usuario;
        $_SESSION['usuario'] = $usuario->usuario;
        $_SESSION['nombre_completo'] = $usuario->nombre_completo;
        $_SESSION['nombre_rol'] = $usuario->nombre_rol;
        $_SESSION['es_admin'] = $usuario->esAdmin();
        $usuario->registrarAcceso($usuario->id_usuario);
        header("Location: index.php");
        exit;
    } else {
        $error = $usuario->estado === 'PENDIENTE'
            ? 'Tu perfil está pendiente de autorización por parte de la administración.'
            : 'El usuario o la contraseña no son correctos. Verifica tus datos e inténtalo de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#124936">
<title>Iniciar sesión - <?= htmlspecialchars(APP_NAME) ?></title>
<style>
  * { box-sizing:border-box; }
  :root { --green:#176b4b; --deep:#103d30; --ink:#172b25; --muted:#65766f; --line:#dce7e1; }
  body { min-height:100vh; margin:0; padding:28px; display:grid; place-items:center; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; color:var(--ink); background:radial-gradient(ellipse at 12% 14%,rgba(54,143,94,.72) 0,transparent 38%),radial-gradient(ellipse at 90% 88%,rgba(32,112,101,.55) 0,transparent 35%),linear-gradient(135deg,#103d30,#0d352a); }
  .login-shell { width:min(920px,100%); min-height:540px; display:grid; grid-template-columns: .92fr 1.08fr; overflow:hidden; border:1px solid rgba(255,255,255,.22); border-radius:24px; background:#eaf5ee; box-shadow:0 24px 70px rgba(5,28,20,.3); }
  .login-brand { position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:space-between; padding:42px; color:#fff; background:linear-gradient(150deg,#1b7955 0%,#12523c 58%,#103d30 100%); }
  .login-brand:before,.login-brand:after { position:absolute; content:""; border-radius:50%; pointer-events:none; }
  .login-brand:before { width:310px;height:310px;right:-130px;top:-100px;background:rgba(255,255,255,.08); }
  .login-brand:after { width:230px;height:230px;left:-100px;bottom:-100px;background:rgba(123,212,169,.12); }
  .brand-content,.brand-footer { position:relative;z-index:1; }
  .brand-logo { display:block;width:154px;height:154px;object-fit:contain;object-position:left center;filter:brightness(0) invert(1); }
  .brand-content h1 { margin:25px 0 12px;font-size:31px;line-height:1.16;letter-spacing:-.5px; }
  .brand-content p { max-width:310px;margin:0;color:rgba(255,255,255,.78);line-height:1.65;font-size:15px; }
  .brand-footer { color:rgba(255,255,255,.66);font-size:12px; }
  .login-form-panel { display:flex;align-items:center;justify-content:center;padding:48px 56px;background:linear-gradient(145deg,#edf7f1,#dceee4); }
  .login-form { width:100%;max-width:360px; }
  .eyebrow { margin-bottom:9px;color:#18815a;font-size:11px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase; }
  h2 { margin:0;color:var(--ink);font-size:29px;letter-spacing:-.5px; }
  .intro { margin:8px 0 28px;color:var(--muted);font-size:14px;line-height:1.55; }
  label { display:block;margin:0 0 7px;color:#354b41;font-size:13px;font-weight:700; }
  .field { margin-bottom:19px; }
  .field input { display:block;width:100%;height:48px;padding:0 14px;border:1px solid var(--line);border-radius:10px;background:#fbfdfc;color:var(--ink);font:inherit;font-size:14px;transition:border-color .15s,box-shadow .15s; }
  .field input:focus { outline:none;border-color:#2a9369;box-shadow:0 0 0 4px rgba(42,147,105,.13);background:#fff; }
  .password-wrap { position:relative; }
  .password-wrap input { padding-right:78px; }
  .toggle-password { position:absolute;right:8px;top:7px;height:34px;padding:0 9px;border:0;border-radius:7px;background:transparent;color:#427361;font:inherit;font-size:12px;font-weight:700;cursor:pointer; }
  .toggle-password:hover { background:#edf6f1;color:#155b40; }
  .submit { width:100%;height:49px;margin-top:3px;border:0;border-radius:10px;background:linear-gradient(110deg,#208258,#176b4b);color:#fff;font:inherit;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 8px 18px rgba(23,107,75,.2);transition:transform .15s,box-shadow .15s; }
  .submit:hover { transform:translateY(-1px);box-shadow:0 11px 22px rgba(23,107,75,.26); }
  .submit:focus-visible,.toggle-password:focus-visible { outline:3px solid #86d3ad;outline-offset:2px; }
  .alert { display:flex;gap:10px;align-items:flex-start;margin:0 0 20px;padding:12px 14px;border:1px solid #f2c6c1;border-radius:10px;background:#fff4f2;color:#a62c22;font-size:13px;line-height:1.45; }
  .alert-mark { flex:0 0 20px;width:20px;height:20px;display:grid;place-items:center;border-radius:50%;background:#f9d9d5;font-weight:800; }
  .notice { margin:0 0 20px;padding:12px 14px;border:1px solid #bbdec9;border-radius:10px;background:#e9f6ee;color:#1d6544;font-size:13px;line-height:1.5; }
  .register-link { display:block;margin-top:19px;text-align:center;color:#176b4b;font-size:13px;font-weight:700;text-decoration:none; }
  .register-link:hover { text-decoration:underline;color:#104c38; }
  @media(max-width:680px) { body{padding:15px}.login-shell{grid-template-columns:1fr;max-width:460px;min-height:0;border-radius:19px}.login-brand{min-height:0;padding:20px 25px}.brand-logo{width:82px;height:82px}.brand-content h1{margin:9px 0 5px;font-size:22px}.brand-content p{font-size:13px}.brand-footer{display:none}.login-form-panel{padding:31px 27px 34px}h2{font-size:26px}.intro{margin-bottom:22px} }
  @media(prefers-reduced-motion:reduce) { *,*:before,*:after{transition:none!important;scroll-behavior:auto!important} }
</style>
</head>
<body>
  <main class="login-shell">
    <section class="login-brand" aria-label="Módulo de requisiciones">
      <div class="brand-content">
        <img class="brand-logo" src="logo_login.png" alt="Logotipo institucional">
        <h1>Requisiciones e inventario</h1>
        <p>Accede al sistema para administrar solicitudes y consultar las existencias de almacén.</p>
      </div>
      <div class="brand-footer">Acceso institucional · <?= date('Y') ?></div>
    </section>
    <section class="login-form-panel">
      <div class="login-form">
        <div class="eyebrow">Bienvenido</div>
        <h2>Iniciar sesión</h2>
        <p class="intro">Ingresa tus datos para continuar al panel de control.</p>
        <?php if ($aviso): ?><div class="notice" role="status"><?= htmlspecialchars($aviso) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert" role="alert"><span class="alert-mark">!</span><span><?= htmlspecialchars($error) ?></span></div><?php endif; ?>
        <form method="POST" autocomplete="on">
          <div class="field">
            <label for="usuario">Usuario</label>
            <input id="usuario" type="text" name="usuario" value="<?= htmlspecialchars($usuarioIngresado) ?>" autocomplete="username" autocapitalize="none" required autofocus>
          </div>
          <div class="field">
            <label for="contrasena">Contraseña</label>
            <div class="password-wrap">
              <input id="contrasena" type="password" name="contrasena" autocomplete="current-password" required>
              <button class="toggle-password" type="button" aria-controls="contrasena" aria-label="Mostrar contraseña" onclick="togglePassword(this)">Mostrar</button>
            </div>
          </div>
          <button class="submit" type="submit">Entrar al sistema</button>
        </form>
        <a class="register-link" href="registro.php">¿No tienes perfil? Crear una solicitud de acceso</a>
      </div>
    </section>
  </main>
<script>
function togglePassword(button) {
  const input = document.getElementById('contrasena');
  const mostrar = input.type === 'password';
  input.type = mostrar ? 'text' : 'password';
  button.textContent = mostrar ? 'Ocultar' : 'Mostrar';
  button.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
}
</script>
</body>
</html>
