# 06 · Manual del administrador

**Proyecto:** Asistente Coyote · UTyPCB
**Versión:** 1.0.0
**Fecha:** 6 de noviembre de 2026
**Audiencia:** personal de Servicios Escolares y comunicación que administra el contenido del asistente.

## 1. Qué puedes hacer en el panel

El panel administrativo te permite:

- revisar cómo está funcionando el asistente;
- crear y corregir preguntas y respuestas;
- agregar formas distintas de hacer la misma pregunta;
- validar la información publicada;
- revisar las preguntas que el asistente no pudo responder;
- sincronizar datos del sitio;
- aplicar la retención de datos;
- cambiar tu contraseña;
- registrar cuentas de apoyo para revisar el panel (solo la cuenta principal).

No necesitas conocimientos de programación. Los cambios se aplican de inmediato:
el asistente lee el catálogo cada vez que alguien pregunta.

## 2. Acceso

### 2.1 Dirección

Abre en el navegador:

```text
https://TU-DOMINIO/asistente/admin/
```

En pruebas locales puede ser:

```text
http://127.0.0.1:8099/asistente/admin/
```

### 2.2 Cuenta principal del ingeniero Oliver

La instalación incluye una cuenta permanente para el ingeniero Oliver con rol de
**cuenta principal**:

| Dato | Valor |
| --- | --- |
| Usuario | `oliver` |
| Contraseña inicial | `Oliver2026!!` |
| Rol | Cuenta principal |

Esta cuenta puede revisar todo el panel y, además, registrar o desactivar cuentas
de apoyo. Por seguridad, cambia la contraseña en el primer acceso desde **Mi
cuenta**. La contraseña se guarda cifrada en la base de datos: nadie puede
consultarla, solo restablecerla.

### 2.3 Iniciar sesión

1. Escribe tu **usuario**.
2. Escribe tu **contraseña**.
3. Pulsa **Entrar**.

Las cuentas se crean por consola o desde la sección **Administradores** de la
cuenta principal; no hay registro público. Si olvidaste tu contraseña, la cuenta
principal puede restablecerla. Si se perdió el acceso a la cuenta principal, el
responsable técnico debe restablecerlo por consola.

### 2.4 Sesión

- La sesión se cierra sola después de **30 minutos** sin actividad.
- Puedes cerrarla cuando quieras con **Cerrar sesión**.
- No compartas tu cuenta ni dejes el panel abierto en una computadora pública.
- El navegador no guarda tu contraseña si usas una computadora compartida.

### 2.5 Si no puedes entrar

| Situación | Qué hacer |
| --- | --- |
| «Usuario o contraseña incorrectos» | Revisa mayúsculas y Bloq Mayús; espera si agotaste los intentos |
| «Demasiados intentos» | Espera 15 minutos antes de volver a intentar |
| La página vuelve al acceso | Vuelve a iniciar sesión; la sesión pudo expirar |
| No ves el panel | Confirma con el responsable técnico que tu cuenta está activa |

## 3. Recorrido del panel

El menú lateral tiene cinco secciones para todas las cuentas. La cuenta principal
ve una sexta:

| Sección | Para qué sirve |
| --- | --- |
| **Panel general** | Resumen de intenciones, variantes, pendientes y precisión |
| **Intenciones** | Base de conocimiento: crear, buscar, editar y validar |
| **Sin respuesta** | Preguntas que el asistente no pudo resolver |
| **Sincronización** | Estado de los archivos del sitio, historial y purga |
| **Mi cuenta** | Datos de acceso y cambio de contraseña |
| **Administradores** | Solo cuenta principal: altas, bajas y contraseñas de apoyo |

En la parte superior siempre tienes los botones **Nueva intención** y **Revisar
pendientes**.

## 4. Panel general

### 4.1 Métricas

| Métrica | Qué significa |
| --- | --- |
| **Intenciones activas** | Preguntas publicadas que el asistente puede responder |
| **Variantes de lenguaje** | Formas distintas de escribir esas preguntas |
| **Sin respuesta** | Preguntas pendientes de revisar |
| **Precisión · 30 días** | Porcentaje de respuestas que el motor reconoció en el último mes |

### 4.2 Avance de validación

Muestra cuántas intenciones han sido revisadas y marcadas como validadas. Una
intención validada significa que una persona confirmó que la respuesta y la fuente
son correctas.

La sincronización con el sitio reinicia la validación de las intenciones que
cambian. Esto es intencional: el contenido nuevo debe revisarse antes de darlo por
bueno.

### 4.3 Última sincronización

Indica cuándo se ejecutó, cuántas intenciones se actualizaron y si hubo algún
problema. El enlace **Abrir sincronización** lleva al detalle.

### 4.4 Preguntas recientes sin respuesta

Lista las últimas preguntas que el asistente no pudo resolver. El enlace **Ver
todas** abre la bandeja completa.

## 5. Intenciones

Una **intención** es una pregunta principal con:

- una respuesta oficial;
- una fuente verificable;
- enlaces adicionales;
- palabras clave;
- cinco variantes base;
- variantes adicionales opcionales;
- categoría, área, programa y tema;
- estado de validación y publicación.

### 5.1 Listado y búsqueda

En **Intenciones** puedes:

- buscar por texto;
- filtrar por categoría;
- filtrar por estado (activas, inactivas, validadas, pendientes);
- abrir una intención para editarla;
- crear una nueva.

La tabla muestra la pregunta, la categoría, el área, el número de variantes y el
estado.

### 5.2 Crear una intención

1. Pulsa **Nueva intención**.
2. Completa la ficha.
3. Pulsa **Guardar**.
4. Revisa la vista previa y valida.

### 5.3 Campos de la ficha

#### Identificación

| Campo | Qué escribir | Ejemplo |
| --- | --- | --- |
| **Identificador** | Nombre corto, único, en minúsculas y con guiones | `admision-costo-ficha` |
| **Categoría** | Tema general | Admisión y nuevo ingreso |
| **Área responsable** | Quién responde si alguien necesita más ayuda | Servicios Escolares |
| **Pregunta principal** | La forma más clara y completa | ¿Cuánto cuesta la ficha de admisión? |

El identificador no debe cambiarse después de crear la intención: la
sincronización lo usa para encontrar la respuesta correcta.

#### Respuesta y fuente

| Campo | Qué escribir |
| --- | --- |
| **Respuesta** | Texto oficial, claro y completo. Entre 20 y 20,000 caracteres |
| **Título de la fuente** | Nombre de la página o documento oficial |
| **URL de la fuente** | Dirección completa que empiece con `https://` |
| **Enlaces adicionales** | Uno por línea, con formato `Título | https://...` |
| **Palabras clave** | Al menos dos, separadas por coma |

La respuesta debe poder entenderse sin abrir la fuente, pero la fuente debe
respaldarla. No escribas datos que no estén publicados oficialmente.

#### Variantes

Las **cinco variantes base** son obligatorias. Deben ser distintas entre sí y no
pueden repetirse en otra intención.

Ejemplo para «¿Cuánto cuesta la ficha de admisión?»:

```text
cuanto cuesta la ficha
precio de la ficha de admision
cuanto tengo que pagar para registrarme
costo del examen de admision
hay que pagar la ficha
```

Incluye:

- formas coloquiales («cuánto me sale»);
- sin acentos («cuanto cuesta»);
- errores leves de dedo («cuanto custa»);
- sinónimos («precio», «costo», «pagar»);
- la forma más directa.

No incluyas variantes que pertenezcan a otra intención: el sistema lo rechaza.

**Variantes adicionales** (opcional): hasta 10, una por línea. Se conservan junto
a las cinco base y también se validan contra todo el catálogo.

#### Contexto

| Campo | Uso |
| --- | --- |
| **Programa** | Si la respuesta es específica de una carrera |
| **Tema** | Agrupa la intención para el contexto y la canalización |
| **Prioridad** | 0 a 10; se usa para ordenar sugerencias y resolver empates |

La prioridad no debe usarse para “esconder” una respuesta incorrecta. Corrige el
contenido.

#### Estado

- **Activa:** si está marcada, el asistente puede usarla.
- **Validada:** se marca después de revisar la respuesta y la fuente.

### 5.4 Reglas de validación

El panel rechaza el guardado si:

- el identificador está repetido o tiene caracteres no permitidos;
- la pregunta mide menos de 5 o más de 255 caracteres;
- la respuesta mide menos de 20 caracteres;
- falta el título o la URL de la fuente;
- la URL no es absoluta o no es `http`/`https`;
- hay menos de dos palabras clave;
- no hay exactamente cinco variantes base;
- una variante está vacía, es muy corta o está repetida;
- una variante ya existe en otra intención.

Corrige el campo que indica el error y vuelve a guardar.

### 5.5 Editar una intención

1. Búscala en el listado.
2. Pulsa **Editar**.
3. Cambia lo necesario.
4. Guarda.
5. Valida de nuevo.

Al guardar se reinicia la validación, porque el contenido cambió.

### 5.6 Activar, desactivar, validar y eliminar

En la ficha de la intención:

- **Activar / Desactivar:** controla si el asistente la usa. Desactivar es la
  opción recomendada cuando una respuesta deja de aplicar.
- **Validar:** confirma que la revisaste.
- **Retirar validación:** si detectas un error.
- **Eliminar:** borra la intención y sus variantes. Úsalo solo para duplicados o
  contenido que nunca debió existir. Si hay dudas, desactiva.

### 5.7 Convertir una pregunta sin respuesta

Desde la bandeja puedes crear una intención a partir de una pregunta real. El
sistema conserva la relación y marca la pregunta como atendida. Completa la
respuesta, la fuente y las cinco variantes antes de guardar.

## 6. Bandeja de preguntas sin respuesta

### 6.1 Qué contiene

Solo preguntas que el motor no pudo resolver con confianza suficiente. Se guardan
**redactadas**: correos, teléfonos, CURP, URLs y números largos se reemplazan antes
de almacenarse. No se guarda la conversación completa.

### 6.2 Filtros

- **Pendientes:** aún no revisadas.
- **Revisadas:** ya atendidas.
- **Todas:** historial.

Puedes buscar por palabra clave.

### 6.3 Acciones

| Acción | Cuándo usarla |
| --- | --- |
| **Marcar revisada** | Ya evaluaste la pregunta |
| **Agregar nota** | Dejas contexto para otra persona |
| **Crear intención** | La pregunta merece una respuesta nueva |
| **Eliminar** | Es ruido, spam o un duplicado sin valor |

### 6.4 Cómo decidir

- Si la pregunta es frecuente y útil, crea una intención.
- Si es una variante de algo existente, agrega una variante a esa intención.
- Si es una queja o un dato personal, no la conviertas en contenido; elimínala.
- Si no tienes la información oficial, canalízala con el área responsable antes
  de inventar una respuesta.

## 7. Sincronización

### 7.1 Qué hace

Lee los archivos de datos del sitio y actualiza las respuestas de las intenciones
que dependen de ellos: noticias, deportes, calendario y carreras. No crea
intenciones nuevas ni modifica las variantes.

### 7.2 Estados de las fuentes

| Estado | Significado |
| --- | --- |
| **Legible** | El archivo existe, se puede leer y tiene tamaño válido |
| **No legible** | No existe, no hay permisos o supera 2 MB |
| **Sin datos** | Se leyó, pero no se reconocieron datos para actualizar |

### 7.3 Ejecutar

1. Entra a **Sincronización**.
2. Revisa el estado de los cinco archivos.
3. Pulsa **Sincronizar ahora**.
4. Lee el resultado: actualizados, sin cambios o error.

Si actualiza intenciones, aparecerán como pendientes de validación. Revísalas y
válidalas.

### 7.4 Frecuencia

- Manual: cuando el área de comunicación publique un cambio importante.
- Automática: el responsable técnico puede programarla cada hora.

No es necesario sincronizar cada vez que alguien entra al panel.

### 7.5 Purga

El botón **Purgar datos vencidos** elimina:

- preguntas sin respuesta de más de 90 días;
- eventos de más de 365 días;
- contadores de límite vencidos.

La acción queda registrada en la bitácora. No borra intenciones ni variantes.

## 8. Administradores

Esta sección solo aparece para la **cuenta principal**. Sirve para que el ingeniero
Oliver registre a las personas que lo apoyarán a revisar el panel del chat.

### 8.1 Cuenta principal y cuentas de apoyo

| Rol | Puede hacer | No puede hacer |
| --- | --- | --- |
| **Cuenta principal** | Todo lo del panel; registrar, desactivar y restablecer contraseñas de cuentas de apoyo | Desactivar su propia cuenta o dejar el sistema sin una cuenta principal activa |
| **Cuenta de apoyo** | Revisar preguntas, crear y editar intenciones, validar, sincronizar y cambiar su propia contraseña | Registrar o desactivar cuentas; cambiar contraseñas de otras personas |

Las cuentas de apoyo no ven la sección **Administradores** y, si intentan abrirla
por su dirección directa, el sistema les niega el acceso.

### 8.2 Registrar una cuenta de apoyo

1. Entra a **Administradores**.
2. En **Nueva cuenta**, escribe el nombre completo, el usuario y una contraseña
   inicial.
3. Confirma la contraseña y pulsa **Crear cuenta**.
4. Entrega el usuario y la contraseña inicial a la persona, por un medio seguro.
5. Pídele que cambie la contraseña en su primer acceso.

Reglas de la cuenta:

- **Usuario:** de 3 a 60 caracteres, en minúsculas, sin espacios; se permiten
  letras, números, punto, guion y guion bajo.
- **Nombre:** de 3 a 120 caracteres.
- **Contraseña inicial:** de 12 a 200 caracteres.
- El usuario no puede repetirse.

Las cuentas creadas desde el panel siempre son de apoyo. Para crear otra cuenta
principal se usa la consola del servidor (ver el manual técnico).

### 8.3 Restablecer una contraseña

1. Localiza la cuenta en la lista.
2. Pulsa **Restablecer contraseña**.
3. Escribe la nueva contraseña y confírmala.
4. Guarda y entrega la nueva contraseña por un medio seguro.

La persona deberá cambiarla al entrar. La cuenta principal gestiona su propia
contraseña en **Mi cuenta**.

### 8.4 Activar o desactivar una cuenta

- **Desactivar:** la persona ya no puede iniciar sesión, pero su historial se
  conserva. Úsalo cuando alguien deja de apoyar o si sospechas un uso indebido.
- **Activar:** devuelve el acceso a la cuenta.

No puedes desactivar tu propia cuenta ni la última cuenta principal activa. El
sistema lo impide y muestra un aviso.

### 8.5 Recomendaciones

- Crea una cuenta individual por persona; no compartas usuarios.
- Retira el acceso en cuanto la persona termine su apoyo.
- Revisa la lista de cuentas al menos una vez al mes.
- Si una cuenta de apoyo necesita más permisos, no intentes cambiar su rol: pide
  al responsable técnico que cree otra cuenta principal.

## 9. Mi cuenta

### 9.1 Datos

Muestra tu nombre, usuario y último acceso. La sesión se cierra por inactividad.

### 9.2 Cambiar contraseña

1. Entra a **Mi cuenta**.
2. Escribe tu contraseña actual.
3. Escribe la nueva (entre 12 y 200 caracteres).
4. Confirma la nueva.
5. Guarda.

La nueva contraseña debe ser distinta de la actual. Usa una contraseña única para
este panel y no la reutilices en otros servicios.

### 9.3 Recomendaciones

- Cambia la contraseña temporal en el primer acceso.
- No la compartas por chat ni por correo.
- Si sospechas que alguien más la conoce, cámbiala de inmediato.
- Cierra sesión al terminar.

## 10. Cómo escribir buenas respuestas

### 10.1 Respuesta

- Empieza con el dato concreto.
- Usa frases cortas.
- Incluye fechas, montos o requisitos solo si están publicados.
- Si hay condiciones, explícalas.
- No prometas resultados ni fechas que no estén confirmadas.
- No incluyas datos personales de alumnos ni de terceros.

### 10.2 Fuente

- Debe ser una página oficial de la UTyPCB.
- Debe abrir sin iniciar sesión.
- El título debe describir la página.
- Si la información está en un PDF, usa la URL del PDF y un título claro.

### 10.3 Variantes

Piensa en cómo pregunta una persona real:

- con prisa: «cuánto la ficha»;
- sin acentos: «admision 2026»;
- con dudas: «qué necesito para entrar»;
- con errores: «iscripcion»;
- con sinónimos: «colegiatura», «mensualidad».

No agregues variantes inventadas que no se parezcan a una pregunta real.

### 10.4 Cuándo crear una intención nueva

Crea una intención cuando:

- la pregunta no existe;
- la respuesta es distinta de las existentes;
- el área responsable es distinta;
- la pregunta se repite en la bandeja.

Si solo cambia la forma de preguntar, agrega una variante.

## 11. Privacidad y trato de datos

- El asistente no guarda conversaciones completas.
- Las preguntas sin respuesta se guardan redactadas.
- La IP se guarda como hash, no en claro.
- Los eventos son agregados: intención, acierto, confianza y tiempo.
- La retención es de 90 días para preguntas y 365 para eventos.
- Las cuentas administrativas son individuales y la contraseña se guarda cifrada.
- No copies preguntas con datos personales a documentos, correos o grupos.
- Si una pregunta contiene datos sensibles, elimínala de la bandeja.

El aviso público está en `/asistente/privacidad.php`.

## 12. Buenas prácticas de seguridad

- Usa una contraseña larga y única.
- No compartas tu usuario.
- Crea una cuenta individual por persona; no uses cuentas genéricas.
- Desactiva de inmediato las cuentas de apoyo que ya no se utilicen.
- No dejes la sesión abierta en equipos compartidos.
- No instales extensiones del navegador que lean formularios.
- No escribas credenciales en la base de conocimiento.
- No publiques capturas del panel con datos internos.
- Reporta cualquier acceso extraño al responsable técnico.

## 13. Preguntas frecuentes

**¿Por qué el asistente no responde una pregunta que sí existe?**
Puede faltar una variante, la pregunta puede ser ambigua o el umbral no alcanzó.
Agrega la forma de preguntar como variante y prueba de nuevo.

**¿Por qué responde algo parecido pero incorrecto?**
Revisa si dos intenciones comparten palabras clave o variantes. Corrige la
redacción o desactiva temporalmente la menos precisa.

**¿Por qué la respuesta cambió sola?**
La sincronización actualizó el contenido desde los archivos del sitio. Revisa la
intención y valídala si es correcta.

**¿Puedo borrar una intención?**
Sí, pero se pierde el historial de esa pregunta. Si solo dejará de aplicar,
desactívala.

**¿Cuántas variantes puedo tener?**
Cinco base obligatorias y hasta diez adicionales.

**¿Los cambios requieren reiniciar el servidor?**
No. El catálogo se lee en cada pregunta.

**¿Quién ve las preguntas sin respuesta?**
Solo las cuentas administrativas. Los visitantes nunca ven esa bandeja.

**¿Cómo doy acceso a otra persona para que me ayude a revisar el panel?**
Entra con la cuenta principal, abre **Administradores**, crea una cuenta de apoyo
individual y entrega la contraseña inicial por un medio seguro. La persona deberá
cambiarla al entrar.

**¿Por qué no veo la sección Administradores?**
Tu cuenta es de apoyo. Solo la cuenta principal puede registrar, desactivar o
restablecer cuentas.

**¿Puedo crear otra cuenta principal desde el panel?**
No. Las cuentas creadas desde el panel son siempre de apoyo. Para otra cuenta
principal, el responsable técnico debe usar la consola del servidor.

**¿El asistente puede inventar respuestas?**
No. Solo responde con intenciones cargadas. Si no encuentra una, canaliza con el
área responsable.

## 14. Problemas y soluciones

| Problema | Causa probable | Solución |
| --- | --- | --- |
| No guarda y marca un campo en rojo | Validación | Lee el mensaje y corrige ese campo |
| «Ya existe una intención con ese identificador» | Slug repetido | Usa otro identificador |
| «Una variante ya pertenece a…» | Variante duplicada | Cambia la variante o edita la otra intención |
| La sincronización dice «sin datos» | Ruta o permisos | Avisa al responsable técnico |
| La pregunta no aparece en el widget | Intención inactiva o sin validar | Actívala y prueba de nuevo |
| La sesión se cerró | Inactividad de 30 minutos | Vuelve a iniciar sesión |
| El panel se ve cortado en el teléfono | Navegador antiguo | Actualiza el navegador o usa uno moderno |
| No puedes cambiar la contraseña | La actual no coincide | Verifica y vuelve a intentar |
| No ves la sección **Administradores** | Tu cuenta es de apoyo | Pide a la cuenta principal que gestione el acceso |
| «Ese usuario ya existe» al crear una cuenta | Usuario repetido | Usa otro nombre de usuario |
| No puedes desactivar una cuenta | Es tu propia cuenta o la última cuenta principal activa | Pide apoyo a la cuenta principal o al responsable técnico |

## 15. Flujo recomendado de trabajo

1. **Cada semana:** revisa el panel general y la bandeja de preguntas.
2. **Cuando llegue una pregunta nueva:** decide si es variante o intención nueva.
3. **Cuando cambie un dato oficial:** edita la intención, actualiza la fuente y
   valida.
4. **Cuando el sitio publique noticias o fechas:** sincroniza y revisa los cambios.
5. **Cada mes:** revisa las intenciones sin validar, las respuestas con enlaces
   rotos y la lista de cuentas administrativas.
6. **Cada trimestre:** revisa las preguntas frecuentes y actualiza el contenido.
