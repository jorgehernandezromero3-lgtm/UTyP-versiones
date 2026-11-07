# 05 · Plan de pruebas y evidencias

**Proyecto:** Asistente Coyote · UTyPCB
**Versión:** 1.0.0
**Fecha de ejecución:** 6 de noviembre de 2026
**Responsable de la ejecución:** equipo de desarrollo del proyecto.

## 1. Objetivo

Demostrar que el asistente cumple los requisitos funcionales y no funcionales de
la entrega, con evidencia reproducible y sin datos de producción. Las pruebas
cubren:

- precisión del motor con 100 preguntas no vistas;
- manejo de contexto conversacional;
- API, seguridad y privacidad;
- panel administrativo;
- sincronización y retención;
- accesibilidad;
- latencia;
- instalación limpia y compatibilidad.

## 2. Alcance y criterios

| Criterio | Meta | Resultado |
| --- | --- | --- |
| Precisión top-1 | ≥ 85 % | **100 % (100/100)** |
| Contexto conversacional | 5/5 | **5/5** |
| Latencia por solicitud | < 2000 ms | **p95 HTTP 55.5 ms** |
| Pruebas de seguridad | 37/37 | **37/37** |
| Accesibilidad de teclado | Foco contenido, Escape restaura | **14/14 y cierre correcto** |
| Instalación limpia | Importar, sincronizar, purgar | **Correcta** |
| Duplicados exactos entre prueba y semilla | 0 | **0** |

## 3. Entorno de pruebas

| Elemento | Valor |
| --- | --- |
| Sistema | Windows 11 x64 |
| PHP | 8.5.12 CLI y servidor integrado |
| MariaDB | 13.0.2 (cliente 10.11.16) |
| Navegador | Microsoft Edge en modo headless vía CDP |
| Base de trabajo | `utypcb_asistente` |
| Base de QA | `utypcb_asistente_qa` (aislada, creada desde cero) |
| Datos del sitio | Archivos de ejemplo en `tests/fixtures/site_data` |
| Fecha | 6 de noviembre de 2026 |

No se usaron datos personales reales, conversaciones de alumnos ni credenciales de
producción. La base QA se eliminó al terminar.

## 4. Tipos de prueba

### 4.1 Funcionales

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| F-01 | Abrir el widget desde el pie del sitio | Panel visible, foco dentro | Correcto |
| F-02 | Preguntar «¿Cuánto cuesta la ficha?» | Respuesta con fuente y enlace | Correcto |
| F-03 | Preguntar con errores de dedo | Respuesta correcta | Correcto |
| F-04 | Preguntar sin acentos | Respuesta correcta | Correcto |
| F-05 | Preguntar algo fuera de alcance | Resguardo con área de contacto | Correcto |
| F-06 | Reiniciar la conversación | Contexto borrado | Correcto |
| F-07 | Ver aviso de privacidad | Página accesible desde el widget | Correcto |

Evidencia: capturas `05-widget-respuesta.png`, `06-widget-fuera-alcance.png` y
`08-widget-movil.png`.

### 4.2 Motor y precisión

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| M-01 | 100 preguntas no vistas, top-1 | ≥ 85 % | 100/100 |
| M-02 | 5 seguimientos con contexto | 5/5 | 5/5 |
| M-03 | Preguntas por categoría | Todas ≥ 85 % | 100 % en las 12 categorías |
| M-04 | Sin duplicados exactos con la semilla | 0 | 0 |
| M-05 | Ambigüedad y umbral | No afirmar respuestas dudosas | Correcto |

Detalle por categoría:

| Categoría | Aciertos | Total |
| --- | ---: | ---: |
| Admisión y nuevo ingreso | 15 | 15 |
| Carreras y planes de estudio | 16 | 16 |
| Costos y pagos | 10 | 10 |
| Calendario escolar | 10 | 10 |
| Becas y apoyos | 6 | 6 |
| Trámites y servicios escolares | 9 | 9 |
| Servicios universitarios | 6 | 6 |
| Deportes | 7 | 7 |
| Noticias y eventos | 5 | 5 |
| Transparencia | 6 | 6 |
| Contacto y ubicación | 5 | 5 |
| La universidad | 5 | 5 |

Evidencia: `resultados-evaluacion-100.json` y `resultados-evaluacion-100.md`.

**Limitación:** las 100 preguntas fueron redactadas para el proyecto. Un 100 %
demuestra que el motor reconoce las formas previstas, no que cubra cualquier forma
futura. La mejora continua se apoya en la bandeja de preguntas sin respuesta.

### 4.3 Seguridad de la API

Se ejecutaron 37 comprobaciones. Todas pasaron.

| Grupo | Comprobaciones | Resultado |
| --- | --- | --- |
| Estado y cabeceras | 200, CSRF de 64 hex, límites, sugerencias, sin CORS, `nosniff`, `SAMEORIGIN`, `no-referrer`, `no-store`, cookies `HttpOnly`, `SameSite=Lax`, `Path=/` | 12/12 |
| Respuesta válida | 200, texto, fuente, confianza y latencia | 5/5 |
| CSRF | Sin token y con token inválido → 403 | 2/2 |
| Formato y tamaño | `Content-Type` no JSON → 415; cuerpo > 4 KB → 413; mensaje no textual → 422 | 3/3 |
| Validación de mensaje | 350 caracteres → 422; el error explica el máximo | 2/2 |
| Métodos | GET sobre chat → 405; PUT sobre estado → 405; OPTIONS → 405 | 3/3 |
| Origen | Origen externo → 403; preflight sin CORS | 2/2 |
| Límites | 429 por sesión y cabecera `Retry-After` | 2/2 |
| Resguardo | Pregunta fuera de alcance → 200 con canalización | 1/1 |
| Privacidad | Correo, teléfono, CURP y número largo redactados; pregunta original no almacenada | 5/5 |

Evidencia: `resultados-seguridad-37.json`.

### 4.4 Privacidad

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| P-01 | Pregunta con correo | Se guarda redactada | Correcto |
| P-02 | Pregunta con teléfono | Se guarda redactada | Correcto |
| P-03 | Pregunta con CURP | Se guarda redactada | Correcto |
| P-04 | Pregunta con número largo | Se guarda redactado | Correcto |
| P-05 | Texto original | No se almacena | Correcto |
| P-06 | IP | Solo hash HMAC | Correcto |
| P-07 | Eventos | Sin texto de conversación | Correcto |
| P-08 | Aviso de privacidad | Público y enlazado | Correcto |

### 4.5 Panel administrativo

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| A-01 | Acceso con credenciales válidas | Panel general | Correcto |
| A-02 | Acceso con credenciales inválidas | Error sin filtrar datos | Correcto |
| A-03 | Listado y búsqueda de intenciones | Resultados correctos | Correcto |
| A-04 | Editar intención y variantes | Guarda y reinicia validación | Correcto |
| A-05 | Activar, desactivar y validar | Estado actualizado | Correcto |
| A-06 | Bandeja de no respondidas | Muestra, permite nota y conversión | Correcto |
| A-07 | Sincronizar y purgar desde el panel | Resultado visible | Correcto |
| A-08 | Cambiar contraseña | Verifica actual y pide 12+ caracteres | Correcto |
| A-09 | Sesión con cuenta eliminada | Vuelve al acceso, sin error 500 | Correcto |
| A-10 | Cierre de sesión | Sesión terminada | Correcto |
| A-11 | Acceso con la cuenta permanente `oliver` | Entra como `superadmin` y ve **Administradores** | Correcto |
| A-12 | Alta de cuenta de apoyo desde el panel | Cuenta creada con rol `admin` y activa | Correcto |
| A-13 | Cuenta de apoyo abre el panel | No ve la sección y recibe 403 en `?r=admins` | Correcto |
| A-14 | Restablecer contraseña de una cuenta de apoyo | La nueva contraseña permite entrar | Correcto |
| A-15 | Desactivar y reactivar una cuenta de apoyo | Sin acceso mientras está inactiva; acceso restaurado | Correcto |
| A-16 | Salvaguardas de cuentas | Bloquea autodesactivación y la última cuenta principal activa | Correcto |

Evidencia: capturas `01-acceso-admin.png`, `02-panel-general.png`,
`03-intenciones.png`, `04-editar-intencion.png` y `07-panel-movil.png`. Los casos
A-11 a A-16 se ejecutaron manualmente el 6 de noviembre de 2026; las cuentas
temporales de prueba se eliminaron al terminar.

### 4.6 Sincronización

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| SY-01 | Leer las 5 fuentes | 5/5 legibles | Correcto |
| SY-02 | Primera ejecución | Actualiza y reinicia validación | 19 intenciones |
| SY-03 | Segunda ejecución | 0 cambios | Correcto |
| SY-04 | Fuente ausente | `sin_datos`, sin modificar la base | Correcto |
| SY-05 | Archivo fuera del directorio | Rechazado | Correcto |
| SY-06 | Archivo mayor a 2 MB | Rechazado | Correcto |

Evidencia: `pruebas-qa.txt`, sección 3.

### 4.7 Retención y purga

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| R-01 | Pregunta de más de 90 días | Eliminada | Correcto |
| R-02 | Evento de más de 365 días | Eliminado | Correcto |
| R-03 | Límite vencido | Eliminado | Correcto |
| R-04 | Registros vigentes | Conservados | Correcto |
| R-05 | Conteo final de vencidos | 0 | Correcto |

Evidencia: `pruebas-qa.txt`, sección 4.

### 4.8 Accesibilidad

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| AC-01 | Panel cerrado al inicio | `hidden`, lanzador `aria-expanded=false` | Correcto |
| AC-02 | Diálogo | `role=dialog`, etiqueta y `aria-modal` | Correcto |
| AC-03 | Foco al abrir | Dentro del panel | Correcto |
| AC-04 | Tabulación | 14/14 sin fugas | Correcto |
| AC-05 | Escape | Cierra y devuelve el foco | Correcto |
| AC-06 | Región de mensajes | `aria-live=polite` | Correcto |
| AC-07 | Campo de texto | Etiqueta y descripción asociadas | Correcto |
| AC-08 | Movimiento reducido | Animación y transición casi nulas | Correcto |

Evidencia: `accesibilidad.txt`.

### 4.9 Rendimiento

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| REND-01 | 100 preguntas en proceso | < 2000 ms | Promedio 5.3 ms; p95 8.6 ms |
| REND-02 | Primera respuesta en frío | < 2000 ms | 29.0 ms |
| REND-03 | 15 solicitudes HTTP | < 2000 ms | Promedio 44.8 ms; p95 55.5 ms; máximo 66 ms |
| REND-04 | Códigos HTTP | Todos 200 | Correcto |

Evidencia: `resultados-latencia-15.json` y `resultados-evaluacion-100.json`.

**Limitación:** no se ejecutó una prueba de carga con cientos de usuarios
simultáneos. El volumen de 15 solicitudes valida el tiempo de respuesta unitario,
no la capacidad máxima del hosting.

### 4.10 Instalación limpia

| ID | Caso | Resultado esperado | Resultado |
| --- | --- | --- | --- |
| INST-01 | Crear base e importar esquema | 8 tablas | Correcto |
| INST-02 | Validar semilla | 156 intenciones, 780 variantes, 5 áreas | Correcto |
| INST-03 | Importar | 156 nuevas, 780 variantes | Correcto |
| INST-04 | Evaluar | 100/100 | Correcto |
| INST-05 | Sincronizar | 19 y luego 0 | Correcto |
| INST-06 | Purgar | Vencidos eliminados | Correcto |
| INST-07 | Latencia HTTP | < 2000 ms | Correcto |

Evidencia: `pruebas-qa.txt` y `compatibilidad-php-mariadb.md`.

### 4.11 Compatibilidad

| ID | Caso | Resultado |
| --- | --- | --- |
| COMP-01 | `php -l` en 64 archivos | 0 errores |
| COMP-02 | Búsqueda de construcciones PHP 8 | 0 coincidencias |
| COMP-03 | Esquema y consultas en MariaDB | Compatible con 10.11 |

**Limitación:** no se dispone de un binario PHP 7.4 ni de una instancia MariaDB
10.11 real. La verificación de PHP 7.4 es estática; la de MariaDB se hizo en
13.0.2. Debe hacerse una prueba de humo en el hosting definitivo.

## 5. Evidencias

Carpeta `documentacion/evidencias/`:

| Archivo | Contenido |
| --- | --- |
| `01-acceso-admin.png` | Formulario de acceso |
| `02-panel-general.png` | Panel general en base limpia |
| `03-intenciones.png` | Listado de intenciones |
| `04-editar-intencion.png` | Formulario de edición |
| `05-widget-respuesta.png` | Respuesta con fuente |
| `06-widget-fuera-alcance.png` | Resguardo con contacto |
| `07-panel-movil.png` | Panel en móvil |
| `08-widget-movil.png` | Widget en móvil |
| `resultados-evaluacion-100.json` | Resultado completo de las 100 preguntas |
| `resultados-evaluacion-100.md` | Resumen legible de la evaluación |
| `resultados-seguridad-37.json` | 37 comprobaciones de seguridad |
| `resultados-latencia-15.json` | 15 muestras de latencia HTTP |
| `accesibilidad.txt` | Comprobaciones de teclado, ARIA y movimiento |
| `pruebas-qa.txt` | Instalación limpia, sincronización y purga |
| `compatibilidad-php-mariadb.md` | Revisión de compatibilidad |
| `LEEME.md` | Índice y notas de las evidencias |

## 6. Cómo reproducir

### 6.1 Evaluación del motor

```bash
php private/bin/evaluar.php
```

Requiere la base importada. Genera los resultados en `tests/resultados/`.

### 6.2 Seguridad

La suite se ejecutó contra un servidor local con la API publicada. Para repetirla:

1. Inicia el servidor: `php -S 127.0.0.1:8098 -t public`.
2. Ejecuta la suite interna usada durante el desarrollo.
3. Verifica que las 37 comprobaciones pasen y que no existan cabeceras CORS.

La suite no se incluye en la entrega para no publicar rutas internas de prueba; los
resultados quedan en `resultados-seguridad-37.json`.

### 6.3 Accesibilidad

1. Abre `demo.php` en un navegador.
2. Recorre el widget solo con `Tab`, `Shift+Tab`, `Enter` y `Escape`.
3. Verifica el foco y el cierre.
4. Activa «reducir movimiento» en el sistema y confirma que no hay animaciones
   largas.

### 6.4 Sincronización y purga

```bash
php private/bin/sincronizar.php
php private/bin/sincronizar.php
php private/bin/purgar.php
```

La segunda sincronización debe informar 0 cambios.

## 7. Incidencias encontradas y corregidas

| Incidencia | Causa | Corrección | Verificación |
| --- | --- | --- | --- |
| Error 500 al abrir el acceso con una sesión que apuntaba a una cuenta eliminada | La sesión se destruía y se intentaba generar CSRF sin sesión activa | Reiniciar la sesión administrativa en `renderLogin()` | Acceso y panel funcionan |
| El subtítulo del widget se partía en móvil | Falta de control de ancho | Tamaño y `white-space` en <= 560 px | Captura 08 |
| La navegación del panel se cortaba en móvil | `overflow` horizontal | Permitir salto de línea en <= 480 px | Captura 07 |
| Pregunta de prueba duplicada con una variante de la semilla | Redacción repetida | Se reemplazó por otra pregunta | 0 duplicados exactos |
| La purga no eliminaba un evento de prueba | Fecha dentro de la retención | Se ajustó la fecha y se repitió | 0 vencidos |

## 8. Conclusión

La entrega cumple los criterios acordados: 100/100 en el conjunto de evaluación,
5/5 en contexto, 37/37 en seguridad, accesibilidad de teclado completa y latencia
muy por debajo de 2 segundos. La gestión de cuentas administrativas se verificó de
extremo a extremo: alta de una cuenta de apoyo, acceso limitado, restablecimiento
de contraseña, desactivación y salvaguardas contra la autodesactivación y la
pérdida de la última cuenta principal. Las limitaciones declaradas (preguntas
diseñadas para el proyecto, PHP 7.4 sin ejecución real y MariaDB 10.11 sin
instancia propia) están documentadas para que el responsable técnico las cierre en
el entorno de producción.
