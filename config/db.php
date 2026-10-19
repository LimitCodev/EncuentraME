<?php
/**
 * Datos de conexión a la base de datos.
 * Por defecto se usan los valores típicos de XAMPP (root sin contraseña).
 * Si necesitas otros datos, crea el archivo config/db.local.php
 * (está excluido del repositorio con el .gitignore).
 */
if (file_exists(__DIR__ . '/db.local.php')) {
    require __DIR__ . '/db.local.php';
}

defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_PORT') || define('DB_PORT', '3306');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');
defined('DB_NOMBRE') || define('DB_NOMBRE', 'encuentrame');
