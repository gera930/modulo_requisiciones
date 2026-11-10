<?php
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'Requisicion.php';

$db = (new Database())->getConnection();
$req = new Requisicion($db);
$id = (int)($_GET['id'] ?? 0);

if ($req->autorizar($id, $_SESSION['usuario_id'])) {
    $_SESSION['msg'] = "Requisición autorizada correctamente.";
} else {
    $_SESSION['error'] = "No se pudo autorizar.";
}
header("Location: lista_requisiciones.php");
exit;