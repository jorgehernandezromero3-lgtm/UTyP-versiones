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
$tabs = [
    'pendientes' => ['Pendientes', (int) $conteos['pendientes']],
    'revisadas' => ['Revisadas', (int) $conteos['revisadas']],
    'todas' => ['Todas', (int) $conteos['total']],
];
?>
<section class="adm-barra">
    <div>
        <p class="adm-eyebrow">Mejora continua</p>
        <h2>Preguntas que el asistente no pudo responder</h2>
        <p class="adm-barra__texto">
            El texto se almacena redactado. Revisa cada caso, agrega una nota y conviértelo en una nueva
            intención cuando tengas una respuesta oficial verificable.
        </p>
    </div>
</section>

<nav class="adm-tabs" aria-label="Filtro de preguntas">
    <?php foreach ($tabs as $clave => $tab): ?>
        <a href="<?= $e($url('unanswered', ['filtro' => $clave])) ?>"
           class="adm-tab<?= $filtro === $clave ? ' adm-tab--activo' : '' ?>"
           <?= $filtro === $clave ? 'aria-current="page"' : '' ?>>
            <?= $e($tab[0]) ?> <span><?= $e((string) $tab[1]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form class="adm-filtros" method="get" action="index.php">
    <input type="hidden" name="r" value="unanswered">
    <input type="hidden" name="filtro" value="<?= $e($filtro) ?>">
    <label class="adm-campo adm-campo--buscar">
        <span>Buscar en las preguntas</span>
        <input type="search" name="q" value="<?= $e($buscar) ?>" maxlength="80" placeholder="Escribe una palabra clave">
    </label>
    <div class="adm-filtros__acciones">
        <button class="adm-boton adm-boton--suave" type="submit">Buscar</button>
        <a class="adm-enlace" href="<?= $e($url('unanswered', ['filtro' => $filtro])) ?>">Limpiar</a>
    </div>
</form>

<?php if ($noRespondidas === []): ?>
    <section class="adm-panel">
        <p class="adm-vacio">
            <?= $filtro === 'pendientes' ? 'No hay preguntas pendientes de revisión.' : 'No se encontraron preguntas con este filtro.' ?>
        </p>
    </section>
<?php else: ?>
    <div class="adm-bandeja">
        <?php foreach ($noRespondidas as $fila): ?>
            <?php $idCard = 'pregunta-' . (int) $fila['id']; ?>
            <article class="adm-pregunta<?= (int) $fila['revisada'] === 1 ? ' adm-pregunta--revisada' : '' ?>" aria-labelledby="<?= $e($idCard) ?>">
                <header class="adm-pregunta__cabeza">
                    <div>
                        <span class="adm-pastilla <?= (int) $fila['revisada'] === 1 ? 'adm-pastilla--ok' : 'adm-pastilla--pendiente' ?>">
                            <?= (int) $fila['revisada'] === 1 ? 'Revisada' : 'Pendiente' ?>
                        </span>
                        <span class="adm-pregunta__meta"><?= $e((string) $fila['veces']) ?> <?= (int) $fila['veces'] === 1 ? 'vez' : 'veces' ?></span>
                    </div>
                    <span class="adm-pregunta__meta">Última: <?= $e($fecha($fila['ultima_vez'] ?? null)) ?></span>
                </header>

                <h3 id="<?= $e($idCard) ?>">«<?= $e($fila['pregunta']) ?>»</h3>

                <div class="adm-pregunta__datos">
                    <span>Primera vez: <?= $e($fecha($fila['primera_vez'] ?? null)) ?></span>
                    <?php if (!empty($fila['intent_id']) && !empty($fila['intent_slug'])): ?>
                        <span>
                            Vinculada a
                            <a href="<?= $e($url('intent', ['id' => (int) $fila['intent_id']])) ?>"><?= $e($fila['intent_pregunta'] ?? $fila['intent_slug']) ?></a>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="adm-pregunta__cuerpo">
                    <form method="post" action="<?= $e($url('unanswered-update')) ?>" class="adm-form-inline">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <input type="hidden" name="id" value="<?= $e((string) $fila['id']) ?>">
                        <label class="adm-check">
                            <input type="checkbox" name="revisada" value="1"<?= (int) $fila['revisada'] === 1 ? ' checked' : '' ?>>
                            <span>Marcar como revisada</span>
                        </label>
                        <label class="adm-campo adm-campo--nota">
                            <span>Nota interna</span>
                            <input type="text" name="nota" value="<?= $e($fila['nota'] ?? '') ?>" maxlength="500"
                                   placeholder="Qué se revisó, con quién se confirmó o por qué no aplica">
                        </label>
                        <button class="adm-boton adm-boton--suave adm-boton--pequeno" type="submit">Guardar revisión</button>
                    </form>

                    <div class="adm-acciones">
                        <a class="adm-boton adm-boton--principal adm-boton--pequeno"
                           href="<?= $e($url('intent', ['nuevo' => 1, 'desde' => (int) $fila['id']])) ?>">Convertir en intención</a>
                        <form method="post" action="<?= $e($url('unanswered-delete')) ?>"
                              data-confirmar="¿Eliminar esta pregunta del registro? Esta acción no se puede deshacer.">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= $e((string) $fila['id']) ?>">
                            <button class="adm-boton adm-boton--peligro adm-boton--pequeno" type="submit">Eliminar</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
