<?php
require_once 'config/base.php';
$conexion = instalar_base();

/* ================= Búsqueda y paginación ================= */
$busqueda = trim($_GET['q'] ?? '');
$pagina = (int) ($_GET['pagina'] ?? 1);
$porPagina = 8;

/* 1) Contar registros (con o sin búsqueda) */
if ($busqueda === '') {
    $consulta = $conexion->query('SELECT COUNT(*) AS total FROM personas');
    $fila = $consulta->fetch_assoc();
    $totalPersonas = (int) $fila['total'];
} else {
    $como = '%' . $busqueda . '%';
    $sentencia = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM personas WHERE nombres LIKE ? OR alias LIKE ? OR dni LIKE ?'
    );
    $sentencia->bind_param('sss', $como, $como, $como);
    $sentencia->execute();
    $fila = $sentencia->get_result()->fetch_assoc();
    $totalPersonas = (int) $fila['total'];
}

/* 2) Calcular la página actual y el desplazamiento */
$totalPaginas = max(1, (int) ceil($totalPersonas / $porPagina));
if ($pagina < 1) {
    $pagina = 1;
}
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$desde = ($pagina - 1) * $porPagina;

/* 3) Traer solo la página solicitada */
if ($busqueda === '') {
    $sentencia = $conexion->prepare(
        "SELECT * FROM personas ORDER BY id DESC LIMIT $desde, $porPagina"
    );
    $sentencia->execute();
} else {
    $sentencia = $conexion->prepare(
        "SELECT * FROM personas WHERE nombres LIKE ? OR alias LIKE ? OR dni LIKE ?
         ORDER BY id DESC LIMIT $desde, $porPagina"
    );
    $sentencia->bind_param('sss', $como, $como, $como);
    $sentencia->execute();
}
$personas = [];
$resultado = $sentencia->get_result();
while ($fila = $resultado->fetch_assoc()) {
    $personas[] = $fila;
}

/* Genera el enlace de una página conservando la búsqueda */
function enlace_pagina($n)
{
    global $busqueda;
    $parametros = ['pagina' => $n];
    if ($busqueda !== '') {
        $parametros['q'] = $busqueda;
    }
    return 'personas.php?' . http_build_query($parametros);
}

$mensaje = $_GET['ok'] ?? '';
$titulo_pagina = 'Expedientes';
include 'plantilla/cabecera.php';
?>
  <section class="titulo-seccion">
    <div>
      <h1>Expedientes de personas</h1>
      <p class="subtitulo">
        <?php if ($busqueda !== ''): ?>
          <?= $totalPersonas ?> resultado(s) para «<?= e($busqueda) ?>»
        <?php else: ?>
          <?= $totalPersonas ?> registro(s) guardado(s)
        <?php endif; ?>
      </p>
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

  <!-- Buscador: filtra por nombres, alias o DNI -->
  <form class="buscador" method="get" action="personas.php">
    <input class="buscador-entrada" type="search" name="q" maxlength="80"
           placeholder="Buscar por nombres, alias o DNI…" value="<?= e($busqueda) ?>">
    <button class="boton boton-primario" type="submit">Buscar</button>
    <?php if ($busqueda !== ''): ?>
      <a class="boton boton-secundario" href="personas.php">Limpiar</a>
    <?php endif; ?>
  </form>

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
            <td colspan="6" class="tabla-vacia">
              <?php if ($busqueda !== ''): ?>
                No se encontraron expedientes para «<?= e($busqueda) ?>».
              <?php else: ?>
                Aún no hay expedientes registrados.
              <?php endif; ?>
            </td>
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

  <!-- Paginador -->
  <?php if ($totalPersonas > 0 && $totalPaginas > 1): ?>
    <nav class="paginador" aria-label="Paginación de resultados">
      <?php if ($pagina > 1): ?>
        <a class="paginador-enlace" href="<?= enlace_pagina($pagina - 1) ?>">← Anterior</a>
      <?php endif; ?>

      <?php for ($n = 1; $n <= $totalPaginas; $n++): ?>
        <?php if ($n === $pagina): ?>
          <span class="paginador-enlace paginador-activo"><?= $n ?></span>
        <?php else: ?>
          <a class="paginador-enlace" href="<?= enlace_pagina($n) ?>"><?= $n ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($pagina < $totalPaginas): ?>
        <a class="paginador-enlace" href="<?= enlace_pagina($pagina + 1) ?>">Siguiente →</a>
      <?php endif; ?>
    </nav>
    <p class="paginador-info">
      Mostrando <?= $desde + 1 ?>–<?= $desde + count($personas) ?> de <?= $totalPersonas ?> expedientes
    </p>
  <?php endif; ?>
<?php include 'plantilla/pie.php';
