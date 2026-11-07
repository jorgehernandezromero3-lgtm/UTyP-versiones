<?php
/** @var callable $e */
/** @var callable $url */
$fecha = static function (?string $valor): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    $ts = strtotime($valor);
    return $ts === false ? '—' : date('d/m/Y', $ts);
};
?>
<section class="adm-barra">
    <div>
        <p class="adm-eyebrow">Base de conocimiento</p>
        <h2><?= $e(number_format(count($intents))) ?> intenciones en esta vista</h2>
        <p class="adm-barra__texto">Cada intención conserva cinco variantes base y puede sumar variantes adicionales.</p>
    </div>
    <a class="adm-boton adm-boton--principal" href="<?= $e($url('intent')) ?>">Nueva intención</a>
</section>

<form class="adm-filtros" method="get" action="index.php">
    <input type="hidden" name="r" value="intents">
    <label class="adm-campo adm-campo--buscar">
        <span>Buscar</span>
        <input type="search" name="q" value="<?= $e($buscar) ?>" maxlength="80"
               placeholder="Pregunta, respuesta o identificador">
    </label>
    <label class="adm-campo">
        <span>Categoría</span>
        <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $clave => $nombre): ?>
                <option value="<?= $e($clave) ?>"<?= $categoria === $clave ? ' selected' : '' ?>><?= $e($nombre) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="adm-campo">
        <span>Estado</span>
        <select name="estado">
            <?php
            $opciones = [
                'todas' => 'Todas',
                'activas' => 'Activas',
                'inactivas' => 'Inactivas',
                'pendientes' => 'Sin validar',
                'validadas' => 'Validadas',
            ];
            ?>
            <?php foreach ($opciones as $clave => $nombre): ?>
                <option value="<?= $e($clave) ?>"<?= $estado === $clave ? ' selected' : '' ?>><?= $e($nombre) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="adm-filtros__acciones">
        <button class="adm-boton adm-boton--suave" type="submit">Filtrar</button>
        <a class="adm-enlace" href="<?= $e($url('intents')) ?>">Limpiar</a>
    </div>
</form>

<?php if ($intents === []): ?>
    <section class="adm-panel">
        <p class="adm-vacio">No se encontraron intenciones con esos filtros.</p>
        <a class="adm-boton adm-boton--suave" href="<?= $e($url('intents')) ?>">Quitar filtros</a>
    </section>
<?php else: ?>
    <div class="adm-tabla-wrap adm-panel adm-panel--tabla">
        <table class="adm-tabla adm-tabla--intents">
            <thead>
            <tr>
                <th scope="col">Intención</th>
                <th scope="col">Categoría</th>
                <th scope="col">Variantes</th>
                <th scope="col">Estado</th>
                <th scope="col">Validación</th>
                <th scope="col">Actualizada</th>
                <th scope="col"><span class="adm-oculto">Acciones</span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($intents as $fila): ?>
                <tr>
                    <td class="adm-tabla__principal">
                        <a href="<?= $e($url('intent', ['id' => (int) $fila['id']])) ?>"><?= $e($fila['pregunta']) ?></a>
                        <small><?= $e($fila['slug']) ?> · prioridad <?= $e((string) $fila['prioridad']) ?></small>
                    </td>
                    <td><?= $e($categorias[$fila['categoria']] ?? $fila['categoria']) ?></td>
                    <td><?= $e((string) $fila['variantes']) ?></td>
                    <td>
                        <?php if ((int) $fila['activa'] === 1): ?>
                            <span class="adm-pastilla adm-pastilla--ok">Activa</span>
                        <?php else: ?>
                            <span class="adm-pastilla adm-pastilla--apagada">Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $fila['validada'] === 1): ?>
                            <span class="adm-pastilla adm-pastilla--ok">Validada</span>
                            <?php if (!empty($fila['validada_por'])): ?>
                                <small class="adm-tabla__nota"><?= $e($fila['validada_por']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="adm-pastilla adm-pastilla--pendiente">Sin validar</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $e($fecha($fila['actualizada_en'] ?? null)) ?></td>
                    <td>
                        <div class="adm-acciones">
                            <a class="adm-boton adm-boton--suave adm-boton--pequeno"
                               href="<?= $e($url('intent', ['id' => (int) $fila['id']])) ?>">Editar</a>

                            <?php if ((int) $fila['validada'] === 1): ?>
                                <form method="post" action="<?= $e($url('intent-unvalidate')) ?>">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= $e((string) $fila['id']) ?>">
                                    <button class="adm-boton adm-boton--fantasma adm-boton--pequeno" type="submit">Quitar validación</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= $e($url('intent-validate')) ?>">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= $e((string) $fila['id']) ?>">
                                    <button class="adm-boton adm-boton--fantasma adm-boton--pequeno" type="submit">Validar</button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="<?= $e($url('intent-toggle')) ?>">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="id" value="<?= $e((string) $fila['id']) ?>">
                                <input type="hidden" name="activa" value="<?= (int) $fila['activa'] === 1 ? '0' : '1' ?>">
                                <button class="adm-boton adm-boton--fantasma adm-boton--pequeno" type="submit">
                                    <?= (int) $fila['activa'] === 1 ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>

                            <form method="post" action="<?= $e($url('intent-delete')) ?>"
                                  data-confirmar="¿Eliminar esta intención y todas sus variantes? Esta acción no se puede deshacer.">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="id" value="<?= $e((string) $fila['id']) ?>">
                                <button class="adm-boton adm-boton--peligro adm-boton--pequeno" type="submit">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
