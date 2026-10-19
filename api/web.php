<?php
/**
 * Endpoint de Ajax: busca huella en la web con Wikipedia (español)
 * y DuckDuckGo, y devuelve enlaces directos a buscadores externos.
 * Uso: api/web.php?texto=María Elena Ríos
 */
require_once __DIR__ . '/../config/base.php';

$texto = trim($_GET['texto'] ?? '');
if ($texto === '' || mb_strlen($texto) > 120) {
    responder_json(['ok' => false, 'mensaje' => 'Escribe un texto de 1 a 120 caracteres.']);
}

$resultados = [];

/* ---------- Wikipedia en español ---------- */
$wiki = consultar_api(
    'https://es.wikipedia.org/w/api.php?action=query&list=search&srsearch='
    . rawurlencode($texto) . '&srlimit=4&format=json&utf8=1'
);
if ($wiki && !empty($wiki['query']['search'])) {
    foreach ($wiki['query']['search'] as $item) {
        $resultados[] = [
            'fuente' => 'Wikipedia',
            'titulo' => $item['title'],
            'resumen' => trim(preg_replace('/<[^>]+>/', '', $item['snippet'])),
            'url' => 'https://es.wikipedia.org/wiki/' . str_replace(' ', '_', $item['title']),
        ];
    }
}

/* ---------- DuckDuckGo (respuesta instantánea) ---------- */
$ddg = consultar_api(
    'https://api.duckduckgo.com/?q=' . rawurlencode($texto)
    . '&format=json&no_html=1&skip_disambig=1'
);
if ($ddg && !empty($ddg['AbstractText'])) {
    $resultados[] = [
        'fuente' => 'DuckDuckGo',
        'titulo' => ($ddg['Heading'] ?? '') !== '' ? $ddg['Heading'] : $texto,
        'resumen' => mb_substr($ddg['AbstractText'], 0, 260),
        'url' => ($ddg['AbstractURL'] ?? '') !== ''
            ? $ddg['AbstractURL']
            : 'https://duckduckgo.com/?q=' . rawurlencode($texto),
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
                'titulo' => mb_substr($tema['Text'], 0, 70),
                'resumen' => $tema['Text'],
                'url' => $tema['FirstURL'],
            ];
            $agregados++;
        }
    }
}

responder_json([
    'ok' => true,
    'resultados' => $resultados,
    'enlaces' => [
        ['fuente' => 'Google', 'url' => 'https://www.google.com/search?q=' . rawurlencode('"' . $texto . '"')],
        ['fuente' => 'DuckDuckGo', 'url' => 'https://duckduckgo.com/?q=' . rawurlencode($texto)],
        ['fuente' => 'Bing', 'url' => 'https://www.bing.com/search?q=' . rawurlencode($texto)],
    ],
]);
