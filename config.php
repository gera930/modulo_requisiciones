<?php
ob_start();
define('DB_HOST', 'localhost');
define('DB_NAME', 'modulo_requisicion');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('INSTITUTIONAL_EMAIL_DOMAIN', 'utrng.edu.mx');

define('APP_NAME', 'Módulo de Requisición de Inventario');
define('APP_VERSION', '1.0.0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Mexico_City');
