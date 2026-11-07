<?php
declare(strict_types=1);

require __DIR__ . '/_marco.php';

up_pagina_inicio(
    'Aviso de privacidad',
    'Cómo trata el Asistente Coyote los datos de las conversaciones: finalidades, conservación, seguridad y derechos de las personas usuarias.',
    'privacidad'
);
?>
<main id="contenido">
    <div class="up-doc">
        <header class="up-doc__cabeza">
            <p class="up-eyebrow">Asistente Coyote · UTyPCB</p>
            <h1>Aviso de <em>privacidad.</em></h1>
            <p>
                Este aviso explica qué información recibe el Asistente Coyote cuando lo usas, para qué
                se utiliza, cuánto tiempo se conserva y cómo puedes ejercer tus derechos. Está redactado
                en lenguaje sencillo y aplica únicamente al asistente virtual, no a los demás formularios
                o servicios del sitio institucional.
            </p>
            <p class="up-doc__fecha">
                <span>Versión 1.0</span>
                <span>Última actualización: <time datetime="2026-11-06">6 de noviembre de 2026</time></span>
            </p>
        </header>

        <article>
            <section aria-labelledby="dp-responsable">
                <h2 id="dp-responsable"><span>01</span> Responsable del tratamiento</h2>
                <p>
                    El responsable es la <strong>Universidad Tecnológica y Politécnica de Coyuca de Benítez
                    (UTyPCB)</strong>, con domicilio en C. Nueva Mercado Nuevo, Centro, 40977 Coyuca de
                    Benítez, Guerrero. Para asuntos relacionados con este aviso, la unidad de contacto es
                    el Departamento de Servicios Escolares.
                </p>
                <div class="up-doc__contacto">
                    <h3>Contacto para privacidad</h3>
                    <p>
                        Correo: <a href="mailto:serviciosescolares@utypcb.edu.mx">serviciosescolares@utypcb.edu.mx</a><br>
                        Teléfono: <a href="tel:+527811003192">781 100 3192</a><br>
                        Horario de atención: días hábiles, en el horario publicado por la universidad.
                    </p>
                </div>
            </section>

            <section aria-labelledby="dp-datos">
                <h2 id="dp-datos"><span>02</span> Qué datos se tratan</h2>
                <p>
                    El asistente no solicita nombre, cuenta ni contraseña. La información que se trata es
                    la siguiente:
                </p>
                <ul>
                    <li>
                        <strong>El texto de tus preguntas.</strong> Se procesa para identificar el tema y
                        construir una respuesta. No se guarda de forma textual en las bitácoras.
                    </li>
                    <li>
                        <strong>El contexto de la conversación.</strong> Se conserva en la sesión del
                        servidor para entender preguntas de seguimiento como «¿y cuánto dura?». Incluye el
                        tema y el programa detectados, no un historial completo.
                    </li>
                    <li>
                        <strong>Datos técnicos mínimos.</strong> La dirección IP se usa para aplicar
                        límites de uso y se convierte en un hash con una clave secreta; no se almacena en
                        claro en las tablas de métricas.
                    </li>
                    <li>
                        <strong>Métricas agregadas de uso.</strong> Tema identificado, si hubo coincidencia,
                        nivel de confianza y tiempo de respuesta, sin texto de la pregunta ni IP.
                    </li>
                    <li>
                        <strong>Preguntas no respondidas.</strong> Cuando el asistente no encuentra una
                        intención, la pregunta se guarda <em>redactada</em> para revisión del área
                        responsable. Se eliminan correos, CURP, teléfonos y cadenas numéricas largas.
                    </li>
                </ul>
                <p>
                    <strong>No proporciones datos personales en el chat.</strong> El asistente está pensado
                    para consultas informativas. Si escribes datos personales, el sistema aplica filtros de
                    redacción antes de guardar una pregunta no respondida, pero no es un canal para
                    solicitudes, trámites ni casos individuales.
                </p>
            </section>

            <section aria-labelledby="dp-finalidades">
                <h2 id="dp-finalidades"><span>03</span> Para qué se utilizan</h2>
                <ul>
                    <li>Resolver consultas sobre admisión, carreras, costos, fechas, trámites y servicios.</li>
                    <li>Mantener el hilo de la conversación durante la sesión activa.</li>
                    <li>Proteger el servicio mediante límites de uso y controles de seguridad.</li>
                    <li>Detectar temas que la base de conocimiento no cubre y mejorarla.</li>
                    <li>Medir desempeño: aciertos, confianza y tiempos de respuesta.</li>
                </ul>
                <p>
                    No se usan para publicidad, perfilado comercial, venta de datos ni decisiones
                    automatizadas que produzcan efectos jurídicos para la persona usuaria.
                </p>
            </section>

            <section aria-labelledby="dp-cookies">
                <h2 id="dp-cookies"><span>04</span> Cookies y almacenamiento local</h2>
                <p>
                    El asistente utiliza una <strong>cookie de sesión estrictamente necesaria</strong> para
                    mantener el hilo de la conversación y validar la seguridad de las solicitudes. No se
                    usan cookies de publicidad, análisis de terceros ni rastreadores. Puedes borrar la
                    cookie desde la configuración de tu navegador; al hacerlo se inicia una conversación
                    nueva.
                </p>
            </section>

            <section aria-labelledby="dp-conservacion">
                <h2 id="dp-conservacion"><span>05</span> Plazos de conservación</h2>
                <table class="up-doc__tabla">
                    <thead>
                        <tr>
                            <th scope="col">Información</th>
                            <th scope="col">Conservación</th>
                            <th scope="col">Ubicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Contexto de la conversación</td>
                            <td>Hasta 30 minutos de inactividad</td>
                            <td>Sesión del servidor</td>
                        </tr>
                        <tr>
                            <td>Contadores de límite de uso</td>
                            <td>Mientras dure la ventana de tiempo configurada</td>
                            <td>MariaDB</td>
                        </tr>
                        <tr>
                            <td>Preguntas no respondidas redactadas</td>
                            <td>Hasta 90 días</td>
                            <td>MariaDB</td>
                        </tr>
                        <tr>
                            <td>Métricas agregadas de uso</td>
                            <td>Hasta 365 días</td>
                            <td>MariaDB</td>
                        </tr>
                    </tbody>
                </table>
                <p>
                    Al cumplirse cada plazo, los registros se eliminan mediante un proceso automático de
                    depuración. Las preguntas revisadas que se convierten en nuevos temas de la base de
                    conocimiento se incorporan sin texto literal, sin IP ni identificadores de la persona.
                </p>
            </section>

            <section aria-labelledby="dp-derechos">
                <h2 id="dp-derechos"><span>06</span> Tus derechos</h2>
                <p>
                    Puedes solicitar el acceso a los datos que se conserven sobre tus conversaciones, así
                    como su rectificación, cancelación u oposición al tratamiento, y revocar el
                    consentimiento cuando proceda. Envía tu solicitud al correo de contacto indicado y
                    describe con claridad lo que necesitas.
                </p>
                <p>
                    También puedes acudir a la autoridad garante competente en materia de protección de
                    datos personales si consideras que tu solicitud no fue atendida correctamente. La
                    universidad atenderá las solicitudes en los plazos y con los requisitos que establece
                    la normativa aplicable a los sujetos obligados.
                </p>
            </section>

            <section aria-labelledby="dp-seguridad">
                <h2 id="dp-seguridad"><span>07</span> Medidas de seguridad</h2>
                <ul>
                    <li>Conexión cifrada mediante HTTPS cuando el sitio opera en producción.</li>
                    <li>Credenciales y configuración fuera de la carpeta pública.</li>
                    <li>Consultas a la base de datos con sentencias preparadas y salida escapada.</li>
                    <li>Control de acceso al panel administrativo y límites de intentos de inicio de sesión.</li>
                    <li>Bitácoras sin texto de preguntas, correos, IP en claro ni datos de sesión.</li>
                </ul>
                <p>
                    Ningún sistema es infalible. Si se detecta un incidente que afecte datos personales, se
                    aplicarán los procedimientos institucionales de respuesta y notificación que
                    correspondan.
                </p>
            </section>

            <section aria-labelledby="dp-alcance">
                <h2 id="dp-alcance"><span>08</span> Alcance de las respuestas</h2>
                <p>
                    El Asistente Coyote es un apoyo informativo. Sus respuestas se basan en la información
                    pública del sitio institucional y pueden cambiar cuando la universidad actualice
                    convocatorias, costos o calendarios. La respuesta no sustituye la convocatoria oficial,
                    la resolución de una autoridad académica ni la atención presencial de Servicios
                    Escolares. Verifica siempre la fuente enlazada antes de tomar una decisión.
                </p>
            </section>

            <section aria-labelledby="dp-cambios">
                <h2 id="dp-cambios"><span>09</span> Cambios a este aviso</h2>
                <p>
                    Si cambian las finalidades, los plazos o los mecanismos de tratamiento, se actualizará
                    este aviso y se modificará la fecha de la versión visible. La versión vigente estará
                    siempre disponible en esta misma dirección.
                </p>
            </section>

            <div class="up-doc__volver">
                <a class="up-boton up-boton--secundario" href="demo.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-flecha"></use></svg>
                    Volver al asistente
                </a>
            </div>
        </article>
    </div>
</main>
<?php
up_pagina_fin(true, false);
