<?php
/**
 * Claves de los servicios externos.
 *
 * Para usar tu token real de ApisPeru (consultas de DNI), crea el archivo
 * config/api.local.php —está excluido del repositorio— con:
 *
 *     <?php
 *     define('APISPERU_TOKEN', 'tu_token_aqui');
 *
 * Sin token, la consulta de DNI funciona en modo demostración con datos
 * ficticios, para poder probar la interfaz.
 */
if (file_exists(__DIR__ . '/api.local.php')) {
    require __DIR__ . '/api.local.php';
}

defined('APISPERU_TOKEN') || define('APISPERU_TOKEN', '');
