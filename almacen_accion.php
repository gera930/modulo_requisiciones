<?php
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'ClaseAlmacen.php';

$db = (new Database())->getConnection();
$alm = new ClaseAlmacen($db);

if (isset($_GET['eliminar'])) {
    try {
        $resultado = $alm->delete((int)$_GET['eliminar']);
        header("Location: almacen.php?msg=" . urlencode($resultado));
        exit;
    } catch (PDOException $e) {
        error_log('Error al retirar material del almacén: ' . $e->getMessage());
        header("Location: almacen.php?msg=delete_error");
        exit;
    }
}
header("Location: almacen.php?msg=invalid_action");
exit;
