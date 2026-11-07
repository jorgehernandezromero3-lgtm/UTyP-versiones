<?php
/** @var callable $e */
/** @var callable $url */
$total = (int) $resumen['activas'];
$validadas = (int) $resumen['validadas'];
$porcentaje = $total > 0 ? (int) round(100 * $validadas / $total) : 0;
$eventos = (int) $resumen['eventos_30d'];
$precision = (float) $resumen['precision_30d'];
$fecha = static function (?string $valor): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    $ts = strtotime($valor);
    return $ts === false ? '—' : date('d/m/Y H:i', $ts);
};
?>
<section class="adm-hero">
    <div>
        <p class="adm-eyebrow">Operación del asistente</p>
        <h2>La información oficial, <em>siempre a la mano.</em></h2>
        <p>
            Revisa el estado de la base de conocimiento, atiende las preguntas que no obtuvieron
            respuesta y mantén los datos sincronizados con el sitio institucional.
        </p>
    </div>
    <div class="adm-hero__acciones">
        <a class="adm-boton adm-boton--principal" href="<?= $e($url('intent')) ?>">Crear intención</a>
        <a class="adm-boton adm-boton--fantasma" href="<?= $e($url('unanswered')) ?>">Revisar preguntas</a>
    </div>
</section>

<div class="adm-metricas">
    <article class="adm-metrica">
        <span class="adm-metrica__etiqueta">Intenciones activas</span>
        <strong><?= $e(number_format($total)) ?></strong>
        <small><?= $e(number_format((int) $resumen['intenciones'])) ?> registradas en total</small>
    </article>
    <article class="adm-metrica">
        <span class="adm-metrica__etiqueta">Variantes de lenguaje</span>
        <strong><?= $e(number_format((int) $resumen['variantes'])) ?></strong>
        <small>Formas de preguntar reconocidas</small>
    </article>
    <article class="adm-metrica">
        <span class="adm-metrica__etiqueta">Sin respuesta</span>
        <strong><?= $e(number_format((int) $resumen['pendientes'])) ?></strong>
        <small><?= $e(number_format((int) $resumen['revisadas'])) ?> revisadas</small>
    </article>
    <article class="adm-metrica">
        <span class="adm-metrica__etiqueta">Precisión · 30 días</span>
        <strong><?= $eventos > 0 ? $e(number_format($precision, 1)) . '%' : '—' ?></strong>
        <small><?= $eventos > 0 ? $e(number_format($eventos)) . ' conversaciones medidas' : 'Aún no hay métricas' ?></small>
    </article>
</div>

<div class="adm-grid adm-grid--principal">
    <section class="adm-panel" aria-labelledby="adm-validacion">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Calidad</p>
                <h2 id="adm-validacion">Avance de validación</h2>
            </div>
            <span class="adm-pastilla"><?= $e((string) $porcentaje) ?>%</span>
        </header>
        <p class="adm-panel__texto">
            Una intención validada significa que una persona revisó la pregunta, la respuesta y la fuente
            oficial. Al editar una intención, su validación se reinicia.
        </p>
        <progress class="adm-progreso" max="<?= $total > 0 ? $e((string) $total) : '1' ?>" value="<?= $e((string) $validadas) ?>">
            <?= $e((string) $porcentaje) ?>%
        </progress>
        <div class="adm-linea">
            <span><?= $e(number_format($validadas)) ?> de <?= $e(number_format($total)) ?> validadas</span>
            <a href="<?= $e($url('intents', ['estado' => 'pendientes'])) ?>">Ver pendientes</a>
        </div>
        <div class="adm-dato">
            <span>Latencia promedio · 30 días</span>
            <strong><?= $eventos > 0 ? $e(number_format((int) $resumen['ms_promedio_30d'])) . ' ms' : 'Sin datos' ?></strong>
        </div>
    </section>

    <section class="adm-panel" aria-labelledby="adm-sync-estado">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Datos del sitio</p>
                <h2 id="adm-sync-estado">Última sincronización</h2>
            </div>
        </header>
        <?php if (is_array($ultimaSync)): ?>
            <p class="adm-sync-fecha"><?= $e($fecha($ultimaSync['iniciado_en'] ?? null)) ?></p>
            <?php
            $estadoSync = (string) ($ultimaSync['estado'] ?? '');
            $etiquetaSync = $estadoSync === 'ok' ? 'Correcta' : ($estadoSync === 'sin_datos' ? 'Sin datos nuevos' : 'Con error');
            $claseSync = $estadoSync === 'ok' ? 'ok' : ($estadoSync === 'sin_datos' ? 'info' : 'error');
            ?>
            <span class="adm-pastilla adm-pastilla--<?= $e($claseSync) ?>"><?= $e($etiquetaSync) ?></span>
            <div class="adm-dato">
                <span>Registros actualizados</span>
                <strong><?= $e(number_format((int) ($ultimaSync['actualizados'] ?? 0))) ?></strong>
            </div>
            <?php if (!empty($ultimaSync['detalle'])): ?>
                <p class="adm-panel__texto"><?= $e((string) $ultimaSync['detalle']) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="adm-vacio">Todavía no se ha ejecutado una sincronización desde este panel.</p>
        <?php endif; ?>
        <a class="adm-boton adm-boton--suave" href="<?= $e($url('sync')) ?>">Abrir sincronización</a>
    </section>
</div>

<section class="adm-panel" aria-labelledby="adm-ultimas">
    <header class="adm-panel__cabeza">
        <div>
            <p class="adm-eyebrow">Bandeja de entrada</p>
            <h2 id="adm-ultimas">Preguntas recientes sin respuesta</h2>
        </div>
        <a class="adm-enlace" href="<?= $e($url('unanswered')) ?>">Ver todas</a>
    </header>

    <?php if ($ultimasPendientes === []): ?>
        <p class="adm-vacio">No hay preguntas pendientes. La base de conocimiento está al día.</p>
    <?php else: ?>
        <div class="adm-tabla-wrap">
            <table class="adm-tabla">
                <thead>
                <tr>
                    <th scope="col">Pregunta redactada</th>
                    <th scope="col">Veces</th>
                    <th scope="col">Última vez</th>
                    <th scope="col">Acción</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimasPendientes as $fila): ?>
                    <tr>
                        <td class="adm-tabla__principal"><?= $e($fila['pregunta']) ?></td>
                        <td><?= $e((string) $fila['veces']) ?></td>
                        <td><?= $e($fecha($fila['ultima_vez'] ?? null)) ?></td>
                        <td>
                            <a class="adm-boton adm-boton--suave adm-boton--pequeno"
                               href="<?= $e($url('intent', ['nuevo' => 1, 'desde' => (int) $fila['id']])) ?>">Convertir en intención</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="adm-aviso-privacidad">
    <h2>Privacidad por diseño</h2>
    <p>
        Las preguntas se guardan redactadas: se eliminan correos, teléfonos, CURP y cadenas numéricas
        largas antes de mostrarse aquí. Las métricas no contienen el texto de la conversación ni la IP
        en claro.
    </p>
</section>
