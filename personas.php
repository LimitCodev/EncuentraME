<?php
require_once 'config/base.php';
$conexion = instalar_base();

$resultado = $conexion->query('SELECT * FROM personas ORDER BY id DESC');
$personas = [];
while ($fila = $resultado->fetch_assoc()) {
    $personas[] = $fila;
}

$mensaje = $_GET['ok'] ?? '';
$titulo_pagina = 'Expedientes';
include 'plantilla/cabecera.php';
?>
  <section class="titulo-seccion">
    <div>
      <h1>Expedientes de personas</h1>
      <p class="subtitulo">Registros guardados: <?= count($personas) ?></p>
    </div>
    <a class="boton boton-primario" href="persona_form.php">+ Nuevo expediente</a>
  </section>

  <?php if ($mensaje === 'guardado'): ?>
    <div class="aviso aviso-correcto">Expediente registrado correctamente.</div>
  <?php elseif ($mensaje === 'actualizado'): ?>
    <div class="aviso aviso-correcto">Expediente actualizado correctamente.</div>
  <?php elseif ($mensaje === 'eliminado'): ?>
    <div class="aviso aviso-correcto">Expediente eliminado.</div>
  <?php elseif ($mensaje === 'error'): ?>
    <div class="aviso aviso-error">Faltan datos obligatorios. El campo «Nombres» es requerido.</div>
  <?php endif; ?>

  <div class="tabla-contenedor">
    <table class="tabla">
      <thead>
        <tr>
          <th>DNI</th>
          <th>Nombres</th>
          <th>Alias</th>
          <th>Redes</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($personas)): ?>
          <tr>
            <td colspan="6" class="tabla-vacia">Aún no hay expedientes registrados.</td>
          </tr>
        <?php endif; ?>

        <?php foreach ($personas as $p): ?>
          <tr>
            <td data-label="DNI"><?= e($p['dni']) !== '' ? e($p['dni']) : '—' ?></td>
            <td data-label="Nombres"><strong><?= e($p['nombres']) ?></strong></td>
            <td data-label="Alias"><code><?= e($p['alias']) ?></code></td>
            <td data-label="Redes">
              <span class="etiquetas">
                <?php foreach (array_filter(array_map('trim', explode(',', $p['redes']))) as $red): ?>
                  <span class="etiqueta"><?= e($red) ?></span>
                <?php endforeach; ?>
                <?php if (trim($p['redes']) === ''): ?>—<?php endif; ?>
              </span>
            </td>
            <td data-label="Estado">
              <span class="estado estado-<?= str_replace(' ', '-', $p['estado']) ?>"><?= e($p['estado']) ?></span>
            </td>
            <td data-label="Acciones" class="celda-acciones">
              <a class="boton boton-chico" href="persona_form.php?id=<?= (int) $p['id'] ?>">Editar</a>
              <form method="post" action="acciones.php"
                    onsubmit="return confirm('¿Eliminar el expediente de «<?= e($p['nombres']) ?>»?');">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="boton boton-chico boton-peligro" type="submit">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php include 'plantilla/pie.php';
