# EncuentraME

Aplicación web para localizar los perfiles públicos de una persona en varias
redes sociales con un solo buscador, complementada con una búsqueda de huella
en la web y una consulta de identidad por DNI. Los hallazgos se guardan en un
expediente que se administra con CRUD, buscador y paginador.

## Requisitos

- XAMPP con **Apache** y **MySQL** (PHP 7.4 o superior con extensión `cURL`).
- Conexión a internet (las APIs externas se consultan desde el servidor).

## Instalación

1. Copiar la carpeta `EncuentraME` dentro de `htdocs` de XAMPP.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Abrir <http://localhost/EncuentraME>

La base de datos `encuentrame` y la tabla `personas` se crean automáticamente
la primera vez, junto con 16 expedientes de ejemplo.

### Importación manual con phpMyAdmin (opcional)

1. Abrir <http://localhost/phpmyadmin>
2. Pestaña **Importar** → elegir el archivo `datos.sql` → Continuar.

### Si MySQL tiene contraseña

Crear el archivo `config/db.local.php` (está excluido del repositorio):

```php
<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'tu_contraseña');
```

## Funcionalidades

| Requisito | Dónde está |
|---|---|
| **CRUD** de expedientes (crear, listar, editar, eliminar) | `personas.php`, `persona_form.php`, `acciones.php` |
| **Buscador** por nombres, alias o DNI | `personas.php` (formulario con `?q=`) |
| **Paginador** de 8 expedientes por página | `personas.php` (`?pagina=N`, conserva la búsqueda) |
| **Caso de Ajax 1**: verificar un usuario en 6 redes | `index.php` + `api/red.php` + `assets/js/buscar.js` |
| **Caso de Ajax 2**: huella en la web | `api/web.php` (Wikipedia + DuckDuckGo) |
| **Caso de Ajax 3**: consulta de DNI | `api/dni.php` (ApiPeru) |
| **Ajax extra**: guardar el hallazgo en el expediente | `acciones.php` (acción `guardar_ajax`) |

El buscador único verifica en paralelo: **GitHub, Dev.to, Stack Overflow,
Dailymotion, Hacker News y Reddit**, y ofrece enlaces directos a Instagram,
TikTok, X, YouTube, Facebook, Twitch y Pinterest.

## APIs y servicios utilizados

| Servicio | Uso | Clave |
|---|---|---|
| ApiPeru (`dniruc.apisperu.com`) | Consulta de DNI | Token (ver abajo) |
| GitHub, Dev.to, Stack Overflow, Dailymotion, Hacker News, Reddit | Verificación de perfiles | No requiere |
| Wikipedia (español) y DuckDuckGo | Huella en la web | No requiere |

### Token de ApiPeru (opcional)

1. Registrarse en <https://apisperu.com> y obtener el token desde el panel
   (**API Consultas DNI/RUC**).
2. Crear el archivo `config/api.local.php`:

```php
<?php
define('APISPERU_TOKEN', 'tu_token_aqui');
```

3. Sin token, la consulta de DNI funciona en **modo demostración** con los DNI
   ficticios `00000001`, `11111111` y `44444444`.

> El archivo `config/api.local.php` está en el `.gitignore`: el token real
> nunca se sube al repositorio.

## Estructura

```
EncuentraME/
├── index.php            # Buscador único de perfiles (resultados por Ajax)
├── personas.php         # Expedientes: CRUD, buscador y paginador
├── persona_form.php     # Alta y edición de expedientes
├── acciones.php         # Guardar / eliminar / guardar por Ajax
├── api/
│   ├── red.php          # Ajax 1: verifica usuario en una red social
│   ├── web.php          # Ajax 2: huella en la web
│   └── dni.php          # Ajax 3: consulta de DNI
├── config/
│   ├── base.php         # Conexión, helpers, instalación y datos de ejemplo
│   ├── db.php           # Datos de conexión por defecto (XAMPP)
│   ├── api.php          # Token de ApiPeru (vacío por defecto)
│   ├── db.local.php     # (opcional, no se sube) contraseña real de MySQL
│   └── api.local.php    # (opcional, no se sube) token real de ApiPeru
├── plantilla/
│   ├── cabecera.php     # Encabezado y menú
│   └── pie.php          # Pie de página
├── assets/
│   ├── css/estilo.css   # Diseño índigo/menta, responsive
│   ├── js/buscar.js     # Los 4 casos de Ajax
│   └── img/logo.svg     # Logo del sistema
├── datos.sql            # Esquema y datos para phpMyAdmin
└── README.md            # Esta documentación
```

## Paleta de colores

| Uso | Color |
|---|---|
| Fondo | `#F4F6FB` |
| Superficies | `#FFFFFF` |
| Primario (índigo) | `#4338CA` |
| Éxito (menta) | `#059669` |

## Licencia

MIT — ver [LICENSE](LICENSE).
