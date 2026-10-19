/* =========================================================
   EncuentraME - JavaScript
   Casos de Ajax de la aplicación:
   1) Verificación de perfiles en redes sociales (api/red.php)
   2) Huella en la web (api/web.php)
   3) Consulta de DNI (api/dni.php)
   4) Guardado del hallazgo en el expediente (acciones.php)
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

  /* Estado del último rastreo, para poder guardarlo en el expediente */
  var ultimoRastreo = null;

  /* ---------------- Formularios ---------------- */
  var formRastreo = document.getElementById('form-rastreo');
  if (formRastreo) {
    formRastreo.addEventListener('submit', function (evento) {
      evento.preventDefault();
      rastrear();
    });
  }

  var formDni = document.getElementById('form-dni');
  if (formDni) {
    formDni.addEventListener('submit', function (evento) {
      evento.preventDefault();
      consultarDNI();
    });
  }

  var botonGuardar = document.getElementById('btn-guardar-hallazgo');
  if (botonGuardar) {
    botonGuardar.addEventListener('click', function () {
      if (!ultimoRastreo) {
        return;
      }
      var esUsuario = /^[A-Za-z0-9._-]{1,40}$/.test(ultimoRastreo.usuario);
      guardarHallazgo({
        dni: '',
        nombres: ultimoRastreo.texto,
        alias: esUsuario ? ultimoRastreo.usuario : '',
        redes: ultimoRastreo.redes,
        notas: 'Rastreo de «' + ultimoRastreo.texto + '» el ' + fechaHoy()
      }, botonGuardar);
    });
  }

  /* ============ Caso de Ajax 1: perfiles en redes ============ */
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

    /* Estado para poder guardar el hallazgo después */
    ultimoRastreo = { texto: texto, usuario: usuario, redes: [] };
    if (botonGuardar) {
      botonGuardar.hidden = false;
      botonGuardar.disabled = false;
      botonGuardar.textContent = 'Guardar hallazgo en expediente';
    }

    /* Búsqueda en la web: siempre se ejecuta, también con nombres completos */
    buscarEnLaWeb(texto);

    if (!/^[A-Za-z0-9._-]{1,40}$/.test(usuario)) {
      caja.innerHTML =
        '<p class="vacio">Para verificar redes escribe un usuario sin espacios ' +
        '(letras, números, puntos o guiones). La búsqueda en la web sí se realizó.</p>';
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
        cuenta.textContent = encontrados + ' de ' + redesVerificadas.length +
          ' redes con cuenta pública';
      }
    }

    /* --- Petición Ajax en paralelo, una por red --- */
    redesVerificadas.forEach(function (red) {
      fetch('api/red.php?red=' + encodeURIComponent(red.id) + '&usuario=' + encodeURIComponent(usuario))
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
          if (datos.existe) {
            encontrados++;
            if (ultimoRastreo) {
              ultimoRastreo.redes.push(datos.etiqueta);
            }
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

  /* ============ Caso de Ajax 2: huella en la web ============ */
  function buscarEnLaWeb(texto) {
    var caja = document.getElementById('caja-web');
    var cuenta = document.getElementById('cuenta-web');
    if (!caja) {
      return;
    }
    caja.innerHTML = '<p class="cargando">Buscando en la web…</p>';
    if (cuenta) {
      cuenta.textContent = '';
    }

    fetch('api/web.php?texto=' + encodeURIComponent(texto))
      .then(function (respuesta) { return respuesta.json(); })
      .then(function (datos) {
        if (!datos.ok) {
          caja.innerHTML = '<p class="vacio">' + datos.mensaje + '</p>';
          return;
        }

        caja.innerHTML = '';
        if (cuenta) {
          cuenta.textContent = datos.resultados.length + ' resultado(s)';
        }
        if (!datos.resultados.length) {
          caja.innerHTML =
            '<p class="vacio">Sin resultados en Wikipedia ni DuckDuckGo para ese texto.</p>';
        }

        datos.resultados.forEach(function (item) {
          var tarjeta = document.createElement('article');
          tarjeta.className = 'tarjeta-web';

          var fuente = document.createElement('span');
          fuente.className = 'fuente fuente-' + item.fuente.toLowerCase().replace(/\s+/g, '-');
          fuente.textContent = item.fuente;

          var enlace = document.createElement('a');
          enlace.className = 'web-titulo';
          enlace.href = item.url;
          enlace.target = '_blank';
          enlace.rel = 'noopener';
          enlace.textContent = item.titulo;

          var resumen = document.createElement('p');
          resumen.className = 'web-resumen';
          resumen.textContent = item.resumen;

          tarjeta.appendChild(fuente);
          tarjeta.appendChild(enlace);
          tarjeta.appendChild(resumen);
          caja.appendChild(tarjeta);
        });

        /* Enlaces directos a buscadores externos */
        var fila = document.createElement('div');
        fila.className = 'enlaces-web';
        datos.enlaces.forEach(function (buscador) {
          var enlace = document.createElement('a');
          enlace.className = 'chip-enlace';
          enlace.href = buscador.url;
          enlace.target = '_blank';
          enlace.rel = 'noopener';
          enlace.textContent = 'Buscar en ' + buscador.fuente + ' ↗';
          fila.appendChild(enlace);
        });
        caja.appendChild(fila);
      })
      .catch(function () {
        caja.innerHTML = '<p class="vacio">No se pudo consultar la web. Revisa tu conexión a internet.</p>';
      });
  }

  /* ============ Caso de Ajax 3: consulta de DNI ============ */
  function consultarDNI() {
    var entrada = document.getElementById('entrada-dni');
    var caja = document.getElementById('caja-dni');
    var dni = entrada.value.trim();

    if (!/^\d{8}$/.test(dni)) {
      caja.innerHTML = '<p class="vacio">El DNI debe tener exactamente 8 dígitos.</p>';
      return;
    }

    caja.innerHTML = '<p class="cargando">Consultando la API de identidad…</p>';

    fetch('api/dni.php?dni=' + dni)
      .then(function (respuesta) { return respuesta.json(); })
      .then(function (datos) {
        if (!datos.ok) {
          caja.innerHTML = '<p class="vacio">' + datos.mensaje + '</p>';
          return;
        }
        pintarResultadoDni(datos);
      })
      .catch(function () {
        caja.innerHTML = '<p class="vacio">No se pudo consultar la API. Revisa tu conexión.</p>';
      });
  }

  function pintarResultadoDni(datos) {
    var caja = document.getElementById('caja-dni');
    caja.innerHTML = '';

    var tarjeta = document.createElement('div');
    tarjeta.className = 'resultado-dni';

    var info = document.createElement('div');
    info.className = 'resultado-dni-info';

    if (datos.modo === 'demo') {
      var insignia = document.createElement('span');
      insignia.className = 'badge-demo';
      insignia.textContent = 'Modo demostración';
      info.appendChild(insignia);
    }

    var nombre = document.createElement('p');
    nombre.className = 'dni-nombre';
    nombre.textContent = (
      (datos.apellido_paterno || '') + ' ' +
      (datos.apellido_materno || '') + ' ' +
      (datos.nombres || '')
    ).trim();

    var detalle = document.createElement('p');
    detalle.className = 'dni-detalle';
    detalle.textContent = 'DNI ' + datos.dni +
      (datos.cod_verifica ? ' · código de verificación ' + datos.cod_verifica : '') +
      (datos.mensaje ? ' · ' + datos.mensaje : '');

    info.appendChild(nombre);
    info.appendChild(detalle);
    tarjeta.appendChild(info);

    var boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'boton boton-secundario boton-chico';
    boton.textContent = 'Guardar en expediente';
    boton.addEventListener('click', function () {
      guardarHallazgo({
        dni: datos.dni,
        nombres: nombre.textContent,
        alias: '',
        redes: [],
        notas: 'Consulta de DNI desde el rastreador el ' + fechaHoy()
      }, boton);
    });
    tarjeta.appendChild(boton);

    caja.appendChild(tarjeta);
  }

  /* ============ Guardar hallazgo en el expediente (Ajax) ============ */
  function guardarHallazgo(datos, boton) {
    if (boton) {
      boton.disabled = true;
      boton.textContent = 'Guardando…';
    }

    var cuerpo = new URLSearchParams();
    cuerpo.append('accion', 'guardar_ajax');
    cuerpo.append('dni', datos.dni || '');
    cuerpo.append('nombres', datos.nombres || '');
    cuerpo.append('alias', datos.alias || '');
    cuerpo.append('estado', 'por revisar');
    cuerpo.append('notas', datos.notas || '');
    (datos.redes || []).forEach(function (red) {
      cuerpo.append('redes[]', red);
    });

    fetch('acciones.php', { method: 'POST', body: cuerpo })
      .then(function (respuesta) { return respuesta.json(); })
      .then(function (resultado) {
        if (boton) {
          boton.disabled = true;
          boton.textContent = resultado.ok ? '✓ Guardado en expedientes' : resultado.mensaje;
        }
      })
      .catch(function () {
        if (boton) {
          boton.disabled = false;
          boton.textContent = 'Error al guardar, intenta de nuevo';
        }
      });
  }

  function fechaHoy() {
    return new Date().toLocaleDateString('es-PE');
  }
});
