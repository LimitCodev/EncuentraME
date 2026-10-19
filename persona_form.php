<?php
require_once 'config/base.php';
$conexion = instalar_base();

$redesDisponibles = [
    'GitHub', 'Dev.to', 'Stack Overflow', 'Dailymotion', 'Hacker News', 'Reddit',
    'Instagram', 'TikTok', 'X', 'YouTube', 'Facebook', 'Twitch', 'Pinterest',
];

$id = (int) ($_GET['id'] ?? 0);
$persona = [
    'dni' => '',
    'nombres' => '',
    'alias' => '',
    'redes' => '',
    'estado' => 'por revisar',
    'notas' => '',
];

if ($id > 0) {
    $sentencia = $conexion->prepare('SELECT * FROM personas WHERE id = ?');
    $sentencia->bind_param('i', $id);
    $sentencia->execute();
    $persona = $sentencia->get_result()->fetch_assoc();
    if (!$persona) {
        redirigir('personas.php?ok=error');
    }
}

$redesMarcadas = trim($persona['redes']) !== ''
    ? array_map('trim', explode(',', $persona['redes']))
    : [];

$hayError = isset($_GET['error']);
$errorTipo = $_GET['error'] ?? '';
$titulo_pagina = $id > 0 ? 'Editar expediente' : 'Nuevo expediente';
$menuActivo = 'expedientes';
include 'plantilla/cabecera.php';
?>
  <section class="titulo-seccion">
    <div>
      <h1><?= $id > 0 ? 'Editar expediente' : 'Nuevo expediente' ?></h1>
      <p class="subtitulo">Registra a la persona y las redes donde fue localizada</p>
    </div>
    <a class="boton boton-secundario" href="personas.php">← Volver a expedientes</a>
  </section>

  <?php if ($errorTipo === 'dni'): ?>
    <div class="aviso aviso-error">El DNI necesita 8 dígitos o el RUC 11 (o déjalo vacío).</div>
  <?php elseif ($errorTipo === 'duplicado'): ?>
    <div class="aviso aviso-error">Ese DNI/RUC ya está registrado en otro expediente.</div>
  <?php elseif ($hayError): ?>
    <div class="aviso aviso-error">El campo «Nombres» es obligatorio.</div>
  <?php endif; ?>

  <form class="formulario" method="post" action="acciones.php">
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="campo">
      <label for="dni">DNI</label>
      <input id="dni" name="dni" type="text" inputmode="numeric" maxlength="12"
             pattern="[0-9]{8,12}" placeholder="8 dígitos" value="<?= e($persona['dni']) ?>">
    </div>

    <div class="campo">
      <label for="nombres">Nombres y apellidos *</label>
      <input id="nombres" name="nombres" type="text" maxlength="150" required
             placeholder="Ej. Ríos Quispe María Elena" value="<?= e($persona['nombres']) ?>">
    </div>

    <div class="campo">
      <label for="alias">Alias o usuario</label>
      <input id="alias" name="alias" type="text" maxlength="60"
             placeholder="Ej. mariel.rios" value="<?= e($persona['alias']) ?>">
    </div>

    <div class="campo">
      <label for="estado">Estado</label>
      <select id="estado" name="estado">
        <?php foreach (['por revisar', 'revisado', 'descartado'] as $opcion): ?>
          <option value="<?= $opcion ?>" <?= $persona['estado'] === $opcion ? 'selected' : '' ?>>
            <?= ucfirst($opcion) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <fieldset class="campo campo-ancho">
      <legend>Redes donde fue localizado</legend>
      <div class="caja-redes">
        <?php foreach ($redesDisponibles as $red): ?>
          <label class="opcion-red">
            <input type="checkbox" name="redes[]" value="<?= e($red) ?>"
                   <?= in_array($red, $redesMarcadas, true) ? 'checked' : '' ?>>
            <?= e($red) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <div class="campo campo-ancho">
      <label for="notas">Notas</label>
      <textarea id="notas" name="notas" rows="4" maxlength="600"
                placeholder="Contexto, motivo de la búsqueda…"><?= e($persona['notas']) ?></textarea>
    </div>

    <div class="acciones-formulario campo-ancho">
      <button class="boton boton-primario" type="submit">Guardar expediente</button>
      <a class="boton boton-secundario" href="personas.php">Cancelar</a>
    </div>
  </form>
<?php include 'plantilla/pie.php';
