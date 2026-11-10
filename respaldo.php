<?php
require_once 'config.php';
require_once 'Database.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    header("Location: login.php");
    exit;
}

$error = "";

if (isset($_GET['generar'])) {
    $fecha = date('Ymd_His');
    $nombreArchivo = "respaldo_" . DB_NAME . "_" . $fecha . ".sql";

    $comando = "mysqldump -h " . escapeshellarg(DB_HOST) . " -u " . escapeshellarg(DB_USER);
    if (DB_PASS !== '') {
        $comando .= " -p" . escapeshellarg(DB_PASS);
    }
    $comando .= " " . escapeshellarg(DB_NAME) . " > " . escapeshellarg($nombreArchivo);

    exec($comando, $output, $return_var);

    if ($return_var === 0 && file_exists($nombreArchivo)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($nombreArchivo) . '"');
        header('Content-Length: ' . filesize($nombreArchivo));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($nombreArchivo);
        unlink($nombreArchivo);
        exit;
    } else {
        $error = "Error al generar el respaldo. Asegúrate de tener mysqldump instalado y accesible desde el servidor.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Respaldo - <?= APP_NAME ?></title>
<style>
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; padding: 40px; }
  .card { background: #fff; padding: 24px; border: 1px solid #ddd; border-radius: 6px; max-width: 560px; margin: 0 auto; }
  h2 { color: #1b5e20; margin-top: 0; }
  .btn { background: #1b5e20; color: #fff; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
  .btn:hover { background: #33691e; }
  .btn-secondary { background: #555; margin-left: 10px; }
  .btn-secondary:hover { background: #444; }
  .alert { padding: 11px 15px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
  .alert-success { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
  .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
  .hint { font-size: 13px; color: #666; margin-top: 10px; }
</style>
</head>
<body>
  <div class="card">
    <h2>Respaldo de Base de Datos</h2>
    <p>Base de datos: <strong><?= DB_NAME ?></strong></p>
    <p>Generará un archivo <code>.sql</code> con la estructura y datos actuales.</p>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <a href="?generar=1" class="btn">Generar Respaldo SQL</a>
    <a href="index.php" class="btn btn-secondary">Volver al Dashboard</a>

    <p class="hint">Nota: Si no se descarga nada, verifica que <b>mysqldump</b> esté instalado en el servidor (XAMPP/WAMP lo incluye).</p>
  </div>
</body>
</html>
