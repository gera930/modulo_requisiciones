<?php
require_once 'session.php';
requireAdmin();
require_once 'Database.php';
require_once 'Requisicion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lista_requisiciones.php');
    exit;
}

$tokenSesion = $_SESSION['csrf_eliminar_requisicion'] ?? '';
$tokenEnviado = $_POST['csrf_token'] ?? '';
if (!$tokenSesion || !$tokenEnviado || !hash_equals($tokenSesion, $tokenEnviado)) {
    $_SESSION['error'] = 'La solicitud de eliminación venció. Actualiza la página e inténtalo de nuevo.';
    header('Location: lista_requisiciones.php');
    exit;
}

$id = (int)($_POST['id_solicitud'] ?? 0);
if ($id < 1) {
    $_SESSION['error'] = 'No se indicó una requisición válida.';
    header('Location: lista_requisiciones.php');
    exit;
}

try {
    $req = new Requisicion((new Database())->getConnection());
    if ($req->eliminar($id)) $_SESSION['msg'] = 'La requisición se eliminó correctamente.';
    else $_SESSION['error'] = 'No se encontró la requisición seleccionada.';
} catch (PDOException $e) {
    error_log('Error al eliminar requisición: ' . $e->getMessage());
    $_SESSION['error'] = 'No se pudo eliminar la requisición. Inténtalo de nuevo.';
}

header('Location: lista_requisiciones.php');
exit;
