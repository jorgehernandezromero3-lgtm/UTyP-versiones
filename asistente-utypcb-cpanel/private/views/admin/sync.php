<?php
/** @var callable $e */
/** @var callable $url */
$fecha = static function (?string $valor): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    $ts = strtotime($valor);
    return $ts === false ? '—' : date('d/m/Y H:i', $ts);
};
$estadoEtiqueta = static function (string $estado): array {
    if ($estado === 'ok') {
        return ['Correcta', 'ok'];
    }
    if ($estado === 'sin_datos') {
        return ['Sin datos', 'info'];
    }
    return ['Error', 'error'];
};
?>
<section class="adm-barra">
    <div>
        <p class="adm-eyebrow">Operación</p>
        <h2>Sincronización con los datos del sitio</h2>
        <p class="adm-barra__texto">
            El sincronizador lee los archivos PHP locales del sitio institucional. No descarga páginas ni
            usa un scraper. Si un archivo no existe o no es legible, la sincronización se detiene sin
            modificar la base de conocimiento.
        </p>
    </div>
    <div class="adm-acciones">
        <form method="post" action="<?= $e($url('sync-run')) ?>">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <button class="adm-boton adm-boton--principal" type="submit">Sincronizar ahora</button>
        </form>
    </div>
</section>

<div class="adm-grid adm-grid--principal">
    <section class="adm-panel" aria-labelledby="adm-fuentes">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Origen de datos</p>
                <h2 id="adm-fuentes">Archivos configurados</h2>
            </div>
        </header>
        <p class="adm-panel__texto">
            Directorio configurado: <code><?= $e($directorio !== '' ? $directorio : 'No configurado') ?></code>
        </p>

        <?php if ($fuentes === []): ?>
            <p class="adm-vacio">No hay archivos configurados en <code>sitio.archivos</code>.</p>
        <?php else: ?>
            <div class="adm-tabla-wrap">
                <table class="adm-tabla adm-tabla--sync">
                    <thead>
                    <tr>
                        <th scope="col">Fuente</th>
                        <th scope="col">Archivo</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Modificado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fuentes as $fuente): ?>
                        <tr>
                            <td class="adm-tabla__principal"><?= $e($fuente['clave']) ?></td>
                            <td>
                                <code><?= $e($fuente['archivo']) ?></code>
                                <small class="adm-tabla__nota"><code><?= $e($fuente['variable']) ?></code></small>
                            </td>
                            <td>
                                <?php if (!empty($fuente['legible'])): ?>
                                    <span class="adm-pastilla adm-pastilla--ok">Disponible</span>
                                <?php elseif (!empty($fuente['existe'])): ?>
                                    <span class="adm-pastilla adm-pastilla--error">Sin permiso de lectura</span>
                                <?php else: ?>
                                    <span class="adm-pastilla adm-pastilla--error">No encontrado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $e($fecha($fuente['modificado'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="adm-panel" aria-labelledby="adm-ultima-sync">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Último proceso</p>
                <h2 id="adm-ultima-sync">Resultado</h2>
            </div>
        </header>
        <?php if (is_array($ultimaSync)): ?>
            <?php $etiqueta = $estadoEtiqueta((string) ($ultimaSync['estado'] ?? 'error')); ?>
            <span class="adm-pastilla adm-pastilla--<?= $e($etiqueta[1]) ?>"><?= $e($etiqueta[0]) ?></span>
            <div class="adm-dato">
                <span>Inicio</span>
                <strong><?= $e($fecha($ultimaSync['iniciado_en'] ?? null)) ?></strong>
            </div>
            <div class="adm-dato">
                <span>Término</span>
                <strong><?= $e($fecha($ultimaSync['terminado_en'] ?? null)) ?></strong>
            </div>
            <div class="adm-dato">
                <span>Registros actualizados</span>
                <strong><?= $e(number_format((int) ($ultimaSync['actualizados'] ?? 0))) ?></strong>
            </div>
            <?php if (!empty($ultimaSync['detalle'])): ?>
                <p class="adm-panel__texto"><?= $e((string) $ultimaSync['detalle']) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="adm-vacio">Aún no se ha ejecutado una sincronización.</p>
        <?php endif; ?>
    </section>
</div>

<section class="adm-panel" aria-labelledby="adm-retencion">
    <header class="adm-panel__cabeza">
        <div>
            <p class="adm-eyebrow">Privacidad</p>
            <h2 id="adm-retencion">Retención y purga</h2>
        </div>
    </header>
    <p class="adm-panel__texto">
        Las preguntas sin respuesta se conservan <?= $e((string) $retencionNoRespondidas) ?> días y las
        métricas agregadas <?= $e((string) $retencionEventos) ?> días. La purga elimina registros vencidos
        y también limpia los contadores de límite de uso que ya expiraron.
    </p>
    <form method="post" action="<?= $e($url('sync-purge')) ?>"
          data-confirmar="¿Ejecutar la purga de datos vencidos? Se eliminarán preguntas y métricas fuera del periodo de retención.">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <button class="adm-boton adm-boton--peligro" type="submit">Purgar datos vencidos</button>
    </form>
</section>

<section class="adm-panel" aria-labelledby="adm-historial-sync">
    <header class="adm-panel__cabeza">
        <div>
            <p class="adm-eyebrow">Bitácora</p>
            <h2 id="adm-historial-sync">Historial de sincronizaciones</h2>
        </div>
    </header>
    <?php if ($syncLog === []): ?>
        <p class="adm-vacio">No hay ejecuciones registradas.</p>
    <?php else: ?>
        <div class="adm-tabla-wrap">
            <table class="adm-tabla">
                <thead>
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Actualizados</th>
                    <th scope="col">Detalle</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($syncLog as $fila): ?>
                    <?php $etiqueta = $estadoEtiqueta((string) ($fila['estado'] ?? 'error')); ?>
                    <tr>
                        <td><?= $e($fecha($fila['iniciado_en'] ?? null)) ?></td>
                        <td><span class="adm-pastilla adm-pastilla--<?= $e($etiqueta[1]) ?>"><?= $e($etiqueta[0]) ?></span></td>
                        <td><?= $e(number_format((int) ($fila['actualizados'] ?? 0))) ?></td>
                        <td><?= $e($fila['detalle'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="adm-aviso-privacidad">
    <h2>Qué se sincroniza</h2>
    <p>
        El proceso actualiza únicamente las intenciones dinámicas que provienen de los datos del sitio,
        conserva las respuestas editoriales y registra cada ejecución en la bitácora. Revisa el resultado
        y valida las intenciones modificadas antes de considerarlas definitivas.
    </p>
</section>
