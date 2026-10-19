<?php
/**
 * Utilidades generales, conexión a la base de datos y
 * helpers para los endpoints de Ajax.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api.php';

/* ================= Utilidades ================= */

/** Escapa un texto antes de imprimirlo dentro del HTML. */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Redirige a otra página y detiene la ejecución. */
function redirigir($ruta)
{
    header('Location: ' . $ruta);
    exit;
}

/** Responde a una petición Ajax con JSON y termina. */
function responder_json($datos)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Consulta una URL externa con cURL y devuelve el JSON decodificado (o null). */
function consultar_api($url, $encabezados = [])
{
    $encabezadosBase = [
        'Accept: application/json',
        'User-Agent: EncuentraME/1.0 (aplicacion academica)',
    ];

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => array_merge($encabezadosBase, $encabezados),
    ]);
    $respuesta = curl_exec($curl);
    curl_close($curl);

    if ($respuesta === false) {
        return null;
    }
    $datos = json_decode($respuesta, true);
    return is_array($datos) ? $datos : null;
}

/* ================= Conexión ================= */

/** Abre la conexión con MySQL (sin seleccionar base de datos todavía). */
function conectar()
{
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, '', (int) DB_PORT);
    if ($conexion->connect_errno) {
        die(
            'No se pudo conectar con MySQL. Asegúrate de que Apache y MySQL estén iniciados en el panel de XAMPP.'
            . '<br>Detalle: ' . $conexion->connect_error
        );
    }
    $conexion->set_charset('utf8mb4');
    return $conexion;
}

/* ================= Instalación automática ================= */

/**
 * Crea la base de datos y la tabla si no existen.
 * Si la tabla queda vacía, inserta los datos de ejemplo.
 * Devuelve la conexión ya apuntando a la base del proyecto.
 */
function instalar_base()
{
    $conexion = conectar();
    $conexion->query(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NOMBRE . '` '
        . 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
    );
    $conexion->select_db(DB_NOMBRE);

    $conexion->query(
        'CREATE TABLE IF NOT EXISTS personas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dni VARCHAR(12) NOT NULL DEFAULT "",
            nombres VARCHAR(150) NOT NULL,
            alias VARCHAR(60) NOT NULL DEFAULT "",
            redes VARCHAR(255) NOT NULL DEFAULT "",
            estado VARCHAR(20) NOT NULL DEFAULT "por revisar",
            notas VARCHAR(600) NOT NULL DEFAULT "",
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $consulta = $conexion->query('SELECT COUNT(*) AS total FROM personas');
    $fila = $consulta->fetch_assoc();
    if ((int) $fila['total'] === 0) {
        sembrar_personas($conexion);
    }

    return $conexion;
}

/** Inserta los expedientes de ejemplo la primera vez que se usa el sistema. */
function sembrar_personas($conexion)
{
    $ejemplos = [
        ['45678901', 'Ríos Quispe María Elena', 'mariel.rios', 'Instagram, TikTok', 'por revisar', 'Cuenta principal de la feria de ciencias.'],
        ['41234567', 'Torres Gálvez Luis Fernando', 'luisfg_dev', 'GitHub, Dev.to', 'revisado', 'Expone el proyecto de redes en GitHub.'],
        ['70123456', 'Medina Castillo Ana Lucía', 'ana.medina', 'Instagram, YouTube', 'por revisar', 'Videoblog de la biblioteca.'],
        ['46789012', 'Salazar Ruiz Diego', 'diegosz', 'X, Reddit', 'descartado', 'Cuenta sin actividad reciente.'],
        ['43567890', 'Quispe Mamani Roxana', 'roxanaq', 'Facebook, TikTok', 'por revisar', 'Organiza eventos del colegio.'],
        ['72345678', 'Vargas León Cristian', 'cvargas', 'GitHub, Stack Overflow', 'revisado', 'Aporta al repositorio del curso.'],
        ['44890123', 'Flores Núñez Gabriela', 'gabyflores', 'Instagram', 'por revisar', 'Consulta sobre horarios de atención.'],
        ['41765432', 'Paredes Soto Renzo', 'renzo.p', 'Twitch, YouTube', 'por revisar', 'Transmisiones en vivo de videojuegos.'],
        ['48901234', 'Aguilar Fernández Karla', 'karla.ag', 'Dev.to, GitHub', 'revisado', 'Portafolio de diseño web.'],
        ['40234567', 'Chávez Mendoza Iván', 'ivanch', 'Reddit, Hacker News', 'por revisar', 'Comenta noticias de tecnología.'],
        ['71456789', 'Espinoza Ramos Teresa', 'tespi', 'Facebook', 'por revisar', 'Contacto de la asociación de padres.'],
        ['45987654', 'Zamudio Cruz Patricio', 'pzamudio', 'X, Instagram', 'descartado', 'Perfil duplicado.'],
        ['42345678', 'Ramos Ortiz Sergio', 'sramos', 'GitHub', 'revisado', 'Copia del proyecto de la clase.'],
        ['49876543', 'Herrera Soto Mónica', 'monicah', 'TikTok, Instagram', 'por revisar', 'Campaña de reciclaje.'],
        ['47654321', 'Núñez Prado Andrés', 'anprado', 'Stack Overflow, GitHub', 'revisado', 'Resuelve dudas de PHP.'],
        ['46543218', 'Loayza Ponce Camila', 'camiloayza', 'YouTube, Twitch', 'por revisar', 'Canal de divulgación científica.'],
    ];

    $sentencia = $conexion->prepare(
        'INSERT INTO personas (dni, nombres, alias, redes, estado, notas)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($ejemplos as $p) {
        $sentencia->bind_param('ssssss', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
        $sentencia->execute();
    }
    $sentencia->close();
}
