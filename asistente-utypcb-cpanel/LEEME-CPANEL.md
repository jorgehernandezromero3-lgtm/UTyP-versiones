# Asistente Coyote · Instalación en cPanel

Este paquete ya viene con la estructura que espera el asistente:

```text
/home/USUARIO/
├── private/                 ← NO debe quedar dentro de public_html
├── public_html/
│   └── asistente/           ← archivos públicos del asistente
├── database/                ← esquema, semilla y migración
├── documentacion/           ← manuales y evidencias
├── tests/                   ← pruebas internas
├── integracion/             ← fragmento para el pie del sitio
├── README.md
└── LEEME-CPANEL.md
```

Sustituye `USUARIO` por el usuario real de cPanel y `SU-DOMINIO` por el dominio
del sitio.

## 1. Subir el paquete

1. En cPanel abre **Administrador de archivos**.
2. Entra a **/home/USUARIO** (Inicio). **No entres a `public_html` todavía.**
3. Sube `asistente-utypcb-cpanel.zip` y usa **Extraer** en esa carpeta.
4. Debes quedar con `private/` y `public_html/` al mismo nivel.

> Si el sitio ya tiene una carpeta `public_html/asistente`, haz un respaldo antes
> de extraer. El ZIP agregará y reemplazará solo los archivos del asistente.

## 2. Crear la base de datos

1. En cPanel abre **MySQL Databases**.
2. Crea una base, por ejemplo `utypcb_asistente`. cPanel le agrega tu prefijo:
   `USUARIO_utypcb_asistente`.
3. Crea un usuario con contraseña larga y única.
4. Agrega el usuario a la base con **ALL PRIVILEGES**.

Anota estos tres datos: nombre de la base, usuario y contraseña.

## 3. Importar las tablas

En **phpMyAdmin**, selecciona la base y entra a **Importar**:

- Instalación nueva: `database/schema.sql`.
- Instalación que ya tenía las tablas: `database/migracion-2026-11-admin-roles.sql`.

Debes ver estas 8 tablas:

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

## 4. Configurar la aplicación

1. En el Administrador de archivos entra a `/home/USUARIO/private/`.
2. Copia `config.example.php` y nómbralo `config.php`.
3. Edita `config.php` con estos valores:

| Clave | Qué poner |
| --- | --- |
| `db.dsn` | `mysql:host=localhost;port=3306;dbname=USUARIO_utypcb_asistente;charset=utf8mb4` |
| `db.usuario` | El usuario de la base creado en cPanel |
| `db.password` | La contraseña de ese usuario |
| `seguridad.ip_salt` | Una cadena aleatoria de 48 o más caracteres |
| `sitio.url` | `https://SU-DOMINIO` |
| `sitio.directorio_datos` | `/home/USUARIO/public_html` |
| `app.entorno` | `produccion` |
| `app.debug` | `false` |

Para generar la sal, abre la **Terminal** de cPanel y ejecuta:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Copia el resultado en `ip_salt`. No compartas ese valor.

## 5. Cargar el contenido

En la Terminal de cPanel:

```bash
cd /home/USUARIO
php private/bin/importar.php --dry-run
php private/bin/importar.php
```

La primera orden solo valida. La segunda carga las 156 intenciones y sus
variantes. Debe terminar sin errores.

## 6. Crear la cuenta de Oliver

**Opción A · Terminal (recomendada):**

```bash
cd /home/USUARIO
php private/bin/crear-admin.php --usuario=oliver --nombre="Ing. Oliver" --rol=superadmin
```

Cuando pida la contraseña, escribe `Oliver2026!!`. No queda en el historial del
shell.

**Opción B · Sin Terminal:**

1. En phpMyAdmin, con la base seleccionada, entra a **Importar**.
2. Importa `database/cuenta-oliver.sql`.
3. Ese archivo crea la cuenta solo si no existe.

La cuenta inicial es:

| Usuario | Contraseña inicial | Rol |
| --- | --- | --- |
| `oliver` | `Oliver2026!!` | `superadmin` |

Cambia la contraseña en el primer acceso desde **Mi cuenta**.

## 7. Integrar el chat en el sitio

Edita el pie del sitio, normalmente `public_html/footer.inc.php`, y agrega antes
de `</body>`:

```php
<?php
$utypAsistenteBase = '/asistente';
$utypAsistenteAviso = '/asistente/privacidad.php';
require __DIR__ . '/asistente/widget.php';
```

Si el pie está dentro de otra carpeta, ajusta la ruta del `require` para que
apunte a `public_html/asistente/widget.php`.

## 8. Probar

- Panel: `https://SU-DOMINIO/asistente/admin/`
- Chat dentro del sitio: abre cualquier página y pulsa el botón flotante.
- Demostración aislada: `https://SU-DOMINIO/asistente/demo.php`
- Estado de la API: `https://SU-DOMINIO/asistente/estado.php`

Entra al panel con `oliver` y la contraseña inicial.

## 9. Permisos recomendados

Desde el Administrador de archivos:

- `private/`: 750.
- `private/config.php`: 640.
- Carpetas públicas: 755.
- Archivos públicos: 644.

La carpeta `private/logs/` se crea sola cuando el asistente registra el primer
evento. Si el hosting no permite crearla, revisa los permisos de `private/`.

## 10. Tareas programadas (opcional)

En **Cron Jobs** puedes agregar:

```text
0 * * * * /usr/local/bin/php /home/USUARIO/private/bin/sincronizar.php
30 3 * * * /usr/local/bin/php /home/USUARIO/private/bin/purgar.php
```

Confirma la ruta real de PHP en cPanel con `which php` o desde **Select PHP
Version**. Si tu hosting usa otro binario, cámbialo.

## 11. Seguridad

- No muevas `private/` dentro de `public_html`.
- No subas `config.php` a repositorios, chats ni respaldos públicos.
- Usa HTTPS.
- Cambia la contraseña inicial de Oliver.
- Crea una cuenta individual por cada persona de apoyo y desactívala cuando
  termine.
- No publiques `documentacion/`, `tests/` ni `database/` dentro de la web.

## 12. Si algo falla

| Síntoma | Revisión |
| --- | --- |
| Error 500 en el panel | `config.php`, conexión a la base y permisos de `private/` |
| El panel vuelve al acceso | HTTPS, ruta de sesión `/asistente/admin` y cookies |
| El chat no aparece en el sitio | El `require` del widget en el pie y la ruta `/asistente` |
| La sincronización dice `sin datos` | `sitio.directorio_datos` debe apuntar a la carpeta real de los archivos del sitio |
| La cuenta de Oliver no existe | Ejecutar la opción A o importar `database/cuenta-oliver.sql` |

Si el sitio usa un subdominio o dominio adicional con un `DocumentRoot` distinto
de `public_html`, avisa al responsable técnico: los `require` de `chat.php`,
`estado.php` y `admin/index.php` suponen la estructura exacta de este paquete.
