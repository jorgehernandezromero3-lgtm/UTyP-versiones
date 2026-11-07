<?php
declare(strict_types=1);

/*
 * Fragmento del Asistente Coyote.
 * Se incluye desde footer.inc.php, antes de </body>.
 *
 * Variables opcionales antes de incluir este archivo:
 *   $utypAsistenteBase  Ruta pública del asistente (por defecto /asistente).
 *   $utypAsistenteAviso URL del aviso de privacidad.
 */

$utypAsistenteBase = isset($utypAsistenteBase) && is_string($utypAsistenteBase)
    ? rtrim($utypAsistenteBase, '/')
    : '/asistente';
if ($utypAsistenteBase === '') {
    $utypAsistenteBase = '/asistente';
}
$utypAsistenteAviso = isset($utypAsistenteAviso) && is_string($utypAsistenteAviso) && $utypAsistenteAviso !== ''
    ? $utypAsistenteAviso
    : $utypAsistenteBase . '/privacidad.php';

$utypE = static function ($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<link rel="stylesheet" href="<?= $utypE($utypAsistenteBase . '/widget.css') ?>">
<div
    id="utyp-asistente"
    class="utyp-chat"
    data-estado="<?= $utypE($utypAsistenteBase . '/estado.php') ?>"
    data-chat="<?= $utypE($utypAsistenteBase . '/chat.php') ?>"
    data-aviso="<?= $utypE($utypAsistenteAviso) ?>"
>
    <button
        class="utyp-chat__launcher"
        type="button"
        aria-expanded="false"
        aria-controls="utyp-chat-panel"
        aria-label="Abrir el Asistente Coyote"
        data-abrir
    >
        <span class="utyp-chat__launcher-icono" aria-hidden="true">
            <svg class="utyp-chat__icono" viewBox="0 0 24 24"><use href="#i-huella"></use></svg>
        </span>
        <span class="utyp-chat__launcher-texto">
            <strong>Asistente Coyote</strong>
            <span>¿Te ayudo?</span>
        </span>
    </button>

    <section
        class="utyp-chat__panel"
        id="utyp-chat-panel"
        role="dialog"
        aria-label="Asistente Coyote de la UTyPCB"
        data-panel
        hidden
    >
        <header class="utyp-chat__cabecera">
            <div class="utyp-chat__marca">
                <span class="utyp-chat__marca-icono" aria-hidden="true">
                    <svg class="utyp-chat__icono" viewBox="0 0 24 24"><use href="#i-huella"></use></svg>
                </span>
                <span class="utyp-chat__marca-texto">
                    <strong>Asistente Coyote</strong>
                    <span class="utyp-chat__estado" data-estado-sesion="cargando">
                        <i aria-hidden="true"></i>
                        <span data-estado-texto>Conectando…</span>
                    </span>
                </span>
            </div>
            <div class="utyp-chat__acciones">
                <button class="utyp-chat__accion" type="button" data-reiniciar aria-label="Empezar una conversación nueva" title="Nueva conversación">
                    <svg class="utyp-chat__icono" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-destello"></use></svg>
                </button>
                <button class="utyp-chat__accion" type="button" data-cerrar aria-label="Cerrar el asistente" title="Cerrar">
                    <svg class="utyp-chat__icono" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-cerrar"></use></svg>
                </button>
            </div>
        </header>

        <div class="utyp-chat__cuerpo" data-mensajes role="log" aria-live="polite" aria-relevant="additions text" tabindex="0">
            <div class="utyp-chat__bienvenida" data-bienvenida>
                <p class="utyp-chat__eyebrow">Información oficial · UTyPCB</p>
                <h2>Pregúntale al <em>Coyote.</em></h2>
                <p class="utyp-chat__intro">
                    Admisión, carreras, costos, fechas y trámites. Respondo con datos publicados en
                    utypcb.edu.mx y te digo de dónde sale cada respuesta.
                </p>
                <div class="utyp-chat__inicio" data-inicio aria-label="Preguntas para empezar"></div>
                <button class="utyp-chat__reintentar" type="button" data-reintentar hidden>
                    Reintentar conexión
                </button>
                <p class="utyp-chat__nota-conexion" data-conexion role="status" hidden></p>
            </div>
        </div>

        <form class="utyp-chat__form" data-form novalidate>
            <label class="utyp-chat__sr" for="utyp-chat-texto">Escribe tu pregunta</label>
            <div class="utyp-chat__campo">
                <textarea
                    id="utyp-chat-texto"
                    data-texto
                    rows="1"
                    maxlength="300"
                    placeholder="Escribe tu pregunta…"
                    autocomplete="off"
                    enterkeyhint="send"
                    aria-describedby="utyp-chat-contador utyp-chat-privacidad"
                ></textarea>
                <button class="utyp-chat__enviar" type="submit" data-enviar aria-label="Enviar pregunta" disabled>
                    <svg class="utyp-chat__icono" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-flecha"></use></svg>
                </button>
            </div>
            <div class="utyp-chat__pie">
                <span class="utyp-chat__contador" id="utyp-chat-contador" data-contador>0/300</span>
                <span class="utyp-chat__privacidad" id="utyp-chat-privacidad">
                    No escribas datos personales.
                    <a href="<?= $utypE($utypAsistenteAviso) ?>" data-aviso> Aviso de privacidad</a>
                </span>
            </div>
        </form>
    </section>
</div>
<script src="<?= $utypE($utypAsistenteBase . '/widget.js') ?>" defer></script>
