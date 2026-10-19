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

    /* El DNI (8 dígitos) o RUC (11 dígitos) es opcional, pero si viene
       debe tener un formato correcto para no ensuciar la base. */
    $dniValido = $dni === '' || preg_match('/^\d{8}$|^\d{11}$/', $dni) === 1;

    return [
        'nombres' => $nombres,
        'dni' => $dni,
        'alias' => $alias,
        'estado' => $estado,
        'notas' => $notas,
        'redes' => $redes,
        'dni_valido' => $dniValido,
        'valido' => $nombres !== '' && $dniValido,
        'error' => $nombres === '' ? 'nombres' : ($dniValido ? '' : 'dni'),
    ];
}

/** Devuelve true si el DNI/RUC ya existe en otro expediente. */
function dni_ya_registrado($conexion, $dni, $excluirId = 0)
{
    if ($dni === '') {
        return 0;
    }
    $sentencia = $conexion->prepare(
        'SELECT id FROM personas WHERE dni = ? AND id <> ? LIMIT 1'
    );
    $excluir = (int) $excluirId;
    $sentencia->bind_param('si', $dni, $excluir);
    $sentencia->execute();
    $fila = $sentencia->get_result()->fetch_assoc();
    return $fila ? (int) $fila['id'] : 0;
}

$accion = $_POST['accion'] ?? '';

/* ---------- Guardar por Ajax (desde el buscador de rastreo) ---------- */
if ($accion === 'guardar_ajax') {
    $datos = datos_del_formulario($redesPermitidas, $estadosPermitidos);
    if ($datos['error'] === 'nombres') {
        responder_json(['ok' => false, 'mensaje' => 'Faltan los nombres de la persona.']);
    }
    if ($datos['error'] === 'dni') {
        responder_json(['ok' => false, 'mensaje' => 'El DNI debe tener 8 dígitos y el RUC 11.']);
    }

    $duplicado = dni_ya_registrado($conexion, $datos['dni']);
    if ($duplicado) {
        responder_json([
            'ok' => false,
            'codigo' => 'duplicado',
            'mensaje' => 'Ese DNI/RUC ya está en el expediente #' . $duplicado . '.',
        ]);
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
        $parametroError = $datos['error'] === 'dni' ? 'error=dni' : 'error=1';
        redirigir('persona_form.php?' . $parametroError . ($id > 0 ? '&id=' . $id : ''));
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
        /* No permitir que el DNI/RUC quede repetido en dos expedientes */
        $duplicado = dni_ya_registrado($conexion, $datos['dni'], $id);
        if ($duplicado) {
            redirigir('persona_form.php?error=duplicado&id=' . $id);
        }
    } else {
        $duplicado = dni_ya_registrado($conexion, $datos['dni']);
        if ($duplicado) {
            redirigir('personas.php?ok=duplicado');
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
