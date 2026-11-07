# Evidencias de prueba

Esta carpeta reúne las capturas y salidas que respaldan el plan de pruebas.
Los scripts temporales con los que se generaron no forman parte de la entrega.

| Archivo | Qué demuestra |
| --- | --- |
| `01-acceso-admin.png` | Formulario de acceso del panel, sin credenciales visibles. |
| `02-panel-general.png` | Panel limpio: 156 intenciones, 780 variantes, 0 pendientes. |
| `03-intenciones.png` | Listado y filtros de la base de conocimiento. |
| `04-editar-intencion.png` | Formulario con respuesta, fuente y cinco variantes. |
| `05-widget-respuesta.png` | Respuesta con fuente oficial en el widget público. |
| `06-widget-fuera-alcance.png` | Respuesta de resguardo con el área y contacto correctos. |
| `07-panel-movil.png` | Panel administrativo a 390 px, sin desbordamiento de página. |
| `08-widget-movil.png` | Widget a pantalla completa en móvil. |
| `resultados-evaluacion-100.json` | Detalle de las 100 preguntas, aciertos y latencias. |
| `resultados-evaluacion-100.md` | Resumen legible de la evaluación. |
| `resultados-seguridad-37.json` | 37 comprobaciones HTTP de seguridad, todas correctas. |
| `resultados-latencia-15.json` | Muestras de latencia HTTP reales. |
| `accesibilidad.txt` | ARIA, foco, trampa de Tab, Escape y movimiento reducido. |
| `pruebas-qa.txt` | Instalación limpia, sincronización, purga y latencia. |
| `compatibilidad-php-mariadb.md` | Revisión estática de PHP 7.4 y del esquema MariaDB. |

## Nota sobre las capturas

Las capturas se tomaron en un servidor local con la base de datos limpia. La
cuenta administrativa usada para las capturas se eliminó después. No se
incluyen conversaciones reales de alumnos ni datos personales.
