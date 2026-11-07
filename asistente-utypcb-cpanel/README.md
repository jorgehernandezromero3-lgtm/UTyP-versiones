# Asistente Coyote · UTyPCB

Chat informativo para el sitio de la Universidad Tecnológica y Politécnica de
Coyuca de Benítez. Responde preguntas sobre admisión, carreras, costos, calendario,
trámites, becas, servicios, deportes, noticias, transparencia y contacto con
información oficial y fuente verificable.

Construido con **PHP 7.4/8.x y MariaDB 10.11**, sin Python, Node, SQLite, scraper,
modelos de lenguaje ni dependencias de frontend.

## Características

- **156 intenciones** y **780 variantes** de pregunta en la semilla.
- Motor determinista con normalización, sinónimos, raíz ligera, Levenshtein,
  trigramas, IDF y contexto conversacional.
- Respuestas con fuente oficial, enlaces adicionales y canalización al área
  responsable cuando no hay información verificada.
- Widget accesible integrado al pie del sitio, con la paleta, tipografías y sprite
  institucionales.
- Panel administrativo protegido con CRUD, validación, bandeja de preguntas sin
  respuesta, sincronización y purga.
- Cuenta principal `oliver` con roles `superadmin`/`admin`; la cuenta principal
  puede registrar, desactivar y restablecer cuentas de apoyo desde el panel.
- API sin CORS abierto, con CSRF, límites por sesión e IP, validación de tamaño y
  escape de salida.
- Privacidad por diseño: preguntas redactadas, IP con hash HMAC, métricas agregadas
  y retención de 90/365 días.
- Documentación completa y evidencias de prueba.

## Resultados de la entrega

| Prueba | Resultado |
| --- | --- |
| Precisión top-1 en 100 preguntas no vistas | **100/100 (100 %)** |
| Contexto conversacional | **5/5** |
| Comprobaciones de seguridad | **37/37** |
| Accesibilidad de teclado | **14/14, Escape restaura el foco** |
| Latencia HTTP (15 solicitudes) | **promedio 44.8 ms; p95 55.5 ms** |
| Instalación limpia, sincronización y purga | **Correcta** |
| Gestión de cuentas administrativas | **Alta, baja, 403 para apoyo y salvaguardas verificadas** |

Las limitaciones y la metodología están en
[`documentacion/05-plan-de-pruebas-y-evidencias.md`](documentacion/05-plan-de-pruebas-y-evidencias.md).

## Inicio rápido

### 1. Requisitos

- PHP 7.4 o superior con `pdo_mysql`, `mbstring` y `json`.
- MariaDB 10.11 o superior.
- Un servidor web (Apache o Nginx) para producción.

### 2. Base de datos

```sql
CREATE DATABASE utypcb_asistente
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'utypcb_asistente'@'127.0.0.1'
  IDENTIFIED BY 'CAMBIA_ESTA_CONTRASENA';

GRANT SELECT, INSERT, UPDATE, DELETE ON utypcb_asistente.*
  TO 'utypcb_asistente'@'127.0.0.1';
```

```bash
mysql -u utypcb_asistente -p utypcb_asistente < database/schema.sql
```

### 3. Configuración

```bash
cp private/config.example.php private/config.php
```

Edita `private/config.php` con los datos de tu base, una sal aleatoria y la ruta
de los archivos de datos del sitio. Genera la sal con:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

### 4. Contenido y cuenta

```bash
php private/bin/importar.php --dry-run
php private/bin/importar.php
php private/bin/crear-admin.php --usuario=oliver --nombre="Ing. Oliver" --rol=superadmin
```

La entrega incluye la cuenta principal del ingeniero Oliver:

| Usuario | Contraseña inicial | Rol |
| --- | --- | --- |
| `oliver` | `Oliver2026!!` | `superadmin` |

Cámbiala en el primer acceso desde **Mi cuenta**. Si la base ya existía, aplica
`database/migracion-2026-11-admin-roles.sql` antes de crear la cuenta. Desde
**Administradores**, la cuenta principal puede registrar cuentas de apoyo con rol
`admin` para revisar el panel.

### 5. Prueba local

```bash
php -S 127.0.0.1:8099 -t public
```

- Demo del widget: `http://127.0.0.1:8099/asistente/demo.php`
- Panel: `http://127.0.0.1:8099/asistente/admin/`

### 6. Integración

En `footer.inc.php`, antes de `</body>`:

```php
<?php
$utypAsistenteBase = '/asistente';
$utypAsistenteAviso = '/asistente/privacidad.php';
require __DIR__ . '/asistente/widget.php';
```

La guía completa está en
[`documentacion/03-instalacion-e-integracion.md`](documentacion/03-instalacion-e-integracion.md).

## Estructura

```text
asistente-utypcb/
├── public/                 # archivos públicos (widget, API, panel, aviso)
├── private/                # código, configuración, logs y comandos
├── database/               # esquema y semilla de conocimiento
├── tests/                  # evaluación de 100 preguntas
├── integracion/            # fragmento para footer.inc.php
└── documentacion/          # entrega completa y evidencias
```

## Comandos

```bash
php private/bin/importar.php [--dry-run] [--actualizar]
php private/bin/crear-admin.php [--usuario=...] [--nombre=...] [--rol=admin|superadmin]
php private/bin/sincronizar.php
php private/bin/purgar.php
php private/bin/evaluar.php
php private/bin/probar-motor.php "cuanto cuesta la ficha"
```

## Documentación

| Documento | Contenido |
| --- | --- |
| [01 · Requisitos y alcance](documentacion/01-requisitos-y-alcance.md) | Objetivos, actores, requisitos y límites |
| [02 · Manual técnico](documentacion/02-manual-tecnico.md) | Arquitectura, motor, base de datos, API y seguridad |
| [03 · Instalación e integración](documentacion/03-instalacion-e-integracion.md) | Puesta en marcha y publicación |
| [04 · Base de conocimiento](documentacion/04-base-de-conocimiento.csv) | 156 intenciones revisables en CSV |
| [05 · Plan de pruebas y evidencias](documentacion/05-plan-de-pruebas-y-evidencias.md) | Casos, resultados y limitaciones |
| [06 · Manual del administrador](documentacion/06-manual-administrador.md) | Uso del panel paso a paso |
| [07 · Aviso de privacidad y seguridad](documentacion/07-aviso-privacidad-y-seguridad.md) | Aviso público y política interna |

Las capturas y resultados están en
[`documentacion/evidencias/`](documentacion/evidencias/LEEME.md).

## Seguridad

- No publiques `private/`, `tests/`, `documentacion/` ni respaldos.
- `private/config.php` debe quedar fuera de la carpeta web.
- Usa HTTPS en producción.
- No desactives los límites de uso ni el CSRF.
- No agregues CORS abierto.
- Cambia la contraseña temporal del panel en el primer acceso.
- Crea una cuenta individual por persona y desactiva las que ya no se usen.
- Revisa y valida el contenido después de cada sincronización.

Para reportar un incidente: `serviciosescolares@utypcb.edu.mx`.

## Licencia y uso

Proyecto desarrollado para la UTyPCB. El contenido institucional pertenece a la
universidad. No se incluyen imágenes del sitio ni datos personales.
