/* =========================================================
   EncuentraME - JavaScript
   Casos de Ajax de la aplicación:
   1) Verificación de perfiles en redes sociales (api/red.php)
   2) Huella en la web (api/web.php)
   3) Consulta de DNI (api/dni.php)
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
  /* ---------------- Redes ---------------- */
  var redesVerificadas = [
    { id: 'github', nombre: 'GitHub' },
    { id: 'devto', nombre: 'Dev.to' },
    { id: 'stackoverflow', nombre: 'Stack Overflow' },
    { id: 'dailymotion', nombre: 'Dailymotion' },
    { id: 'hackernews', nombre: 'Hacker News' },
    { id: 'reddit', nombre: 'Reddit' }
  ];

  var redesEnlace = [
    { nombre: 'Instagram', url: 'https://www.instagram.com/{u}/' },
    { nombre: 'TikTok', url: 'https://www.tiktok.com/@{u}' },
    { nombre: 'X', url: 'https://x.com/{u}' },
    { nombre: 'YouTube', url: 'https://www.youtube.com/@{u}' },
    { nombre: 'Facebook', url: 'https://www.facebook.com/{u}' },
    { nombre: 'Twitch', url: 'https://www.twitch.tv/{u}' },
    { nombre: 'Pinterest', url: 'https://www.pinterest.com/{u}/' }
  ];

  /* ---------------- Buscador único ---------------- */
  var formRastreo = document.getElementById('form-rastreo');
  if (formRastreo) {
    formRastreo.addEventListener('submit', function (evento) {
      evento.preventDefault();
      rastrear();
    });
  }

  function rastrear() {
    var entrada = document.getElementById('entrada-rastreo');
    var caja = document.getElementById('caja-perfiles');
    var cajaEnlaces = document.getElementById('caja-enlaces');
    var cuenta = document.getElementById('cuenta-verificadas');
    var texto = entrada.value.trim();
    var usuario = texto.replace(/^@/, '');

    if (texto.length < 2) {
      caja.innerHTML = '<p class="vacio">Escribe al menos 2 caracteres.</p>';
      return;
    }

    if (!/^[A-Za-z0-9._-]{1,40}$/.test(usuario)) {
      caja.innerHTML =
        '<p class="vacio">Para verificar redes escribe un usuario sin espacios ' +
        '(letras, números, puntos o guiones).</p>';
      cajaEnlaces.innerHTML =
        '<p class="vacio">Los enlaces directos se generan a partir de un usuario.</p>';
      cuenta.textContent = '';
      return;
    }

    /* --- Esqueletos de carga: una tarjeta por red --- */
    caja.innerHTML = '';
    cuenta.textContent = 'verificando…';
    var tarjetas = {};

    redesVerificadas.forEach(function (red) {
      var tarjeta = document.createElement('article');
      tarjeta.className = 'tarjeta-red tarjeta-cargando';

      var encabezado = document.createElement('header');
      var punto = document.createElement('span');
      punto.className = 'punto punto-cargando';
      var titulo = document.createElement('strong');
      titulo.textContent = red.nombre;
      encabezado.appendChild(punto);
      encabezado.appendChild(titulo);

      var estado = document.createElement('p');
      estado.className = 'tarjeta-estado';
      estado.textContent = 'Verificando…';

      tarjeta.appendChild(encabezado);
      tarjeta.appendChild(estado);
      caja.appendChild(tarjeta);
      tarjetas[red.id] = tarjeta;
    });

    var encontrados = 0;
    var terminadas = 0;

    function terminar() {
      terminadas++;
      if (terminadas === redesVerificadas.length) {
        cuenta.textContent = encontrados + ' de ' + redesVerificadas.length + ' redes con cuenta pública';
      }
    }

    /* --- Petición Ajax en paralelo, una por red --- */
    redesVerificadas.forEach(function (red) {
      fetch('api/red.php?red=' + encodeURIComponent(red.id) + '&usuario=' + encodeURIComponent(usuario))
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
          if (datos.existe) {
            encontrados++;
          }
          pintarTarjeta(tarjetas[red.id], datos);
          terminar();
        })
        .catch(function () {
          pintarTarjeta(tarjetas[red.id], {
            etiqueta: red.nombre,
            existe: false,
            mensaje: 'Error de conexión con la API.'
          });
          terminar();
        });
    });

    pintarEnlaces(usuario);
  }

  function pintarTarjeta(tarjeta, datos) {
    tarjeta.className = 'tarjeta-red';
    tarjeta.innerHTML = '';

    var encabezado = document.createElement('header');
    var punto = document.createElement('span');
    punto.className = 'punto ' + (datos.existe ? 'punto-ok' : 'punto-no');
    var titulo = document.createElement('strong');
    titulo.textContent = datos.etiqueta || datos.red;
    encabezado.appendChild(punto);
    encabezado.appendChild(titulo);
    tarjeta.appendChild(encabezado);

    if (datos.existe) {
      if (datos.avatar) {
        var avatar = document.createElement('img');
        avatar.className = 'avatar';
        avatar.src = datos.avatar;
        avatar.alt = '';
        tarjeta.appendChild(avatar);
      }
      var nombre = document.createElement('p');
      nombre.className = 'tarjeta-nombre';
      nombre.textContent = '@' + (datos.nombre || '');
      tarjeta.appendChild(nombre);

      if (datos.detalle) {
        var detalle = document.createElement('p');
        detalle.className = 'tarjeta-detalle';
        detalle.textContent = datos.detalle;
        tarjeta.appendChild(detalle);
      }

      var enlace = document.createElement('a');
      enlace.className = 'tarjeta-enlace';
      enlace.href = datos.url;
      enlace.target = '_blank';
      enlace.rel = 'noopener';
      enlace.textContent = 'Ver perfil ↗';
      tarjeta.appendChild(enlace);
    } else {
      var estado = document.createElement('p');
      estado.className = 'tarjeta-estado';
      estado.textContent = datos.mensaje || 'Sin cuenta pública encontrada.';
      tarjeta.appendChild(estado);
    }
  }

  function pintarEnlaces(usuario) {
    var caja = document.getElementById('caja-enlaces');
    caja.innerHTML = '';

    redesEnlace.forEach(function (red) {
      var enlace = document.createElement('a');
      enlace.className = 'chip-enlace';
      enlace.href = red.url.replace('{u}', encodeURIComponent(usuario));
      enlace.target = '_blank';
      enlace.rel = 'noopener';
      enlace.textContent = red.nombre + ' ↗';
      caja.appendChild(enlace);
    });
  }
});
