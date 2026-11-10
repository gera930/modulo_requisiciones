<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
        $_SESSION['error'] = "Acceso denegado: solo el administrador puede ver esta sección.";
        header("Location: index.php");
        exit;
    }
}

function esAdmin() {
    return isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
}