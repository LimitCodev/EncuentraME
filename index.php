<?php
require_once 'config/base.php';
instalar_base();

$titulo_pagina = 'Rastreo';
include 'plantilla/cabecera.php';
?>
  <!-- Buscador único: los resultados llegan por Ajax, sin recargar la página -->
  <section class="hero">
    <h1>Un solo buscador, varias redes</h1>
    <p class="hero-texto">
      Escribe un nombre de usuario y EncuentraME verifica en paralelo si existe una
      cuenta pública en cada red. También puedes escribir un nombre completo para
      revisar su huella en la web.
    </p>
    <form class="buscador-grande" id="form-rastreo">
      <input id="entrada-rastreo" type="search" maxlength="60" autocomplete="off"
             placeholder="Ej. torvalds, @mariel.rios o María Elena Ríos">
      <button class="boton boton-primario" type="submit">Rastrear</button>
    </form>
    <p class="buscador-pista">Los resultados se cargan con Ajax, sin recargar la página.</p>
  </section>

  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Perfiles verificados</h2>
      <span class="cuenta-verificadas" id="cuenta-verificadas"></span>
    </div>
    <div class="rejilla-perfiles" id="caja-perfiles">
      <p class="vacio">Escribe un usuario y toca «Rastrear» para verificar las redes.</p>
    </div>
  </section>

  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Huella en la web</h2>
      <span class="cuenta-verificadas" id="cuenta-web"></span>
    </div>
    <div class="lista-web" id="caja-web">
      <p class="vacio">Aquí aparecerán resultados de Wikipedia y DuckDuckGo sobre lo que busques.</p>
    </div>
  </section>

  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Otras redes (enlace directo)</h2>
    </div>
    <div class="caja-enlaces" id="caja-enlaces">
      <p class="vacio">Aquí aparecerán los enlaces de búsqueda de cada red.</p>
    </div>
  </section>
<?php include 'plantilla/pie.php';
