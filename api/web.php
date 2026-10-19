<?php
/**
 * Endpoint de Ajax: huella en la web con METASEARCH INLINE.
 * Consulta en PARALELO siete fuentes reales (todas sin clave API):
 *   Wikipedia · DuckDuckGo · GitHub (usuario exacto y nombre completo)
 *   Stack Overflow · Dailymotion · Hacker News · Reddit
 * y devuelve todo en una sola respuesta JSON para pintar los resultados
 * en la misma página, sin redirigir al usuario a otros buscadores.
 * Uso: api/web.php?texto=Alex Josue Ynocencio Callirgos
 */
require_once __DIR__ . '/../config/base.php';

$texto = trim($_GET['texto'] ?? '');
if ($texto === '' || mb_strlen($texto) > 120) {
    responder_json(['ok' => false, 'mensaje' => 'Escribe un texto de 1 a 120 caracteres.']);
}

/* ---------- Caché corta en MySQL (15 min) para no agotar los límites de las APIs ---------- */
$hash = sha1(mb_strtolower($texto));
$conexion = null;
try {
    $conexion = instalar_base();
} catch (Throwable $e) {
    $conexion = null; // sin base de datos seguimos, solo sin caché
}
if ($conexion instanceof mysqli) {
    /* La edad se calcula en SQL: MySQL y PHP pueden tener husos distintos
       (la BD en hora local, PHP en UTC) y así nunca falla la comparación. */
    $sentencia = $conexion->prepare(
        'SELECT datos, TIMESTAMPDIFF(SECOND, creado_en, NOW()) AS edad
         FROM cache_busquedas WHERE hash_texto = ?'
    );
    $sentencia->bind_param('s', $hash);
    $sentencia->execute();
    $fila = $sentencia->get_result()->fetch_assoc();
    $sentencia->close();
    if ($fila && (int) $fila['edad'] < 900) {
        $cache = json_decode($fila['datos'], true);
        if (is_array($cache)) {
            $cache['de_cache'] = true;
            responder_json($cache);
        }
    }
}

/* ---------- Descarga EN PARALELO de todas las fuentes ---------- */
function http_json_en_paralelo(array $peticiones)
{
    $multiplex = curl_multi_init();
    $manejadores = [];

    foreach ($peticiones as $clave => $url) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 7,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) EncuentraME/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        curl_multi_add_handle($multiplex, $curl);
        $manejadores[$clave] = $curl;
    }

    do {
        $estado = curl_multi_exec($multiplex, $activos);
        if ($activos) {
            curl_multi_select($multiplex, 0.5);
        }
    } while ($activos && $estado === CURLM_OK);

    $salida = [];
    foreach ($manejadores as $clave => $curl) {
        $bruto = curl_multi_getcontent($curl);
        $salida[$clave] = $bruto ? json_decode($bruto, true) : null;
        curl_multi_remove_handle($multiplex, $curl);
        curl_close($curl);
    }
    curl_multi_close($multiplex);

    return $salida;
}

$q = rawurlencode($texto);
$esUsuario = preg_match('/^[A-Za-z0-9._-]{1,40}$/', $texto) === 1;

$peticiones = [
    'wikipedia' => 'https://es.wikipedia.org/w/api.php?action=query&list=search&srsearch='
        . $q . '&srlimit=3&format=json&utf8=1',
    'duckduckgo' => 'https://api.duckduckgo.com/?q=' . $q . '&format=json&no_html=1&skip_disambig=1',
    'github_busca' => 'https://api.github.com/search/users?q=' . $q . '+in:fullname&per_page=5',
    'stackoverflow' => 'https://api.stackexchange.com/2.3/users?order=desc&sort=reputation&inname='
        . $q . '&site=stackoverflow&pagesize=5',
    'dailymotion' => 'https://api.dailymotion.com/users?search=' . $q
        . '&fields=id,screenname,description,avatar_720_url,followers_total&limit=5',
    'hackernews' => 'https://hn.algolia.com/api/v1/search?query=' . $q . '&hitsPerPage=5',
    'reddit' => 'https://www.reddit.com/search.json?q=' . rawurlencode('"' . $texto . '"')
        . '&limit=5&sort=relevance&raw_json=1',
];
if ($esUsuario) {
    $peticiones['github_exacto'] = 'https://api.github.com/users/' . $q;
}

$datos = http_json_en_paralelo($peticiones);
$resultados = [];

/* ---------- GitHub: usuario exacto ---------- */
if (!empty($datos['github_exacto']['login'])) {
    $u = $datos['github_exacto'];
    $resultados[] = [
        'fuente' => 'GitHub',
        'tipo' => 'perfil',
        'titulo' => $u['login'] . (!empty($u['name']) ? ' — ' . $u['name'] : ''),
        'resumen' => number_format($u['followers']) . ' seguidores · '
            . number_format($u['public_repos']) . ' repositorios'
            . (!empty($u['bio']) ? ' · ' . mb_substr($u['bio'], 0, 120) : ''),
        'url' => $u['html_url'],
    ];
}

/* ---------- GitHub: cuentas cuyo NOMBRE COMPLETO coincide ---------- */
if (!empty($datos['github_busca']['items'])) {
    foreach ($datos['github_busca']['items'] as $item) {
        if (isset($datos['github_exacto']['login'])
            && strcasecmp($item['login'], $datos['github_exacto']['login']) === 0) {
            continue; // ya salió como usuario exacto
        }
        $resultados[] = [
            'fuente' => 'GitHub',
            'tipo' => 'perfil',
            'titulo' => $item['login'],
            'resumen' => 'Cuenta pública cuyo nombre completo coincide con la búsqueda.',
            'url' => $item['html_url'],
        ];
    }
}

/* ---------- Stack Overflow: perfiles por nombre ---------- */
if (!empty($datos['stackoverflow']['items'])) {
    foreach ($datos['stackoverflow']['items'] as $item) {
        $resultados[] = [
            'fuente' => 'Stack Overflow',
            'tipo' => 'perfil',
            'titulo' => $item['display_name'],
            'resumen' => number_format($item['reputation']) . ' puntos de reputación'
                . (!empty($item['location']) ? ' · ' . mb_substr($item['location'], 0, 60) : ''),
            'url' => $item['link'],
        ];
    }
}

/* ---------- Dailymotion: perfiles por búsqueda ---------- */
if (!empty($datos['dailymotion']['list'])) {
    foreach ($datos['dailymotion']['list'] as $item) {
        $resultados[] = [
            'fuente' => 'Dailymotion',
            'tipo' => 'perfil',
            'titulo' => '@' . $item['screenname'],
            'resumen' => number_format($item['followers_total'] ?? 0) . ' seguidores'
                . (!empty($item['description']) ? ' · ' . mb_substr($item['description'], 0, 100) : ''),
            'url' => 'https://www.dailymotion.com/' . $item['id'],
        ];
    }
}

/* ---------- Hacker News: menciones en historias y comentarios ---------- */
if (!empty($datos['hackernews']['hits'])) {
    foreach ($datos['hackernews']['hits'] as $hit) {
        $tituloHit = $hit['title'] ?? ($hit['story_title'] ?? '');
        $cuerpo = trim(preg_replace(
            '/\s+/',
            ' ',
            strip_tags((string) ($hit['story_text'] ?? ($hit['comment_text'] ?? '')))
        ));
        if ($tituloHit === '' && $cuerpo === '') {
            continue;
        }
        $resultados[] = [
            'fuente' => 'Hacker News',
            'tipo' => 'mención',
            'titulo' => $tituloHit !== ''
                ? $tituloHit
                : 'Comentario en "' . ($hit['story_title'] ?? '') . '"',
            'resumen' => $cuerpo !== ''
                ? mb_substr($cuerpo, 0, 160)
                : 'Publicado por ' . ($hit['author'] ?? 'usuario anónimo') . ' en Hacker News.',
            'url' => 'https://news.ycombinator.com/item?id=' . $hit['objectID'],
        ];
    }
}

/* ---------- Reddit: menciones en publicaciones ---------- */
if (!empty($datos['reddit']['data']['children'])) {
    foreach ($datos['reddit']['data']['children'] as $publicacion) {
        $p = $publicacion['data'] ?? [];
        if (empty($p['title'])) {
            continue;
        }
        $resultados[] = [
            'fuente' => 'Reddit',
            'tipo' => 'mención',
            'titulo' => mb_substr($p['title'], 0, 120),
            'resumen' => 'r/' . ($p['subreddit'] ?? '?') . ' · publicado por u/' . ($p['author'] ?? '?'),
            'url' => 'https://www.reddit.com' . ($p['permalink'] ?? '/search/?q=' . $q),
        ];
    }
}

/* ---------- Wikipedia (enciclopedia) ---------- */
if (!empty($datos['wikipedia']['query']['search'])) {
    foreach ($datos['wikipedia']['query']['search'] as $item) {
        $resultados[] = [
            'fuente' => 'Wikipedia',
            'tipo' => 'ficha',
            'titulo' => $item['title'],
            'resumen' => trim(preg_replace('/<[^>]+>/', '', $item['snippet'])),
            'url' => 'https://es.wikipedia.org/wiki/' . str_replace(' ', '_', $item['title']),
        ];
    }
}

/* ---------- DuckDuckGo (respuesta instantánea) ---------- */
$ddg = $datos['duckduckgo'] ?? null;
if ($ddg && !empty($ddg['AbstractText'])) {
    $resultados[] = [
        'fuente' => 'DuckDuckGo',
        'tipo' => 'ficha',
        'titulo' => ($ddg['Heading'] ?? '') !== '' ? $ddg['Heading'] : $texto,
        'resumen' => mb_substr($ddg['AbstractText'], 0, 260),
        'url' => ($ddg['AbstractURL'] ?? '') !== ''
            ? $ddg['AbstractURL']
            : 'https://duckduckgo.com/?q=' . $q,
    ];
} elseif ($ddg && !empty($ddg['RelatedTopics'])) {
    $agregados = 0;
    foreach ($ddg['RelatedTopics'] as $tema) {
        if ($agregados >= 3) {
            break;
        }
        if (isset($tema['Text']) && isset($tema['FirstURL'])) {
            $resultados[] = [
                'fuente' => 'DuckDuckGo',
                'tipo' => 'ficha',
                'titulo' => mb_substr($tema['Text'], 0, 70),
                'resumen' => $tema['Text'],
                'url' => $tema['FirstURL'],
            ];
            $agregados++;
        }
    }
}

/* ---------- Respuesta y guardado en caché ---------- */
$porFuente = [];
foreach ($resultados as $r) {
    $porFuente[$r['fuente']] = ($porFuente[$r['fuente']] ?? 0) + 1;
}

$respuesta = [
    'ok' => true,
    'texto' => $texto,
    'resultados' => $resultados,
    'fuentes' => $porFuente,
    'consultadas' => count($peticiones),
    'enlaces' => [
        ['fuente' => 'Google', 'url' => 'https://www.google.com/search?q=' . rawurlencode('"' . $texto . '"')],
        ['fuente' => 'DuckDuckGo', 'url' => 'https://duckduckgo.com/?q=' . rawurlencode($texto)],
        ['fuente' => 'Bing', 'url' => 'https://www.bing.com/search?q=' . rawurlencode($texto)],
    ],
];

if ($conexion instanceof mysqli) {
    $json = json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    $sentencia = $conexion->prepare(
        'INSERT INTO cache_busquedas (hash_texto, datos) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE datos = VALUES(datos), creado_en = CURRENT_TIMESTAMP'
    );
    $sentencia->bind_param('ss', $hash, $json);
    $sentencia->execute();
    $sentencia->close();
}

responder_json($respuesta);
