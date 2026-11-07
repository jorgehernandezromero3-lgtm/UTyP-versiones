# 03 · Instalación e integración

**Proyecto:** Asistente Coyote · UTyPCB
**Versión:** 1.0.0
**Fecha:** 6 de noviembre de 2026
**Audiencia:** responsable técnico que instalará y publicará el asistente.

## 1. Antes de empezar

### 1.1 Lo que incluye la entrega

```text
asistente-utypcb/
├── public/          # archivos que deben quedar accesibles por web
├── private/         # código, configuración y bitácoras (NO público)
├── database/        # esquema y semilla
├── tests/           # evaluación de 100 preguntas
├── integracion/     # fragmento para footer.inc.php
├── documentacion/   # siete documentos y evidencias
└── README.md
```

### 1.2 Requisitos

| Requisito | Mínimo | Recomendado |
| --- | --- | --- |
| PHP | 7.4 | 8.1 o superior |
| Extensiones PHP | `pdo_mysql`, `mbstring`, `json` | Las mismas |
| MariaDB | 10.11 | 10.11 o superior |
| Servidor web | Apache o Nginx | Apache con PHP-FPM |
| HTTPS | No obligatorio en local | Obligatorio en producción |
| Espacio en disco | 10 MB | 50 MB con respaldos |
| Acceso | Consola SSH o panel de archivos | SSH y cron |

Verifica las extensiones:

```bash
php -m | grep -E 'pdo_mysql|mbstring|json'
```

Verifica la versión:

```bash
php -v
mysql --version
```

### 1.3 Lo que no se debe publicar

- `private/config.php` (contiene credenciales y sal).
- `private/logs/`.
- `private/bin/`.
- `tests/` y `documentacion/` (pueden contener rutas internas).
- Cualquier respaldo `.sql` o `.zip`.

Solo `public/asistente/` debe quedar dentro del directorio web.

## 2. Estructura de instalación

### 2.1 Producción recomendada

```text
/home/USUARIO/
├── private/                     ← fuera de public_html
│   ├── config.php
│   ├── bin/
│   ├── logs/
│   ├── src/
│   └── views/
└── public_html/                 ← único directorio web
    ├── footer.inc.php           ← sitio existente
    ├── icons.inc.php
    ├── header.inc.php
    ├── estilos_base.inc.php
    └── asistente/
        ├── admin/
        ├── chat.php
        ├── estado.php
        ├── demo.php
        ├── pagina.css
        ├── privacidad.php
        ├── widget.css
        ├── widget.js
        └── widget.php
```

Los archivos de `public/asistente/` se copian a `public_html/asistente/`. El
contenido de `private/` se copia a `/home/USUARIO/private/`, exactamente un nivel
arriba de `public_html/`, porque los endpoints calculan la ruta así:

```php
require __DIR__ . '/../../private/src/bootstrap.php';   // chat.php y estado.php
require dirname(__DIR__, 3) . '/private/src/bootstrap.php'; // admin/index.php
```

Si tu hosting no permite esa estructura, cambia esos `require` **antes de subir**
los archivos. No muevas `private/` dentro de `public_html/`.

### 2.2 Prueba local con el servidor integrado

Desde la raíz del proyecto:

```bash
php -S 127.0.0.1:8099 -t public
```

Abre:

- `http://127.0.0.1:8099/asistente/demo.php` — demostración del widget.
- `http://127.0.0.1:8099/asistente/admin/` — panel administrativo.

Esta opción es solo para pruebas. En producción usa Apache o Nginx.

### 2.3 Prueba local con XAMPP

1. Copia el proyecto completo a `C:\xampp\htdocs\asistente-utypcb`.
2. Configura un host virtual o usa el servidor integrado. Si accedes por
   `http://localhost/asistente-utypcb/public/asistente/demo.php`, la ruta relativa
   funciona, pero las URLs del widget quedarán con `/public/`. Para evitar
   diferencias con producción, se recomienda el servidor integrado o un host
   virtual con `DocumentRoot` en `...\asistente-utypcb\public`.
3. Inicia Apache y MariaDB desde el panel de XAMPP.

## 3. Crear la base de datos

### 3.1 Entrar a MariaDB

```bash
mysql -u root -p
```

En XAMPP, usa la contraseña que definiste al instalar MariaDB. No escribas esa
contraseña en archivos del proyecto ni en la documentación.

### 3.2 Crear base y usuario

Sustituye `CAMBIA_ESTA_CONTRASENA` por una contraseña larga y única:

```sql
CREATE DATABASE utypcb_asistente
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'utypcb_asistente'@'127.0.0.1'
  IDENTIFIED BY 'CAMBIA_ESTA_CONTRASENA';

GRANT SELECT, INSERT, UPDATE, DELETE
  ON utypcb_asistente.*
  TO 'utypcb_asistente'@'127.0.0.1';

FLUSH PRIVILEGES;
```

El usuario de la aplicación no necesita `CREATE`, `ALTER`, `DROP` ni permisos
globales. Si el hosting usa `localhost` en lugar de `127.0.0.1`, crea el usuario
con ese host.

### 3.3 Importar el esquema

```bash
mysql -u utypcb_asistente -p utypcb_asistente < database/schema.sql
```

o, si prefieres usar root:

```bash
mysql -u root -p utypcb_asistente < database/schema.sql
```

Verifica las ocho tablas:

```sql
USE utypcb_asistente;
SHOW TABLES;
```

Resultado esperado:

```text
asistente_admin_users
asistente_areas
asistente_events
asistente_intent_variants
asistente_intents
asistente_rate_limits
asistente_sync_log
asistente_unanswered
```

### 3.4 Actualizar una instalación existente

Si la base ya tenía datos de una versión anterior, no vuelvas a importar
`schema.sql`. Aplica solo la migración de roles:

```bash
mysql -u utypcb_asistente -p utypcb_asistente \
  < database/migracion-2026-11-admin-roles.sql
```

La migración agrega la columna `rol` a `asistente_admin_users` y promueve a
`superadmin` la cuenta `oliver` si ya existe. Para comprobarla:

```sql
SHOW COLUMNS FROM asistente_admin_users LIKE 'rol';
SELECT usuario, rol, activo FROM asistente_admin_users;
```

## 4. Configurar la aplicación

### 4.1 Crear `private/config.php`

```bash
cp private/config.example.php private/config.php
```

Edita el archivo y cambia todos los valores marcados. La configuración tiene seis
secciones:

| Sección | Qué contiene |
| --- | --- |
| `app` | Nombre, versión, zona horaria y depuración |
| `db` | DSN, usuario y contraseña de MariaDB |
| `seguridad` | Sal de IP, nombres de sesión, inactividad y límites |
| `privacidad` | Retención, aviso y responsable |
| `sitio` | URL, carpeta de datos y archivos a sincronizar |
| `motor` | Umbral, margen, límites y contexto |

### 4.2 Base de datos

```php
'db' => [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=utypcb_asistente;charset=utf8mb4',
    'usuario' => 'utypcb_asistente',
    'password' => 'CAMBIA_ESTA_CONTRASENA',
],
```

La contraseña debe coincidir con la del usuario creado en el paso 3.2.

### 4.3 Sal de identidad

Genera una cadena aleatoria:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Copia el resultado en:

```php
'ip_salt' => 'CADENA_ALEATORIA_DE_64_CARACTERES',
```

Esta sal se usa para calcular el hash de IP de las preguntas sin respuesta. Si la
cambias, los hashes anteriores dejan de coincidir; no es necesario conservarlos.

### 4.4 Sesiones y límites

```php
'chat_session_name' => 'UTYPCHAT',
'admin_session_name' => 'UTYPADM',
'chat_session_path' => '/',
'admin_session_path' => '/asistente/admin',
'admin_idle_minutos' => 30,
'limites' => [
    'chat_sesion' => ['limite' => 20, 'ventana' => 300],
    'chat_ip' => ['limite' => 120, 'ventana' => 600],
    'admin_login' => ['limite' => 5, 'ventana' => 900],
],
```

- `chat_sesion`: 20 preguntas cada 5 minutos por sesión.
- `chat_ip`: 120 preguntas cada 10 minutos por IP.
- `admin_login`: 5 intentos cada 15 minutos.

Ajusta los valores según el tráfico, sin desactivar los límites.

### 4.5 Sitio y sincronización

```php
'sitio' => [
    'url' => 'https://utypcb.edu.mx',
    'directorio_datos' => '/home/USUARIO/public_html',
    'archivos' => [
        'calendario' => 'calendario_data.php',
        'noticias' => 'noticias_data.php',
        'carreras' => 'carreras_data.php',
        'deportes' => 'deportes_data.php',
        'tripticos' => 'tripticos_data.php',
    ],
    'variables' => [
        'calendario' => 'CAL',
        'noticias' => 'NOTICIAS',
        'carreras' => 'CARRERAS',
        'deportes' => 'DEPORTES',
        'tripticos' => 'TRIPTICOS',
    ],
],
```

`directorio_datos` debe ser una **ruta absoluta del servidor** a la carpeta donde
viven los archivos del sitio. No es una URL. El usuario de PHP necesita permiso de
lectura sobre esos archivos.

### 4.6 Zona horaria y depuración

```php
'app' => [
    'zona_horaria' => 'America/Mexico_City',
    'debug' => false,
],
```

En producción, `debug` siempre debe ser `false`. Si hay un error, revisa
`private/logs/error.log`; nunca muestres trazas al visitante.

### 4.7 Permisos

En servidores Linux:

```bash
chmod 750 private
chmod 640 private/config.php
mkdir -p private/logs
chmod 750 private/logs
chown -R USUARIO_WEB:USUARIO_WEB private/logs
```

El usuario del servidor web debe poder escribir en `private/logs/`, pero no en
`private/config.php`.

## 5. Cargar la base de conocimiento

### 5.1 Validar la semilla sin escribir

```bash
php private/bin/importar.php --dry-run
```

Salida esperada:

```text
Áreas: 5
Intenciones: 156
Variantes: 780
Validación correcta (simulación; no se escribió en la base).
```

Si aparece un error, el importador indica la intención y el problema. No continúes
hasta que la validación sea correcta.

### 5.2 Importar

```bash
php private/bin/importar.php
```

La primera importación inserta todas las intenciones y variantes. Las validaciones
comprueban:

- slug único y con formato;
- categoría, área, programa y tema existentes;
- pregunta de 5 a 255 caracteres;
- respuesta de al menos 20 caracteres;
- fuente con título y URL válida;
- al menos 2 palabras clave;
- exactamente 5 variantes por intención;
- variantes no repetidas dentro de la intención ni entre intenciones.

### 5.3 Actualizar desde la semilla

Después de editar los archivos `database/seed/*.php`:

```bash
php private/bin/importar.php --actualizar
```

Este modo actualiza las intenciones existentes, reemplaza solo las variantes de
origen `base` y reinicia la validación. Las variantes agregadas desde el panel
(`origen = admin`) se conservan.

### 5.4 Verificar

```sql
SELECT COUNT(*) AS intenciones FROM asistente_intents WHERE activa = 1;
SELECT COUNT(*) AS variantes FROM asistente_intent_variants;
SELECT COUNT(*) AS areas FROM asistente_areas;
```

Resultado esperado en una instalación nueva:

| Consulta | Resultado |
| --- | ---: |
| Intenciones activas | 156 |
| Variantes | 780 |
| Áreas | 5 |

## 6. Crear la cuenta administrativa

### 6.1 Roles

| Rol | Uso | Gestión de cuentas |
| --- | --- | --- |
| `superadmin` | Cuenta principal del ingeniero Oliver | Sí: crea, desactiva y restablece cuentas de apoyo |
| `admin` | Cuentas de apoyo para revisar el panel | No |

La entrega incluye una cuenta permanente para el ingeniero Oliver:

| Dato | Valor |
| --- | --- |
| Usuario | `oliver` |
| Contraseña inicial | `Oliver2026!!` |
| Rol | `superadmin` |

Cámbiala en el primer acceso desde **Mi cuenta**. La contraseña se almacena con
`password_hash`; no se guarda en claro ni puede recuperarse, solo restablecerse.

### 6.2 Crear o actualizar una cuenta por consola

```bash
php private/bin/crear-admin.php
```

El comando solicita:

- usuario (minúsculas, sin espacios, de 3 a 60 caracteres);
- nombre visible;
- rol (`admin` o `superadmin`, por omisión `admin`);
- contraseña de 12 a 200 caracteres, escrita de forma oculta;
- confirmación.

También puede automatizarse en un entorno controlado:

```bash
UTYP_ADMIN_PASSWORD='CONTRASENA_TEMPORAL' php private/bin/crear-admin.php \
  --usuario=servicios --nombre="Servicios Escolares" --rol=admin
```

Para crear la cuenta principal del ingeniero Oliver en una instalación nueva:

```bash
php private/bin/crear-admin.php \
  --usuario=oliver --nombre="Ing. Oliver" --rol=superadmin
```

Cuando el comando solicite la contraseña, escribe `Oliver2026!!`. Así la
credencial no queda en el historial del shell. El comando actualiza el nombre y
el rol si el usuario ya existe. Cambia la contraseña temporal en el primer acceso
desde **Mi cuenta**.

Para verificar:

```sql
SELECT usuario, nombre, rol, activo, ultimo_acceso FROM asistente_admin_users;
```

### 6.3 Cuentas de apoyo desde el panel

La cuenta principal puede registrar a las personas que la apoyarán en la revisión
del panel sin usar la consola:

1. Entra a **Administradores**.
2. Pulsa **Nueva cuenta**.
3. Captura nombre, usuario y contraseña inicial.
4. Entrega la credencial por un medio seguro.

Las cuentas creadas desde el panel siempre tienen rol `admin`. Para otra cuenta
`superadmin` se usa la consola del servidor.

## 7. Probar la aplicación

### 7.1 Estado de la API

```bash
curl -i http://127.0.0.1:8099/asistente/estado.php
```

Debe responder `200`, `Content-Type: application/json` y un JSON con `ok: true`,
`csrf`, `limites`, `privacidad` y `sugerencias`.

### 7.2 Panel

1. Abre `/asistente/admin/`.
2. Inicia sesión con la cuenta creada.
3. Verifica que el panel general muestre 156 intenciones y 0 pendientes.
4. Abre **Intenciones**, busca «ficha» y edita una.
5. Cierra sesión.

### 7.3 Widget

1. Abre `/asistente/demo.php`.
2. Pulsa el botón del asistente.
3. Pregunta «¿Cuánto cuesta la ficha?».
4. Verifica respuesta, fuente y sugerencias.
5. Pregunta algo fuera de alcance, por ejemplo «¿Quién ganó el partido?».
6. Verifica que no invente y que canalice con un área.

### 7.4 Prueba por consola

```bash
php private/bin/probar-motor.php "cuanto cuesta la ficha" "y el examen?"
```

Muestra tipo, confianza, milisegundos, fuente y sugerencias. Sirve para diagnosticar
sin abrir el navegador.

## 8. Sincronizar con los datos del sitio

### 8.1 Comprobar rutas

```bash
ls -l /home/USUARIO/public_html/calendario_data.php
ls -l /home/USUARIO/public_html/noticias_data.php
```

Ajusta `sitio.directorio_datos` hasta que los cinco archivos sean legibles.

### 8.2 Ejecutar

```bash
php private/bin/sincronizar.php
```

Salida esperada la primera vez:

```text
Sincronización terminada: 19 intención(es) actualizada(s) y pendiente(s) de validación.
Detalle: 5 de 5 fuentes legibles; ... respuestas derivadas; 19 intención(es) actualizada(s).
Actualizados: 19
```

La segunda ejecución debe informar 0 actualizaciones. Si vuelve a actualizar los
mismos registros, revisa que los archivos no estén cambiando entre lecturas.

### 8.3 Revisar y validar

Los cambios de sincronización quedan con `validada = 0`. Entra al panel, revisa
las intenciones actualizadas y válidalas cuando el contenido sea correcto.

## 9. Integrar el widget en el sitio

### 9.1 Copiar el fragmento

En `footer.inc.php`, antes de `</body>`:

```php
<?php
$utypAsistenteBase = '/asistente';
$utypAsistenteAviso = '/asistente/privacidad.php';
require __DIR__ . '/asistente/widget.php';
```

Puedes partir de `integracion/footer.inc.php.example`.

### 9.2 Verificar el sprite

El widget usa el sprite del sitio con estos identificadores:

- `i-huella` — lanzador y encabezado;
- `i-cerrar` — cierre;
- `i-flecha` — envío;
- `i-destello` — nueva conversación.

Si alguno no aparece, revisa que `icons.inc.php` esté incluido y que el sprite
tenga esos símbolos. No copies imágenes nuevas.

### 9.3 Verificar estilos

El widget usa los tokens del sitio:

```css
--naranja: #ff7a00;
--verde: #0c9b63;
--tinta: #071a16;
--espuma: #eef3f1;
--texto: #12211d;
--ff-display: "Fraunces", serif;
--ff-texto: "Archivo", sans-serif;
--sp-bloque: ...;
--e-suave: ...;
```

Si el sitio cambia un token, el widget lo hereda. No agregues una segunda paleta.

### 9.4 Orden de carga

1. Tipografías del sitio.
2. `estilos_base.inc.php`.
3. Contenido de la página.
4. `footer.inc.php` con `widget.php`.

El widget carga `widget.css` y `widget.js` desde `/asistente/`. No requiere
jQuery ni archivos externos.

### 9.5 Probar en el sitio real

- Abre la página de inicio y confirma que el botón aparece.
- Abre el widget con teclado (`Tab` + `Enter`).
- Envía una pregunta y revisa la fuente.
- Cierra con `Escape` y confirma que el foco vuelve al botón.
- Prueba en un teléfono: el panel debe ocupar la pantalla.
- Revisa la consola del navegador: no debe haber errores de CSP ni 404.

## 10. Programar tareas

### 10.1 Sincronización

```cron
0 * * * * /usr/bin/php /home/USUARIO/private/bin/sincronizar.php >> /home/USUARIO/private/logs/cron.log 2>&1
```

### 10.2 Purga de retención

```cron
30 3 * * * /usr/bin/php /home/USUARIO/private/bin/purgar.php >> /home/USUARIO/private/logs/cron.log 2>&1
```

La purga elimina preguntas sin respuesta de más de 90 días, eventos de más de 365
días y contadores de límite vencidos. Los valores se cambian en `privacidad`.

### 10.3 Respaldo

```cron
0 2 * * 0 mysqldump -u RESPALDO -p'CONTRASENA' --single-transaction \
  utypcb_asistente | gzip > /home/USUARIO/respaldos/asistente-$(date +\%F).sql.gz
```

Guarda los respaldos fuera de `public_html/` y protéjelos con permisos restringidos.

## 11. Lista de verificación de publicación

- [ ] `private/` está fuera de la carpeta web.
- [ ] `private/config.php` existe, tiene permisos 640 y no está en `public/`.
- [ ] La base tiene 8 tablas y 156 intenciones activas.
- [ ] `estado.php` responde 200 con `ok: true`.
- [ ] El panel permite iniciar sesión y cambiar la contraseña.
- [ ] La cuenta `oliver` tiene rol `superadmin` y ve la sección **Administradores**.
- [ ] Una cuenta de apoyo no ve esa sección y recibe 403 si intenta abrirla.
- [ ] El widget aparece en el sitio y responde con fuente.
- [ ] El resguardo canaliza correctamente.
- [ ] La sincronización es idempotente.
- [ ] La purga funciona.
- [ ] HTTPS está activo y la cookie usa `Secure`.
- [ ] `debug` está en `false`.
- [ ] No hay conversaciones ni datos de prueba en la base.
- [ ] No hay contraseñas de base de datos ni de infraestructura en archivos públicos.
- [ ] Los respaldos y el cron están configurados.

## 12. Actualizaciones

### 12.1 Código

1. Respalda base de datos y `private/config.php`.
2. Copia los nuevos archivos de `private/src/`, `public/asistente/` y `database/`.
3. No sobrescribas `private/config.php`.
4. Revisa si hay cambios de esquema y aplícalos. Para la versión con roles,
   ejecuta `database/migracion-2026-11-admin-roles.sql`.
5. Ejecuta la lista de verificación.

### 12.2 Contenido

- Usa el panel para cambios pequeños.
- Usa `importar.php --actualizar` para cambios masivos en la semilla.
- Ejecuta `evaluar.php` después de cambios importantes en el catálogo.

### 12.3 Reversión

1. Restaura la base desde el respaldo.
2. Restaura `private/` y `public/asistente/`.
3. Verifica el estado de la API y el panel.

## 13. Problemas frecuentes

| Problema | Causa | Solución |
| --- | --- | --- |
| `503 Servicio no disponible` | Falta configuración o tiene error | Copiar `config.example.php` y revisar sintaxis |
| `SQLSTATE[HY000] [1045]` | Usuario o contraseña incorrectos | Revisar `db.usuario` y `db.password` |
| `SQLSTATE[42S02]` | Tabla inexistente | Importar `database/schema.sql` |
| El panel redirige siempre al acceso | Cookie bloqueada o ruta distinta | Revisar HTTPS, ruta `/asistente/admin` y reloj del servidor |
| `429` en el widget | Límite alcanzado | Esperar o ajustar límites con criterio |
| Sincronización `sin_datos` | Ruta incorrecta o archivos ausentes | Revisar `sitio.directorio_datos` y permisos |
| El widget no carga estilos | URL base incorrecta | Revisar `$utypAsistenteBase` y la consola |
| Iconos vacíos | Sprite sin los símbolos | Verificar `icons.inc.php` |
| Error de cabeceras | Espacios o `?>` al final de archivos incluidos | Revisar `config.php` y el `footer.inc.php` |
| La sesión administrativa caduca rápido | `admin_idle_minutos` bajo | Ajustar el valor |

## 14. Notas de compatibilidad

- **PHP 7.4:** el código evita funciones y sintaxis de PHP 8. La ejecución de
  pruebas se hizo en PHP 8.5.12; antes de publicar en un hosting 7.4, realiza una
  prueba de humo con `php private/bin/probar-motor.php` y abre el panel.
- **MariaDB 10.11:** el esquema usa InnoDB, `utf8mb4`, `JSON` como `TEXT` y
  `ON UPDATE CURRENT_TIMESTAMP`, disponibles en 10.11. Las pruebas se ejecutaron
  en MariaDB 13.0.2.
- **Nginx:** configura `try_files` solo para archivos existentes y bloquea el
  acceso a `private/`. Los endpoints son archivos PHP directos, no requieren
  reescritura.
- **Apache:** no necesitas `.htaccess` adicional si `private/` está fuera del
  `DocumentRoot`. Si el hosting obliga a tener todo dentro, bloquea `private/`
  con una regla de servidor y nunca publiques `config.php`.

## 15. Desinstalación

1. Elimina la inclusión de `widget.php` en `footer.inc.php`.
2. Elimina la carpeta `public_html/asistente/`.
3. Elimina `/home/USUARIO/private/`.
4. Elimina la base `utypcb_asistente` y el usuario de base de datos.
5. Retira las tareas de cron.

Antes de eliminar, exporta la base si necesitas conservar métricas o contenido
editado.
