# Integración en footer.inc.php

El asistente se inserta con una sola línea al final del pie de página del sitio.
No requiere jQuery, Bootstrap ni bibliotecas externas.

## 1. Copia el fragmento

Al final de `footer.inc.php`, antes de `</body>`, agrega:

```php
<?php
$utypAsistenteBase = '/asistente';
$utypAsistenteAviso = '/asistente/privacidad.php';
require __DIR__ . '/asistente/widget.php';
```

Puedes usar como base el archivo `footer.inc.php.example` de esta carpeta.

## 2. Verifica los iconos

El widget reutiliza el sprite institucional con estos identificadores:

- `i-huella` — lanzador y encabezado.
- `i-cerrar` — botón de cierre.
- `i-flecha` — botón de envío.
- `i-destello` — nueva conversación.

El `footer.inc.php` del sitio ya incluye `icons.inc.php`; no copies imágenes nuevas.
La página de demostración trae su propio sprite local para pruebas.

## 3. Rutas de instalación

Estructura recomendada, con `private/` fuera de la carpeta pública:

```text
/home/USUARIO/
├── private/                 ← configuración, código y bitácoras
│   ├── config.php
│   └── src/
└── public_html/             ← único directorio accesible por web
    ├── footer.inc.php
    └── asistente/
        ├── estado.php
        ├── chat.php
        ├── widget.php
        ├── widget.css
        └── widget.js
```

Los endpoints `estado.php` y `chat.php` cargan `../../private/src/bootstrap.php`,
por lo que `private/` debe quedar exactamente un nivel arriba de `public_html/`.
Si tu hosting usa otra ruta, ajusta el `require` de esos dos archivos **antes**
de subirlos. No pongas credenciales dentro de `public_html/`.

## 4. Prueba rápida

1. Abre `https://TU-DOMINIO/asistente/demo.php` en el navegador.
2. Pulsa «Abrir el asistente».
3. Pregunta: «¿Cuánto cuesta la ficha?».
4. Verifica que aparezca la respuesta con su enlace a la convocatoria.

## 5. Qué no hacer

- No copies `private/config.php` dentro de `public_html`.
- No agregues encabezados CORS abiertos; el widget y la API viven en el mismo dominio.
- No modifiques `header.inc.php` ni los estilos base del sitio para este widget.
- No publiques conversaciones de prueba ni datos de alumnos en la base de conocimiento.
