<?php
require_once 'config/base.php';
instalar_base();

/* Estado real de la API de identidad (para mostrarlo en la interfaz) */
$hayToken = APISPERU_TOKEN !== '';

$titulo_pagina = 'Rastreo';
$menuActivo = 'rastreo';
include 'plantilla/cabecera.php';
?>
  <!-- Buscador único: los resultados llegan por Ajax, sin recargar la página -->
  <section class="hero">
    <h1>Un solo buscador, varias redes</h1>
    <p class="hero-texto">
      Escribe un nombre de usuario y EncuentraME verifica en paralelo si existe una
      cuenta pública en cada red. También puedes escribir un nombre completo:
      verifica usuarios derivados del nombre y su huella real en la web.
    </p>
    <form class="buscador-grande" id="form-rastreo">
      <input id="entrada-rastreo" type="search" maxlength="60" autocomplete="off"
             placeholder="Ej. torvalds, @mariel.rios o María Elena Ríos">
      <button class="boton boton-primario" type="submit">Rastrear</button>
    </form>
    <p class="buscador-pista">Los resultados se cargan con Ajax, sin recargar la página.</p>
    <div class="chips-ejemplo">
      <span class="chips-etiqueta">Prueba con:</span>
      <button class="chip-ejemplo" type="button" data-rellena="entrada-rastreo" data-valor="torvalds">@torvalds</button>
      <button class="chip-ejemplo" type="button" data-rellena="entrada-rastreo" data-valor="María Elena Ríos">María Elena Ríos</button>
      <button class="chip-ejemplo" type="button" data-rellena="entrada-dni"
              data-valor="<?= $hayToken ? '72865403' : '00000001' ?>">
        <?= $hayToken ? 'DNI 72865403' : 'DNI de prueba' ?>
      </button>
    </div>
    <ul class="hero-stats">
      <li><strong>6</strong> redes verificadas en paralelo</li>
      <li><strong>Wikipedia + DuckDuckGo</strong> huella web real</li>
      <li><strong>RENIEC</strong> vía ApisPeru</li>
    </ul>
  </section>

  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Perfiles verificados</h2>
      <div class="bloque-acciones">
        <span class="cuenta-verificadas" id="cuenta-verificadas"></span>
        <button class="boton boton-secundario boton-chico" id="btn-guardar-hallazgo"
                type="button" hidden>Guardar hallazgo en expediente</button>
      </div>
    </div>
    <div class="rejilla-perfiles" id="caja-perfiles">
      <p class="vacio">Escribe un usuario y toca «Rastrear» para verificar las redes.</p>
    </div>
  </section>

  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Resultados en la web (metasearch)</h2>
      <span class="cuenta-verificadas" id="cuenta-web"></span>
    </div>
    <div class="lista-web" id="caja-web">
      <p class="vacio">Busca un usuario o un nombre y aquí aparecerán los resultados de GitHub,
        Stack Overflow, Dailymotion, Hacker News, Reddit, Wikipedia y DuckDuckGo — todo en esta misma página.</p>
    </div>
  </section>

  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Otras redes (enlace directo)</h2>
    </div>
    <div class="caja-enlaces" id="caja-enlaces">
      <p class="vacio">Con un usuario te doy sus enlaces directos; con un nombre completo,
        la búsqueda dentro de cada red.</p>
    </div>
  </section>

  <section class="bloque bloque-dni">
    <div class="bloque-titulo">
      <h2>Consulta de identidad (DNI / RUC)</h2>
      <span class="nota-api<?= $hayToken ? ' nota-api-activa' : '' ?>">
        <?= $hayToken ? 'ApisPeru · RENIEC ✓ datos reales' : 'ApisPeru · modo demostración' ?>
      </span>
    </div>
    <form class="buscador" id="form-dni">
      <input class="buscador-entrada" id="entrada-dni" type="text" inputmode="numeric"
             maxlength="11" autocomplete="off"
             placeholder="<?= $hayToken ? '8 dígitos de DNI o 11 de RUC' : 'Escribe 8 dígitos de DNI' ?>">
      <button class="boton boton-primario" type="submit">Consultar identidad</button>
    </form>
    <?php if ($hayToken): ?>
      <div class="chips-ejemplo chips-ejemplo-claro">
        <span class="chips-etiqueta">Prueba con:</span>
        <button class="chip-ejemplo" type="button" data-rellena="entrada-dni" data-valor="72865403">DNI 72865403</button>
        <span class="chips-nota">cada consulta consume 1 de tu plan Free</span>
      </div>
    <?php endif; ?>
    <div class="caja-dni" id="caja-dni">
      <?php if ($hayToken): ?>
        <p class="vacio">Consulta real de RENIEC vía ApisPeru: escribe un DNI o RUC y toca
          «Consultar identidad». El resultado se puede guardar directo en un expediente.</p>
      <?php else: ?>
        <p class="vacio">La consulta se hace por Ajax. Sin token configurado funciona en modo
          demostración: prueba con 00000001, 11111111 o 44444444.</p>
      <?php endif; ?>
    </div>
  </section>
<?php include 'plantilla/pie.php';
