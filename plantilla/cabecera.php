<?php $titulo_pagina = $titulo_pagina ?? 'Rastreo'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo_pagina) ?> · EncuentraME</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
  <header class="encabezado">
    <div class="contenedor encabezado-interno">
      <a class="marca" href="index.php">
        <img class="marca-logo" src="assets/img/logo.svg" alt="" width="42" height="42">
        <span class="marca-nombre">Encuentra<span class="marca-acento">ME</span></span>
      </a>
      <nav class="menu">
        <a class="menu-enlace" href="index.php">Rastreo</a>
        <a class="menu-enlace" href="personas.php">Expedientes</a>
        <a class="boton boton-primario" href="persona_form.php">+ Nuevo expediente</a>
      </nav>
    </div>
  </header>
  <main class="contenedor">
