# 01 · Requisitos y alcance

**Proyecto:** Asistente Coyote · UTyPCB
**Versión:** 1.0.0
**Fecha:** 6 de noviembre de 2026
**Responsable:** Universidad Tecnológica y Politécnica de Coyuca de Benítez (UTyPCB)

## 1. Resumen ejecutivo

El Asistente Coyote es un chat informativo para el sitio de la UTyPCB. Responde
preguntas sobre admisión, carreras, costos, calendario, trámites, becas, servicios,
deportes, noticias, transparencia y contacto usando una base de conocimiento
institucional revisable. Cada respuesta informativa incluye la fuente oficial de
donde proviene; cuando no hay un dato verificado, el asistente lo dice y canaliza
a la persona con el área responsable.

La solución está construida con **PHP 7.4/8.x y MariaDB 10.11**, sin Python, Node,
SQLite, scraper ni modelos de lenguaje. El motor es determinista: la misma pregunta,
con el mismo contexto, produce la misma respuesta. El widget se integra desde
`footer.inc.php` y reutiliza los tokens de diseño, las tipografías y el sprite de
iconos del sitio.

## 2. Objetivo

Entregar un asistente virtual listo para publicarse que:

1. Resuelva al menos el 85 % de un conjunto de 100 preguntas no vistas en la
   primera coincidencia.
2. Responda en menos de 2 segundos por solicitud.
3. Permita que personal de la universidad revise, corrija y amplíe la información
   sin programar.
4. Mantenga separadas las credenciales del código público y trate los datos de las
   conversaciones con criterios de privacidad.
5. Se integre visualmente al sitio actual sin copiar sus imágenes ni alterar sus
   estilos base.

## 3. Contexto y actores

| Actor | Necesidad | Cómo lo cubre el sistema |
| --- | --- | --- |
| Aspirante | Conocer oferta, costos, fechas y requisitos | Widget público con respuestas y fuentes |
| Estudiante | Consultar trámites, servicios, becas y calendario | Widget público y preguntas sugeridas |
| Familiar o visitante | Ubicación, contacto y noticias | Widget público y respuestas de canalización |
| Personal de Servicios Escolares | Corregir respuestas y validar datos | Panel administrativo con CRUD |
| Área de comunicación | Revisar preguntas sin respuesta | Bandeja de revisión con datos redactados |
| Cuenta principal (Ing. Oliver) | Administrar el contenido y dar acceso a apoyos | Panel completo y sección **Administradores** |
| Cuentas de apoyo | Ayudar a revisar el panel del chat | Cuenta `admin` con acceso al contenido, sin gestión de cuentas |
| Responsable técnico | Instalar, actualizar y respaldar | Guía de instalación y comandos de consola |
| Administrador del sitio | Publicar el widget | Fragmento para `footer.inc.php` |

## 4. Requisitos funcionales

| ID | Requisito | Implementación | Evidencia |
| --- | --- | --- | --- |
| RF-01 | Chat web integrado al sitio | Widget flotante accesible en `widget.php` | Capturas 05 y 06 |
| RF-02 | Base de conocimiento amplia | 156 intenciones y 780 variantes | `04-base-de-conocimiento.csv` |
| RF-03 | Exactamente cinco variantes base por intención | Validación de la semilla y del panel | Importación QA |
| RF-04 | Entender lenguaje coloquial, sin acentos y con errores leves | Normalización, sinónimos, tallo, Levenshtein y trigramas | Evaluación 100/100 |
| RF-05 | Mantener contexto conversacional | Programa, tema, intención e historial en sesión | Contexto 5/5 |
| RF-06 | Responder con fuente oficial verificable | `fuente_titulo`, `fuente_url` y enlaces adicionales | Captura 05 |
| RF-07 | Decir cuando no sabe y canalizar | Respuesta de resguardo con área, correo, teléfono y URL | Captura 06 |
| RF-08 | Administrar la base desde un panel | CRUD, activar, desactivar y validar | Capturas 03 y 04 |
| RF-09 | Revisar preguntas no respondidas | Bandeja, nota, revisión y conversión a intención | Manual del administrador |
| RF-10 | Sincronizar datos publicados del sitio | Lectura de archivos PHP configurados | Pruebas QA: 19 → 0 |
| RF-11 | Aplicar retención de datos | Purga de 90 y 365 días y limpieza de límites | Pruebas QA de purga |
| RF-12 | Aviso de privacidad público | Página `privacidad.php` | Documento 07 |
| RF-13 | Accesible con teclado y lectores de pantalla | Diálogo ARIA, foco, Tab, Escape y movimiento reducido | `accesibilidad.txt` |
| RF-14 | Responsive en móvil y escritorio | Diseño fluido y panel adaptado | Capturas 02, 07 y 08 |
| RF-15 | Documentación de entrega | Siete documentos y README | Carpeta `documentacion/` |
| RF-16 | Gestionar cuentas administrativas | Cuenta principal `oliver`; alta, baja y restablecimiento de cuentas de apoyo | Pruebas A-11 a A-15 |

### Cobertura temática

| Categoría | Intenciones |
| --- | ---: |
| Admisión y nuevo ingreso | 22 |
| Carreras y planes de estudio | 44 |
| Costos y pagos | 12 |
| Calendario escolar | 14 |
| Becas y apoyos | 6 |
| Trámites y servicios escolares | 13 |
| Servicios universitarios | 6 |
| Deportes | 10 |
| Noticias y eventos | 6 |
| Transparencia | 8 |
| Contacto y ubicación | 8 |
| La universidad | 7 |
| **Total** | **156** |

## 5. Requisitos no funcionales

| ID | Requisito | Meta | Resultado medido |
| --- | --- | --- | --- |
| RNF-01 | Precisión top-1 | ≥ 85 % | 100/100 (100 %) |
| RNF-02 | Latencia por respuesta | < 2 000 ms | 44.8 ms promedio; 66.0 ms máximo HTTP |
| RNF-03 | Compatibilidad de lenguaje | PHP 7.4 y 8.x | Sintaxis y construcciones revisadas; ejecución local en PHP 8.5 |
| RNF-04 | Base de datos | MariaDB 10.11 | Probado en MariaDB 13.0.2; esquema compatible con 10.11 |
| RNF-05 | Dependencias de frontend | Sin jQuery, Bootstrap ni Tailwind | Solo CSS y JavaScript propios |
| RNF-06 | Seguridad | Sin CORS abierto, CSRF, origen, límites y PDO | 37/37 comprobaciones HTTP |
| RNF-07 | Privacidad | Redacción y retención declarada | Correo, teléfono, CURP y números redactados |
| RNF-08 | Mantenibilidad | Configuración fuera de lo público y semilla versionada | `private/config.php`, `database/seed/` |
| RNF-09 | Diseño | Respetar paleta, tipografías e iconos del sitio | Tokens `--naranja`, `--verde`, `--tinta`, `--espuma` y sprite `#i-*` |
| RNF-10 | Accesibilidad | WCAG orientativo AA y navegación por teclado | 14/14 Tab dentro del diálogo, Escape restaura foco |

## 6. Alcance incluido

- Widget público y API interna del asistente.
- Base de conocimiento con 156 intenciones y 780 variantes.
- Motor determinista de coincidencia y contexto.
- Panel administrativo con acceso protegido.
- Sincronización con archivos de datos del sitio.
- Purga automática y manual de datos vencidos.
- Aviso de privacidad público.
- Página de demostración local y documentación completa.

## 7. Fuera de alcance

- Sustituir la convocatoria oficial, una resolución académica o la atención
  presencial de Servicios Escolares.
- Recibir solicitudes, documentos, pagos o trámites individuales.
- Usar modelos de lenguaje o servicios de IA externos.
- Hacer scraping del sitio o descargar sus imágenes.
- Modificar `header.inc.php`, `estilos_base.inc.php` o el diseño general del sitio.
- Administrar otros formularios o servicios de la universidad.
- Enviar correos, mensajes de WhatsApp o notificaciones push.
- Analítica de terceros, publicidad o perfilado comercial.

## 8. Criterios de aceptación

| Criterio | Umbral | Resultado | Estado |
| --- | --- | --- | --- |
| Precisión en 100 preguntas no vistas | ≥ 85 % | 100 % | Cumple |
| Preguntas idénticas a una variante | 0 | 0 | Cumple |
| Respuesta con fuente en temas informativos | 100 % | 100 % de las intenciones tienen fuente | Cumple |
| Respuesta de resguardo con contacto | 100 % de los temas mapeados | Verificado con caso fuera de alcance | Cumple |
| Latencia HTTP promedio | < 2 000 ms | 44.8 ms | Cumple |
| Cinco variantes base por intención | 156 × 5 | 780 | Cumple |
| Seguridad HTTP | Sin fallos | 37/37 | Cumple |
| Foco atrapado en el diálogo | 14/14 Tab | 14/14 | Cumple |
| Base limpia de datos de prueba | 0 conversaciones | 0 | Cumple |
| Gestión de cuentas | Cuenta principal ve y usa **Administradores**; cuenta de apoyo recibe 403 | Verificado con cuenta temporal | Cumple |

## 9. Supuestos

- El sitio institucional conserva los archivos `calendario_data.php`,
  `noticias_data.php`, `carreras_data.php`, `deportes_data.php` y
  `tripticos_data.php` con las variables documentadas.
- El servidor permite leer los archivos del sitio desde PHP y ejecutar tareas
  programadas (cron) para sincronización y purga.
- La universidad revisará y validará el contenido editorial antes de considerarlo
  definitivo.
- El aviso de privacidad será revisado por la instancia jurídica correspondiente
  antes de su publicación.

## 10. Limitaciones conocidas

1. **Cobertura del lenguaje.** El 100 % se obtuvo sobre 100 preguntas de prueba
   diseñadas para el proyecto. En producción aparecerán formas nuevas; la bandeja
   de preguntas sin respuesta y la revisión editorial son el mecanismo previsto
   para mejorarlas.
2. **Datos oficiales.** Las respuestas reflejan la información cargada en la
   semilla y la sincronizada desde el sitio. Si la universidad cambia una
   convocatoria sin actualizar sus archivos, la respuesta quedará desactualizada.
3. **PHP 7.4.** El código se revisó de forma estática y se ejecutó en PHP 8.5. No
   se dispone de un binario PHP 7.4 en el equipo de desarrollo; antes de publicar
   en un hosting 7.4 debe hacerse una prueba de humo en ese entorno.
4. **MariaDB.** Las pruebas se ejecutaron en MariaDB 13.0.2. El esquema y las
   consultas usan características disponibles en 10.11, pero no se ejecutó una
   instancia 10.11 real.
5. **Administración de cuentas.** La primera cuenta principal se crea por consola.
   Desde el panel, la cuenta principal puede crear cuentas de apoyo; no hay
   autorregistro ni recuperación de contraseña por correo.
6. **Sin LLM.** El asistente no redacta respuestas nuevas: responde con contenido
   editorial aprobado. Esto es deliberado para evitar invenciones y mantener la
   trazabilidad de la fuente.

## 11. Glosario

- **Intención:** pregunta principal con respuesta, fuente y variantes.
- **Variante:** forma distinta de escribir la misma pregunta.
- **Confianza:** puntaje de coincidencia entre 0 y 1.
- **Umbral:** confianza mínima (0.40) para responder una intención.
- **Resguardo:** respuesta que reconoce no tener un dato verificado y canaliza.
- **Redacción:** reemplazo de datos personales antes de guardar una pregunta.
- **Cuenta principal:** cuenta con rol `superadmin`; administra el contenido y las
  cuentas de apoyo.
- **Cuenta de apoyo:** cuenta con rol `admin`; revisa el panel sin gestionar
  cuentas.
- **Semilla:** archivos PHP versionados con el contenido inicial de la base.
- **Sincronización:** actualización de respuestas desde los archivos del sitio.
- **Purga:** eliminación programada de registros vencidos.
