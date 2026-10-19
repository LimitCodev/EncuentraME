<?php
/**
 * Endpoint de Ajax: consulta de DNI con la API de ApisPeru.
 * Uso: api/dni.php?dni=72865403
 *
 * Si no hay token configurado (config/api.local.php), la consulta
 * responde en modo demostración con datos ficticios.
 */
require_once __DIR__ . '/../config/base.php';

$dni = trim($_GET['dni'] ?? '');
if (!preg_match('/^\d{8}$/', $dni)) {
    responder_json(['ok' => false, 'mensaje' => 'El DNI debe tener exactamente 8 dígitos.']);
}

/* ---------- Con token: consulta real a ApisPeru ---------- */
if (APISPERU_TOKEN !== '') {
    $url = 'https://dniruc.apisperu.com/api/v1/dni/' . $dni
        . '?token=' . urlencode(APISPERU_TOKEN);
    $datos = consultar_api($url);

    if ($datos && isset($datos['nombres'])) {
        responder_json([
            'ok' => true,
            'modo' => 'api',
            'dni' => $datos['dni'] ?? $dni,
            'nombres' => $datos['nombres'],
            'apellido_paterno' => $datos['apellidoPaterno'] ?? '',
            'apellido_materno' => $datos['apellidoMaterno'] ?? '',
            'cod_verifica' => $datos['codVerifica'] ?? '',
            'cod_verifica_letra' => $datos['codVerificaLetra'] ?? '',
            'mensaje' => '',
        ]);
    }

    $mensaje = 'La API no devolvió datos para ese DNI.';
    if ($datos && isset($datos['message'])) {
        $mensaje = $datos['message'];
    } elseif ($datos && isset($datos['mensaje'])) {
        $mensaje = $datos['mensaje'];
    }
    responder_json(['ok' => false, 'modo' => 'api', 'mensaje' => $mensaje]);
}

/* ---------- Sin token: modo demostración ---------- */
$demostracion = [
    '00000001' => ['nombres' => 'JUAN CARLOS', 'apellido_paterno' => 'PEREZ', 'apellido_materno' => 'LOPEZ'],
    '11111111' => ['nombres' => 'MARIA ELENA', 'apellido_paterno' => 'RAMOS', 'apellido_materno' => 'SOTO'],
    '44444444' => ['nombres' => 'LUIS ALBERTO', 'apellido_paterno' => 'GOMEZ', 'apellido_materno' => 'DIAZ'],
];

if (isset($demostracion[$dni])) {
    $persona = $demostracion[$dni];
    responder_json([
        'ok' => true,
        'modo' => 'demo',
        'dni' => $dni,
        'nombres' => $persona['nombres'],
        'apellido_paterno' => $persona['apellido_paterno'],
        'apellido_materno' => $persona['apellido_materno'],
        'cod_verifica' => '000',
        'mensaje' => 'Datos ficticios. Configure APISPERU_TOKEN en config/api.local.php para consultas reales.',
    ]);
}

responder_json([
    'ok' => false,
    'modo' => 'demo',
    'mensaje' => 'Modo demostración: solo los DNI 00000001, 11111111 y 44444444 tienen datos de prueba. '
        . 'Configure el token de ApisPeru para consultar cualquier DNI.',
]);
