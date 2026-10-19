<?php
/**
 * Procesa las acciones del CRUD de expedientes:
 * guardar (crear o editar), eliminar y guardar por Ajax.
 */
require_once 'config/base.php';
$conexion = instalar_base();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('personas.php');
}

$redesPermitidas = [
    'GitHub', 'Dev.to', 'Stack Overflow', 'Dailymotion', 'Hacker News', 'Reddit',
    'Instagram', 'TikTok', 'X', 'YouTube', 'Facebook', 'Twitch', 'Pinterest',
];
$estadosPermitidos = ['por revisar', 'revisado', 'descartado'];

/** Valida y arma los datos recibidos del formulario. */
function datos_del_formulario($redesPermitidas, $estadosPermitidos)
{
    $nombres = trim($_POST['nombres'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $alias = trim($_POST['alias'] ?? '');
    $estado = $_POST['estado'] ?? 'por revisar';
    $notas = trim($_POST['notas'] ?? '');

    $elegidas = is_array($_POST['redes'] ?? null) ? $_POST['redes'] : [];
    $elegidas = array_map('trim', $elegidas);
    $redes = implode(', ', array_values(array_filter(
        $elegidas,
        function ($red) use ($redesPermitidas) {
            return in_array($red, $redesPermitidas, true);
        }
    )));

    if (!in_array($estado, $estadosPermitidos, true)) {
        $estado = 'por revisar';
    }

    return [
        'nombres' => $nombres,
        'dni' => $dni,
        'alias' => $alias,
        'estado' => $estado,
        'notas' => $notas,
        'redes' => $redes,
        'valido' => $nombres !== '',
    ];
}

$accion = $_POST['accion'] ?? '';

/* ---------- Guardar por Ajax (desde el buscador de rastreo) ---------- */
if ($accion === 'guardar_ajax') {
    $datos = datos_del_formulario($redesPermitidas, $estadosPermitidos);
    if (!$datos['valido']) {
        responder_json(['ok' => false, 'mensaje' => 'Faltan los nombres de la persona.']);
    }
    $sentencia = $conexion->prepare(
        'INSERT INTO personas (dni, nombres, alias, redes, estado, notas) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $sentencia->bind_param(
        'ssssss',
        $datos['dni'],
        $datos['nombres'],
        $datos['alias'],
        $datos['redes'],
        $datos['estado'],
        $datos['notas']
    );
    $sentencia->execute();
    responder_json(['ok' => true, 'id' => $conexion->insert_id, 'mensaje' => 'Guardado en expedientes.']);
}

/* ---------- Guardar (crear o editar) ---------- */
if ($accion === 'guardar') {
    $datos = datos_del_formulario($redesPermitidas, $estadosPermitidos);
    $id = (int) ($_POST['id'] ?? 0);

    if (!$datos['valido']) {
        redirigir('persona_form.php?error=1' . ($id > 0 ? '&id=' . $id : ''));
    }

    if ($id > 0) {
        $sentencia = $conexion->prepare(
            'UPDATE personas SET dni = ?, nombres = ?, alias = ?, redes = ?, estado = ?, notas = ? WHERE id = ?'
        );
        $sentencia->bind_param(
            'ssssssi',
            $datos['dni'],
            $datos['nombres'],
            $datos['alias'],
            $datos['redes'],
            $datos['estado'],
            $datos['notas'],
            $id
        );
    } else {
        $sentencia = $conexion->prepare(
            'INSERT INTO personas (dni, nombres, alias, redes, estado, notas) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $sentencia->bind_param(
            'ssssss',
            $datos['dni'],
            $datos['nombres'],
            $datos['alias'],
            $datos['redes'],
            $datos['estado'],
            $datos['notas']
        );
    }

    $sentencia->execute();
    redirigir('personas.php?ok=' . ($id > 0 ? 'actualizado' : 'guardado'));
}

/* ---------- Eliminar ---------- */
if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $sentencia = $conexion->prepare('DELETE FROM personas WHERE id = ?');
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
    }
    redirigir('personas.php?ok=eliminado');
}

redirigir('personas.php');
