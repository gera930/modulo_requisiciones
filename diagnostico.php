<?php
require_once 'config.php';

echo "<h1>🔍 DIAGNÓSTICO</h1>";

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión a MySQL y BD '".DB_NAME."': EXITOSA<br><br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    echo "<p>Revisa <b>config.php</b> (DB_USER y DB_PASS).</p>";
    exit;
}

echo "<h3>👥 Usuarios en la base de datos:</h3>";
try {
    $stmt = $pdo->query("SELECT id_usuario, usuario, nombre_completo, contrasena FROM usuario ORDER BY id_usuario");
    $usuarios = $stmt->fetchAll();
    if (count($usuarios) > 0) {
        foreach ($usuarios as $u) {
            $coincide = password_verify('123456', $u['contrasena']) ? '✅ SÍ es 123456' : '❌ NO es 123456';
            echo "ID: {$u['id_usuario']} | Usuario: {$u['usuario']} | Nombre: {$u['nombre_completo']} | $coincide<br>";
        }
    } else {
        echo "⚠️ No hay usuarios.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>