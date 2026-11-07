<?php
declare(strict_types=1);

require __DIR__ . '/_marco.php';

up_pagina_inicio(
    'Demostración',
    'Prueba local del Asistente Coyote: respuestas oficiales sobre admisión, carreras, costos, fechas y trámites de la UTyPCB.',
    'inicio',
    true
);
?>
<main id="contenido">
    <section class="up-hero" aria-labelledby="up-hero-titulo">
        <div class="up-hero__inner">
            <div class="up-hero__texto">
                <p class="up-eyebrow">Asistente Coyote · UTyPCB</p>
                <h1 id="up-hero-titulo">
                    La información de tu universidad, a una <em>pregunta.</em>
                </h1>
                <p class="up-hero__lead">
                    Admisión, carreras, costos, fechas y trámites. Pregunta como hablas: el Coyote
                    entiende el tema, responde con datos publicados en el sitio oficial y te muestra
                    de dónde salió cada respuesta.
                </p>
                <div class="up-hero__acciones">
                    <button class="up-boton up-boton--primario" type="button" data-demo-abrir>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chat"></use></svg>
                        Abrir el asistente
                    </button>
                    <a class="up-boton up-boton--secundario" href="#preguntas">
                        Ver qué puede responder
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-flecha"></use></svg>
                    </a>
                </div>
                <ul class="up-hero__meta">
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-reloj"></use></svg>
                        Respuestas en segundos
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-escudo"></use></svg>
                        Fuentes oficiales
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"></use></svg>
                        Más de 150 temas
                    </li>
                </ul>
            </div>

            <div class="up-vista" aria-hidden="true">
                <div class="up-vista__marco">
                    <div class="up-vista__barra">
                        <span class="up-vista__punto">
                            <svg viewBox="0 0 24 24"><use href="#i-huella"></use></svg>
                        </span>
                        <span class="up-vista__titulo">
                            <strong>Asistente Coyote</strong>
                            <span>En línea · Información oficial</span>
                        </span>
                    </div>
                    <div class="up-vista__hilo">
                        <p class="up-globo up-globo--bot">
                            Hola, soy el Coyote. ¿Qué necesitas saber de la UTyPCB?
                        </p>
                        <p class="up-globo up-globo--user">¿Cuánto cuesta la ficha?</p>
                        <p class="up-globo up-globo--bot">
                            La ficha cuesta $500.00 e incluye la evaluación psicométrica y el examen
                            de admisión.
                        </p>
                        <span class="up-globo up-globo--fuente">
                            <svg viewBox="0 0 24 24"><use href="#i-doc"></use></svg>
                            Convocatoria 2026–2027
                        </span>
                    </div>
                    <div class="up-vista__campo">
                        <span>Escribe tu pregunta…</span>
                        <i><svg viewBox="0 0 24 24"><use href="#i-flecha"></use></svg></i>
                    </div>
                </div>
                <span class="up-vista__nota"><b>●</b> Respuesta con fuente verificable</span>
            </div>
        </div>
    </section>

    <section class="up-seccion" id="preguntas" aria-labelledby="up-preguntas-titulo">
        <div class="up-seccion__inner">
            <div class="up-seccion__cabeza">
                <div>
                    <span class="up-kicker">Temas disponibles</span>
                    <h2 id="up-preguntas-titulo">¿Qué le puedes preguntar?</h2>
                </div>
                <p>
                    La base de conocimiento cubre la información pública del sitio institucional.
                    Si algo no está publicado, el asistente te canaliza con el área correspondiente.
                </p>
            </div>

            <ul class="up-tarjetas">
                <li class="up-tarjeta">
                    <span class="up-tarjeta__icono">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-doc"></use></svg>
                    </span>
                    <h3>Admisión y documentos</h3>
                    <p>Proceso, fechas, requisitos, resultados y qué llevar si ya quedaste.</p>
                    <span class="up-tarjeta__pie">Ficha · Examen · Inscripción</span>
                </li>
                <li class="up-tarjeta">
                    <span class="up-tarjeta__icono">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-libro"></use></svg>
                    </span>
                    <h3>Carreras y planes de estudio</h3>
                    <p>Oferta educativa, duración, materias, perfil de egreso y campo laboral.</p>
                    <span class="up-tarjeta__pie">TSU · Ingenierías · Licenciaturas</span>
                </li>
                <li class="up-tarjeta">
                    <span class="up-tarjeta__icono">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-beca"></use></svg>
                    </span>
                    <h3>Costos y becas</h3>
                    <p>Ficha, inscripción, colegiaturas, requisitos y opciones de apoyo económico.</p>
                    <span class="up-tarjeta__pie">Pagos · Apoyos · Requisitos</span>
                </li>
                <li class="up-tarjeta">
                    <span class="up-tarjeta__icono">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendario"></use></svg>
                    </span>
                    <h3>Fechas y trámites</h3>
                    <p>Calendario escolar, revalidación, constancias, servicios y contacto.</p>
                    <span class="up-tarjeta__pie">Cuatrimestres · Servicios</span>
                </li>
            </ul>
        </div>
    </section>

    <section class="up-seccion" id="como-funciona" aria-labelledby="up-como-titulo">
        <div class="up-seccion__inner">
            <div class="up-seccion__cabeza">
                <div>
                    <span class="up-kicker">Tres pasos</span>
                    <h2 id="up-como-titulo">Así de simple funciona</h2>
                </div>
                <p>
                    No necesitas aprender comandos ni escribir de forma perfecta. El motor está hecho
                    para reconocer errores de dedo, faltas de acento y formas coloquiales.
                </p>
            </div>

            <ol class="up-pasos">
                <li class="up-paso">
                    <h3>Abre al Coyote</h3>
                    <p>Usa el botón flotante de la esquina inferior derecha en cualquier página del sitio.</p>
                </li>
                <li class="up-paso">
                    <h3>Pregunta como hablas</h3>
                    <p>«¿Cuándo es la ficha?», «cuanto cuesta» o «q carreras hay»: el asistente interpreta la intención.</p>
                </li>
                <li class="up-paso">
                    <h3>Revisa la fuente</h3>
                    <p>Cada respuesta incluye el documento o sección oficial donde puedes confirmar el dato.</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="up-banda" aria-labelledby="up-banda-titulo">
        <div class="up-banda__inner">
            <div>
                <h2 id="up-banda-titulo">¿Tienes una duda? <em>Pregúntale al Coyote.</em></h2>
                <p>Respuestas claras, en español, con la fuente oficial a la mano.</p>
            </div>
            <button class="up-boton up-boton--primario" type="button" data-demo-abrir>
                <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chat"></use></svg>
                Empezar conversación
            </button>
        </div>
    </section>
</main>
<?php
up_pagina_fin(true, true);
