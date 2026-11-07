# 07 · Aviso de privacidad y política de seguridad

**Proyecto:** Asistente Coyote · UTyPCB
**Versión:** 1.0
**Fecha:** 6 de noviembre de 2026
**Responsable:** Universidad Tecnológica y Politécnica de Coyuca de Benítez (UTyPCB)

Este documento reúne dos partes:

1. El **aviso de privacidad** que se publica en `/asistente/privacidad.php`.
2. La **política interna de seguridad** que aplica el equipo responsable del
   asistente.

La versión publicada en el sitio es la vigente para las personas usuarias. Este
documento conserva el mismo contenido y agrega los controles internos.

---

# Parte A · Aviso de privacidad

## A.1 Responsable del tratamiento

El responsable es la **Universidad Tecnológica y Politécnica de Coyuca de Benítez
(UTyPCB)**, con domicilio en C. Nueva Mercado Nuevo, Centro, 40977 Coyuca de
Benítez, Guerrero.

Para asuntos relacionados con este aviso, la unidad de contacto es el
**Departamento de Servicios Escolares**:

- Correo: `serviciosescolares@utypcb.edu.mx`
- Teléfono: 781 100 3192
- Horario: días hábiles, en el horario publicado por la universidad.

## A.2 Alcance

Este aviso aplica únicamente al **Asistente Coyote**, el chat informativo del sitio
institucional. No aplica a otros formularios, trámites, plataformas escolares ni
servicios que tengan su propio aviso de privacidad.

## A.3 Qué datos se tratan

El asistente no solicita nombre, cuenta, contraseña ni identificación oficial.

| Dato | Tratamiento | Conservación |
| --- | --- | --- |
| Texto de la pregunta | Se procesa para identificar el tema y responder | No se guarda en bitácoras |
| Contexto de la conversación | Programa, tema e intención detectados; se guarda en la sesión | Hasta 30 minutos de inactividad |
| Dirección IP | Se usa para límites de uso; se guarda como hash HMAC | Según la ventana del límite |
| Métricas de uso | Intención, acierto, confianza y milisegundos | Hasta 365 días |
| Preguntas no respondidas | Texto redactado para revisión editorial | Hasta 90 días |
| Cuenta administrativa | Usuario, nombre, rol, hash de contraseña y último acceso | Mientras la cuenta esté activa |

**No proporciones datos personales en el chat.** El asistente es un canal
informativo, no un canal de trámites ni de atención de casos individuales. Si
escribes datos personales, los filtros de redacción los eliminan antes de guardar
una pregunta no respondida, pero no es posible garantizar la eliminación de todo
dato que decidas escribir.

## A.4 Finalidades

Los datos se utilizan para:

1. Resolver consultas sobre admisión, carreras, costos, fechas, trámites y
   servicios.
2. Mantener el hilo de la conversación durante la sesión activa.
3. Proteger el servicio mediante límites de uso y controles de seguridad.
4. Detectar temas que la base de conocimiento no cubre y mejorarla.
5. Medir desempeño: aciertos, confianza y tiempos de respuesta.

No se usan para publicidad, perfilado comercial, venta de datos ni decisiones
automatizadas que produzcan efectos jurídicos para la persona usuaria.

## A.5 Base y límites del tratamiento

El tratamiento se limita a la información necesaria para operar el asistente
informativo. No se elaboran perfiles de aspirantes, estudiantes o visitantes. Las
respuestas se basan en información pública del sitio y no sustituyen:

- la convocatoria oficial;
- una resolución académica o administrativa;
- la atención presencial de Servicios Escolares.

## A.6 Cookies y almacenamiento local

El asistente usa una **cookie de sesión estrictamente necesaria** para mantener el
hilo de la conversación y validar la seguridad de las solicitudes.

- Nombre: `UTYPCHAT`.
- Atributos: `HttpOnly`, `SameSite=Lax`, `Secure` cuando el sitio usa HTTPS.
- No se usan cookies de publicidad, análisis de terceros ni rastreadores.
- Al borrar la cookie se inicia una conversación nueva.

El panel administrativo usa una cookie independiente (`UTYPADM`), restringida a
`/asistente/admin`.

## A.7 Conservación

| Información | Plazo |
| --- | --- |
| Contexto de la conversación | Hasta 30 minutos de inactividad |
| Contadores de límite | Durante la ventana configurada |
| Preguntas no respondidas redactadas | Hasta 90 días |
| Métricas agregadas | Hasta 365 días |
| Cuenta administrativa | Mientras esté activa |
| Bitácoras técnicas | Según la operación del servidor |

Al cumplirse cada plazo, los registros se eliminan mediante el proceso de purga.
Las preguntas que se convierten en nuevos temas se incorporan sin texto literal,
sin IP y sin identificadores de la persona.

## A.8 Derechos de las personas

Puedes solicitar:

- **Acceso** a los datos que se conserven sobre tus conversaciones.
- **Rectificación** de datos inexactos.
- **Cancelación** de los datos cuando corresponda.
- **Oposición** al tratamiento.
- **Revocación** del consentimiento cuando proceda.

Envía tu solicitud al correo `serviciosescolares@utypcb.edu.mx` con:

1. Nombre completo.
2. Medio de contacto.
3. Descripción clara de la solicitud.
4. Fecha aproximada de la conversación, si la conoces.
5. Cualquier elemento que ayude a localizar el registro.

La universidad atenderá la solicitud conforme a los plazos y requisitos de la
normativa aplicable. Si consideras que la solicitud no fue atendida correctamente,
puedes acudir a la autoridad garante competente en materia de protección de datos
personales.

## A.9 Seguridad

Medidas aplicadas:

- HTTPS en producción.
- Credenciales y configuración fuera de la carpeta pública.
- Consultas preparadas y salida escapada.
- Panel con autenticación, CSRF y límite de intentos.
- Bitácoras sin texto de preguntas, correos, IP en claro ni tokens.
- Retención y purga automática.
- Acceso mínimo a la base de datos.

Ningún sistema es infalible. Si se detecta un incidente que afecte datos
personales, se aplicarán los procedimientos institucionales de respuesta y
notificación que correspondan.

## A.10 Cambios al aviso

Si cambian las finalidades, los plazos o los mecanismos de tratamiento, se
actualizará el aviso y la fecha de la versión visible. La versión vigente estará
siempre en `/asistente/privacidad.php`.

---

# Parte B · Política interna de seguridad

## B.1 Objetivo

Proteger la confidencialidad, integridad y disponibilidad del Asistente Coyote y
de los datos que trata, con controles proporcionales al riesgo y verificables.

## B.2 Alcance

Aplica a:

- el código y la configuración del asistente;
- la base de datos `utypcb_asistente`;
- el panel administrativo;
- las bitácoras;
- los respaldos;
- las personas que tienen acceso a cualquiera de los anteriores.

## B.3 Roles y responsabilidades

| Rol | Responsabilidad |
| --- | --- |
| Responsable del servicio | Aprueba contenidos, políticas y cambios mayores |
| Administrador del sistema | Instala, actualiza, respalda y monitorea la infraestructura |
| Administrador de contenido | Crea, corrige y valida intenciones |
| Responsable de privacidad | Atiende solicitudes de derechos y revisa el aviso |
| Áreas responsables | Confirman la información de su competencia |

Los privilegios se asignan por función. Una persona no debe acumular permisos de
infraestructura y de edición de contenido sin justificación.

## B.4 Inventario de datos

| Categoría | Dónde vive | Sensibilidad |
| --- | --- | --- |
| Preguntas redactadas | `asistente_unanswered` | Media |
| Métricas agregadas | `asistente_events` | Baja |
| Hashes de IP | `asistente_unanswered`, `asistente_rate_limits` | Media |
| Cuentas administrativas | `asistente_admin_users` | Alta |
| Configuración y credenciales | `private/config.php` | Crítica |
| Bitácoras | `private/logs/` | Media |
| Respaldos | Fuera de `public_html/` | Alta |

No se almacenan conversaciones completas, datos de tarjetas, documentos de
identidad ni expedientes académicos.

## B.5 Control de acceso

### Cuentas administrativas

- La cuenta principal se crea por consola; las cuentas de apoyo se crean desde la
  sección **Administradores**. No hay autorregistro.
- Dos roles: `superadmin` (cuenta principal, gestiona cuentas) y `admin` (apoyo,
  solo contenido).
- La autorización por rol se valida en el servidor; una cuenta de apoyo recibe
  `403` en las rutas de gestión aunque escriba la dirección directamente.
- Contraseña de 12 a 200 caracteres, almacenada con `password_hash`.
- Límite de 5 intentos por 15 minutos.
- Sesión con `HttpOnly`, `SameSite=Lax`, ruta restringida e inactividad de 30
  minutos.
- CSRF en todas las acciones POST.
- Salvaguardas: no se puede desactivar la cuenta propia ni la última cuenta
  principal activa.
- Auditoría de altas, bajas, restablecimientos y cambios de contraseña.

### Base de datos

- Usuario dedicado con permisos `SELECT`, `INSERT`, `UPDATE` y `DELETE` sobre la
  base del asistente.
- Sin permisos globales ni `DROP`.
- Credenciales fuera de `public/`.
- Conexiones desde `127.0.0.1` cuando la base vive en el mismo servidor.

### Servidor

- Acceso SSH con llave, no con contraseña.
- Usuarios nominales; prohibido compartir cuentas.
- `private/` fuera del `DocumentRoot`.
- Permisos 750 para directorios y 640 para configuración.
- Actualizaciones de seguridad del sistema operativo y PHP.

## B.6 Controles técnicos

| Control | Implementación | Verificación |
| --- | --- | --- |
| Inyección SQL | PDO preparado, sin concatenación | Revisión de código |
| XSS | Escape en vistas, `textContent` en JS | Pruebas de seguridad |
| CSRF | Token por sesión y `hash_equals` | 37/37 |
| Origen | Validación de `Origin`/`Referer` | 37/37 |
| CORS | Sin cabeceras abiertas | 37/37 |
| Fuerza bruta | Límite de acceso y de chat | 37/37 |
| Escalada de privilegios | Verificación de rol en el servidor; cuentas de apoyo con 403 | Pruebas A-11 a A-16 |
| Carga | Cuerpo máximo de 4 KB y mensaje de 300 caracteres | 37/37 |
| Sesiones | Cookies seguras y modo estricto | 37/37 |
| Cabeceras | `nosniff`, `SAMEORIGIN`, `no-referrer`, CSP | 37/37 |
| Privacidad | Redacción, hash de IP y retención | 37/37 |
| Bitácoras | Claves sensibles omitidas | Revisión de código |
| Secretos | Configuración fuera de `public/` | Lista de publicación |

## B.7 Gestión de secretos

- `private/config.php` no se versiona ni se comparte.
- La contraseña de la base y la sal de IP son únicas por entorno.
- La cuenta principal `oliver` recibe una contraseña inicial documentada en la
  entrega; debe cambiarse en el primer acceso y no reutilizarse en otros
  servicios.
- No se envían secretos por correo, chat ni capturas.
- Si un secreto se expone, se rota de inmediato:
  1. cambiar la contraseña de la base;
  2. generar una nueva sal;
  3. actualizar `config.php`;
  4. reiniciar PHP-FPM;
  5. revisar bitácoras.

Cambiar la sal invalida los hashes de IP anteriores; no es necesario conservarlos.

## B.8 Respaldos

- Frecuencia: semanal como mínimo; diaria si hay cambios frecuentes.
- Contenido: base de datos, `private/config.php` y bitácoras.
- Ubicación: fuera de `public_html/`, con permisos restringidos.
- Cifrado: obligatorio si el respaldo sale del servidor.
- Prueba de restauración: al menos una vez por trimestre.
- Retención: según la política institucional; no conservar indefinidamente.

## B.9 Bitácoras y monitoreo

Se revisan semanalmente:

- errores 500 y 503;
- intentos de acceso fallidos;
- picos de 429;
- sincronizaciones con error;
- crecimiento anormal de la bandeja;
- altas, bajas y restablecimientos de cuentas administrativas;
- accesos administrativos fuera de horario.

Las bitácoras no deben contener:

- contraseñas;
- tokens CSRF;
- texto completo de conversaciones;
- IP en claro;
- datos personales de alumnos.

## B.10 Gestión de incidentes

### Clasificación

| Nivel | Ejemplo | Respuesta |
| --- | --- | --- |
| Bajo | Error de contenido, enlace roto | Corregir en el ciclo normal |
| Medio | Caída del servicio, límite mal configurado | Atender en el mismo día |
| Alto | Acceso no autorizado, fuga de credenciales | Atención inmediata |
| Crítico | Exposición de datos personales | Activación del protocolo institucional |

### Pasos

1. **Detectar y registrar:** fecha, hora, síntoma y sistemas afectados.
2. **Contener:** desactivar el acceso comprometido, rotar credenciales, aislar el
   servicio si es necesario.
3. **Evaluar:** qué datos pudieron verse afectados y a cuántas personas.
4. **Erradicar:** corregir la causa raíz.
5. **Recuperar:** restaurar desde un respaldo verificado.
6. **Notificar:** informar al responsable de privacidad y, si corresponde, a las
   personas afectadas y a la autoridad.
7. **Aprender:** documentar la causa, la corrección y las medidas preventivas.

No se debe ocultar un incidente ni borrar evidencia para “limpiar” la situación.

## B.11 Desarrollo seguro

- Revisión de código antes de publicar cambios.
- `php -l` sobre todos los archivos modificados.
- Pruebas de la suite de seguridad y de la evaluación de 100 preguntas.
- Validación de entradas en el servidor, no solo en el navegador.
- Escape de toda salida.
- Consultas preparadas.
- Sin dependencias externas innecesarias.
- Sin datos reales en pruebas.
- Sin credenciales en el repositorio.

## B.12 Proveedores y terceros

El asistente no envía datos a servicios de terceros:

- no usa LLM externo;
- no usa CDN;
- no usa analítica de terceros;
- no usa fuentes externas en el widget;
- no comparte datos con anunciantes.

Si en el futuro se incorpora un proveedor, se requiere evaluación de privacidad,
contrato de tratamiento y actualización del aviso.

## B.13 Retención y eliminación

| Dato | Plazo | Método |
| --- | --- | --- |
| Contexto | 30 minutos | Expiración de sesión |
| Preguntas sin respuesta | 90 días | Purga |
| Eventos | 365 días | Purga |
| Límites vencidos | Al vencer | Purga |
| Cuentas inactivas | Según política institucional | Baja administrativa |
| Respaldos | Según política institucional | Destrucción segura |

La purga se ejecuta con `private/bin/purgar.php` y queda registrada en auditoría.

## B.14 Revisión y mejora

- Revisión del aviso de privacidad: al menos una vez al año o cuando cambie el
  tratamiento.
- Revisión de accesos: cada trimestre.
- Prueba de restauración: cada trimestre.
- Revisión de dependencias y versiones: cada semestre.
- Actualización de la evaluación de seguridad: con cada cambio mayor.

## B.15 Contacto de seguridad

Para reportar una vulnerabilidad o un incidente:

- Correo: `serviciosescolares@utypcb.edu.mx`
- Asunto sugerido: `Seguridad · Asistente Coyote`
- Incluye: descripción, pasos para reproducir, impacto estimado y medio de
  contacto.

No publiques detalles de una vulnerabilidad antes de que el equipo pueda
corregirla.

## B.16 Aceptación

Esta política entra en vigor en la fecha de publicación de la versión 1.0. Su
cumplimiento es obligatorio para el personal que opera, administra o mantiene el
Asistente Coyote.
