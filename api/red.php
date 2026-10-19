<?php
/**
 * Endpoint de Ajax: verifica si existe un usuario público en una red social.
 * Uso: api/red.php?red=github&usuario=torvalds
 */
require_once __DIR__ . '/../config/base.php';

$red = strtolower(trim($_GET['red'] ?? ''));
$usuario = ltrim(trim($_GET['usuario'] ?? ''), '@');

$etiquetas = [
    'github' => 'GitHub',
    'devto' => 'Dev.to',
    'stackoverflow' => 'Stack Overflow',
    'dailymotion' => 'Dailymotion',
    'hackernews' => 'Hacker News',
    'reddit' => 'Reddit',
];

if (!isset($etiquetas[$red])) {
    responder_json(['red' => $red, 'existe' => false, 'mensaje' => 'Red no disponible.']);
}
if (!preg_match('/^[A-Za-z0-9._-]{1,40}$/', $usuario)) {
    responder_json([
        'red' => $red,
        'etiqueta' => $etiquetas[$red],
        'existe' => false,
        'mensaje' => 'Nombre de usuario inválido.',
    ]);
}

$respuesta = [
    'red' => $red,
    'etiqueta' => $etiquetas[$red],
    'existe' => false,
    'nombre' => '',
    'detalle' => '',
    'url' => '',
    'avatar' => '',
    'mensaje' => '',
];

/* ---------- Caché en MySQL (1 h): GitHub solo admite 60 consultas por
   hora sin clave, y una demostración completa hace varias por búsqueda. ---------- */
$usuarioCache = mb_strtolower($usuario);
$conexion = null;
try {
    $conexion = instalar_base();
} catch (Throwable $e) {
    $conexion = null; // sin base de datos seguimos, solo sin caché
}
if ($conexion instanceof mysqli) {
    /* La edad se calcula en SQL: MySQL y PHP pueden tener husos distintos. */
    $sentencia = $conexion->prepare(
        'SELECT datos, TIMESTAMPDIFF(SECOND, creado_en, NOW()) AS edad
         FROM cache_redes WHERE red = ? AND usuario = ?'
    );
    $sentencia->bind_param('ss', $red, $usuarioCache);
    $sentencia->execute();
    $fila = $sentencia->get_result()->fetch_assoc();
    $sentencia->close();
    if ($fila && (int) $fila['edad'] < 3600) {
        $cache = json_decode($fila['datos'], true);
        if (is_array($cache)) {
            $cache['de_cache'] = true;
            responder_json($cache);
        }
    }
}

switch ($red) {
    case 'github':
        $datos = consultar_api('https://api.github.com/users/' . rawurlencode($usuario));
        if ($datos === null) {
            $respuesta['mensaje'] = 'La API no respondió, vuelve a intentarlo.';
        } elseif (isset($datos['login'])) {
            $respuesta['existe'] = true;
            $respuesta['nombre'] = $datos['name'] ?: $datos['login'];
            $respuesta['detalle'] = number_format($datos['followers']) . ' seguidores'
                . (!empty($datos['bio']) ? ' · ' . mb_substr($datos['bio'], 0, 70) : '');
            $respuesta['url'] = $datos['html_url'];
            $respuesta['avatar'] = $datos['avatar_url'];
        } elseif (isset($datos['message']) && stripos($datos['message'], 'rate limit') !== false) {
            $respuesta['mensaje'] = 'GitHub está en su límite de 60 consultas por hora; espera unos minutos.';
        } else {
            $respuesta['mensaje'] = 'No se encontró una cuenta pública con ese usuario.';
        }
        break;

    case 'devto':
        $datos = consultar_api('https://dev.to/api/users/by_username?url=' . rawurlencode($usuario));
        if ($datos === null) {
            $respuesta['mensaje'] = 'La API no respondió, vuelve a intentarlo.';
        } elseif (isset($datos['username'])) {
            $respuesta['existe'] = true;
            $respuesta['nombre'] = $datos['name'] ?: $datos['username'];
            $respuesta['detalle'] = !empty($datos['summary'])
                ? mb_substr($datos['summary'], 0, 90)
                : ($datos['location'] ?? 'Perfil público en Dev.to');
            $respuesta['url'] = 'https://dev.to/' . $datos['username'];
            $respuesta['avatar'] = $datos['profile_image'] ?? '';
        } else {
            $respuesta['mensaje'] = 'No se encontró una cuenta pública con ese usuario.';
        }
        break;

    case 'stackoverflow':
        $datos = consultar_api(
            'https://api.stackexchange.com/2.3/users?order=desc&sort=reputation&inname='
            . rawurlencode($usuario) . '&site=stackoverflow'
        );
        if ($datos === null) {
            $respuesta['mensaje'] = 'La API no respondió, vuelve a intentarlo.';
        } elseif (!empty($datos['items'])) {
            $coincidencia = null;
            foreach ($datos['items'] as $item) {
                $nombre = strtolower($item['display_name']);
                if ($nombre === strtolower($usuario) || stripos($item['link'], '/' . $usuario) !== false) {
                    $coincidencia = $item;
                    break;
                }
            }
            if ($coincidencia) {
                $respuesta['existe'] = true;
                $respuesta['nombre'] = $coincidencia['display_name'];
                $respuesta['detalle'] = number_format($coincidencia['reputation']) . ' puntos de reputación';
                $respuesta['url'] = $coincidencia['link'];
                $respuesta['avatar'] = $coincidencia['profile_image'] ?? '';
            } else {
                $respuesta['mensaje'] = 'No se encontró una cuenta con ese nombre.';
            }
        } else {
            $respuesta['mensaje'] = 'No se encontró una cuenta con ese nombre.';
        }
        break;

    case 'dailymotion':
        $datos = consultar_api(
            'https://api.dailymotion.com/user/' . rawurlencode($usuario)
            . '?fields=id,screenname,description,avatar_720_url,followers_total'
        );
        if ($datos === null) {
            $respuesta['mensaje'] = 'La API no respondió, vuelve a intentarlo.';
        } elseif (isset($datos['id'])) {
            $respuesta['existe'] = true;
            $respuesta['nombre'] = $datos['screenname'] ?? $usuario;
            $respuesta['detalle'] = number_format($datos['followers_total'] ?? 0) . ' seguidores'
                . (!empty($datos['description']) ? ' · ' . mb_substr($datos['description'], 0, 60) : '');
            $respuesta['url'] = 'https://www.dailymotion.com/' . $usuario;
            $respuesta['avatar'] = $datos['avatar_720_url'] ?? '';
        } else {
            $respuesta['mensaje'] = 'No se encontró una cuenta pública con ese usuario.';
        }
        break;

    case 'hackernews':
        $datos = consultar_api('https://hn.algolia.com/api/v1/users/' . rawurlencode($usuario));
        if ($datos === null) {
            $respuesta['mensaje'] = 'La API no respondió, vuelve a intentarlo.';
        } elseif (isset($datos['username'])) {
            $respuesta['existe'] = true;
            $respuesta['nombre'] = $datos['username'];
            $respuesta['detalle'] = number_format($datos['karma'] ?? 0) . ' puntos de karma';
            $respuesta['url'] = 'https://news.ycombinator.com/user?id=' . rawurlencode($datos['username']);
        } else {
            $respuesta['mensaje'] = 'No se encontró una cuenta pública con ese usuario.';
        }
        break;

    case 'reddit':
        $datos = consultar_api(
            'https://www.reddit.com/user/' . rawurlencode($usuario) . '/about.json',
            ['User-Agent: Mozilla/5.0 EncuentraME/1.0']
        );
        $perfil = $datos['data']['children']['data'] ?? null;
        if ($perfil && isset($perfil['name'])) {
            $respuesta['existe'] = true;
            $respuesta['nombre'] = $perfil['name'];
            $respuesta['detalle'] = number_format($perfil['total_karma'] ?? 0) . ' de karma total';
            $respuesta['url'] = 'https://www.reddit.com/user/' . $perfil['name'] . '/';
        } else {
            $respuesta['mensaje'] = 'Reddit no confirmó la cuenta; prueba el enlace directo.';
        }
        break;
}

/* ---------- Guardar en caché (solo respuestas definitivas, no fallos de red) ---------- */
if ($conexion instanceof mysqli && strpos($respuesta['mensaje'], 'no respondió') === false) {
    $json = json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    $sentencia = $conexion->prepare(
        'INSERT INTO cache_redes (red, usuario, datos) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE datos = VALUES(datos), creado_en = CURRENT_TIMESTAMP'
    );
    $sentencia->bind_param('sss', $red, $usuarioCache, $json);
    $sentencia->execute();
    $sentencia->close();
}

responder_json($respuesta);
