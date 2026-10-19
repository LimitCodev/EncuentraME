<?php
/**
 * Endpoint de Ajax: consulta de RUC con la API de ApisPeru.
 * Uso: api/ruc.php?ruc=20123456789
 *
 * Mismo token que el DNI (config/api.local.php). Sin token responde
 * en modo demostración indicando cómo configurarlo.
 */
require_once __DIR__ . '/../config/base.php';

$ruc = trim($_GET['ruc'] ?? '');
if (!preg_match('/^\d{11}$/', $ruc)) {
    responder_json(['ok' => false, 'mensaje' => 'El RUC debe tener exactamente 11 dígitos.']);
}

/* ---------- Con token: consulta real a ApisPeru ---------- */
if (APISPERU_TOKEN !== '') {
    $url = 'https://dniruc.apisperu.com/api/v1/ruc/' . $ruc
        . '?token=' . urlencode(APISPERU_TOKEN);
    $datos = consultar_api($url);

    if ($datos && ($datos['razonSocial'] ?? '') !== '') {
        $ubigeo = trim(
            ($datos['distrito'] ?? '') . ', ' . ($datos['provincia'] ?? '')
            . ', ' . ($datos['departamento'] ?? ''),
            ' ,'
        );
        responder_json([
            'ok' => true,
            'modo' => 'api',
            'ruc' => $datos['ruc'] ?? $ruc,
            'razon_social' => $datos['razonSocial'],
            'estado' => $datos['estado'] ?? '',
            'condicion' => $datos['condicion'] ?? '',
            'direccion' => $datos['direccion'] ?? '',
            'ubigeo' => $ubigeo !== ', , ' ? $ubigeo : '',
            'mensaje' => '',
        ]);
    }

    $mensaje = 'La API no devolvió datos para ese RUC.';
    if (is_array($datos) && !empty($datos['message'])) {
        $mensaje = (string) $datos['message'];
    }
    responder_json(['ok' => false, 'modo' => 'api', 'mensaje' => $mensaje]);
}

/* ---------- Sin token: modo demostración ---------- */
responder_json([
    'ok' => false,
    'modo' => 'demo',
    'mensaje' => 'Modo demostración: configura el token de ApisPeru en '
        . 'config/api.local.php para consultar RUC reales.',
]);
