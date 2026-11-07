<?php
/** @var callable $e */
/** @var callable $url */
/** @var array $valores */
/** @var array $errores */
/** @var array|null $intent */
/** @var array|null $noRespondida */
$err = static function (string $campo) use ($errores): string {
    return isset($errores[$campo]) ? (string) $errores[$campo] : '';
};
$error = static function (string $campo) use ($err, $e): string {
    $mensaje = $err($campo);
    return $mensaje !== '' ? '<p class="adm-error-campo" role="alert">' . $e($mensaje) . '</p>' : '';
};
$variantes = is_array($valores['variantes']) ? $valores['variantes'] : [];
while (count($variantes) < 5) {
    $variantes[] = '';
}
$adminTexto = is_array($valores['variantes_admin'])
    ? implode("\n", array_map('strval', $valores['variantes_admin']))
    : (string) $valores['variantes_admin'];
$enlacesTexto = '';
if (is_array($valores['enlaces'])) {
    $lineas = [];
    foreach ($valores['enlaces'] as $enlace) {
        if (is_array($enlace)) {
            $lineas[] = trim((string) ($enlace['titulo'] ?? '')) . ' | ' . trim((string) ($enlace['url'] ?? ''));
        }
    }
    $enlacesTexto = implode("\n", $lineas);
} else {
    $enlacesTexto = (string) $valores['enlaces'];
}
$clavesTexto = is_array($valores['palabras_clave'])
    ? implode(', ', array_map('strval', $valores['palabras_clave']))
    : (string) $valores['palabras_clave'];
$fecha = static function (?string $valor): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    $ts = strtotime($valor);
    return $ts === false ? '—' : date('d/m/Y H:i', $ts);
};
$validada = is_array($intent) && (int) $intent['validada'] === 1;
$activa = !empty($valores['activa']);
?>
<section class="adm-barra">
    <div>
        <p class="adm-eyebrow"><?= $intent !== null ? 'Edición' : 'Alta de conocimiento' ?></p>
        <h2><?= $intent !== null ? $e($intent['pregunta']) : 'Nueva intención' ?></h2>
        <p class="adm-barra__texto">
            <?= $intent !== null ? 'Los cambios reinician la validación de la intención.' : 'Completa la pregunta, la respuesta oficial y sus cinco variantes base.' ?>
        </p>
    </div>
    <a class="adm-boton adm-boton--fantasma" href="<?= $e($url('intents')) ?>">Volver al listado</a>
</section>

<?php if (isset($errores['general'])): ?>
    <div class="adm-alerta adm-alerta--error" role="alert"><?= $e($errores['general']) ?></div>
<?php endif; ?>

<?php if (is_array($noRespondida)): ?>
    <div class="adm-alerta adm-alerta--info" role="status">
        Esta intención se creará a partir de una pregunta sin respuesta. Al guardarla, la pregunta quedará
        marcada como revisada y vinculada. Pregunta original: «<?= $e($noRespondida['pregunta']) ?>».
    </div>
<?php endif; ?>

<form class="adm-form-intent" method="post" action="<?= $e($url('intent-save')) ?>" data-formulario-principal>
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <input type="hidden" name="id" value="<?= $intent !== null ? $e((string) $intent['id']) : '0' ?>">
    <input type="hidden" name="no_respondida_id" value="<?= is_array($noRespondida) ? $e((string) $noRespondida['id']) : '0' ?>">

    <div class="adm-grid adm-grid--form">
        <div class="adm-columna">
            <section class="adm-panel" aria-labelledby="adm-identificacion">
                <header class="adm-panel__cabeza">
                    <div>
                        <p class="adm-eyebrow">01 · Identificación</p>
                        <h2 id="adm-identificacion">Ficha de la intención</h2>
                    </div>
                </header>

                <label class="adm-campo">
                    <span>Identificador (slug)</span>
                    <input type="text" name="slug" value="<?= $e($valores['slug']) ?>" maxlength="100"
                           pattern="[a-z0-9-]{3,100}" placeholder="ejemplo: costos-ficha" required>
                    <small>Minúsculas, números y guiones. Se usa en pruebas y reportes.</small>
                    <?= $error('slug') ?>
                </label>

                <div class="adm-grid adm-grid--dos">
                    <label class="adm-campo">
                        <span>Categoría</span>
                        <select name="categoria" required>
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categorias as $clave => $nombre): ?>
                                <option value="<?= $e($clave) ?>"<?= $valores['categoria'] === $clave ? ' selected' : '' ?>>
                                    <?= $e($nombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $error('categoria') ?>
                    </label>

                    <label class="adm-campo">
                        <span>Área responsable</span>
                        <select name="area_id" required>
                            <option value="0">Selecciona un área</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= $e((string) $area['id']) ?>"<?= (int) $valores['area_id'] === (int) $area['id'] ? ' selected' : '' ?>>
                                    <?= $e($area['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $error('area_id') ?>
                    </label>
                </div>

                <label class="adm-campo">
                    <span>Pregunta principal</span>
                    <input type="text" name="pregunta" value="<?= $e($valores['pregunta']) ?>" maxlength="255"
                           placeholder="¿Cuánto cuesta la ficha de admisión?" required>
                    <?= $error('pregunta') ?>
                </label>
            </section>

            <section class="adm-panel" aria-labelledby="adm-contenido">
                <header class="adm-panel__cabeza">
                    <div>
                        <p class="adm-eyebrow">02 · Contenido</p>
                        <h2 id="adm-contenido">Respuesta y fuente</h2>
                    </div>
                </header>

                <label class="adm-campo">
                    <span>Respuesta oficial</span>
                    <textarea name="respuesta" rows="12" maxlength="20000" required
                              placeholder="Redacta una respuesta breve, verificable y en lenguaje claro."><?= $e($valores['respuesta']) ?></textarea>
                    <small>No inventes datos. Si el sitio no publica un dato, indícalo y canaliza al área.</small>
                    <?= $error('respuesta') ?>
                </label>

                <div class="adm-grid adm-grid--dos">
                    <label class="adm-campo">
                        <span>Título de la fuente</span>
                        <input type="text" name="fuente_titulo" value="<?= $e($valores['fuente_titulo']) ?>" maxlength="160"
                               placeholder="Convocatoria 2026–2027 · Pago de ficha" required>
                        <?= $error('fuente_titulo') ?>
                    </label>
                    <label class="adm-campo">
                        <span>URL de la fuente</span>
                        <input type="url" name="fuente_url" value="<?= $e($valores['fuente_url']) ?>" maxlength="500"
                               placeholder="https://utypcb.edu.mx/convocatorias.php" required>
                        <?= $error('fuente_url') ?>
                    </label>
                </div>

                <label class="adm-campo">
                    <span>Enlaces adicionales</span>
                    <textarea name="enlaces" rows="3" maxlength="3000"
                              placeholder="Título del enlace | https://utypcb.edu.mx/ruta"><?= $e($enlacesTexto) ?></textarea>
                    <small>Uno por línea, con el formato «título | URL». Hasta cinco enlaces.</small>
                    <?= $error('enlaces') ?>
                </label>

                <label class="adm-campo">
                    <span>Palabras clave</span>
                    <input type="text" name="palabras_clave" value="<?= $e($clavesTexto) ?>" maxlength="1200"
                           placeholder="ficha, costo, examen, psicometrico" required>
                    <small>Separadas por coma. Se usan para mejorar la recuperación de la respuesta.</small>
                    <?= $error('palabras_clave') ?>
                </label>
            </section>

            <section class="adm-panel" aria-labelledby="adm-variantes">
                <header class="adm-panel__cabeza">
                    <div>
                        <p class="adm-eyebrow">03 · Lenguaje</p>
                        <h2 id="adm-variantes">Variantes de la pregunta</h2>
                    </div>
                </header>
                <p class="adm-panel__texto">
                    Las cinco variantes base son obligatorias y deben ser distintas entre sí y del resto del catálogo.
                    Incluye formas naturales, coloquiales o sin acentos.
                </p>

                <?php if (isset($errores['variantes'])): ?>
                    <div class="adm-alerta adm-alerta--error" role="alert"><?= $e($errores['variantes']) ?></div>
                <?php endif; ?>

                <?php for ($i = 0; $i < 5; $i++): ?>
                    <label class="adm-campo">
                        <span>Variante <?= $e((string) ($i + 1)) ?></span>
                        <input type="text" name="variantes[]" value="<?= $e((string) ($variantes[$i] ?? '')) ?>" maxlength="255" required>
                    </label>
                <?php endfor; ?>

                <label class="adm-campo">
                    <span>Variantes adicionales (opcional)</span>
                    <textarea name="variantes_admin" rows="4" maxlength="3000"
                              placeholder="Una variante por línea"><?= $e($adminTexto) ?></textarea>
                    <small>Hasta 10 variantes adicionales. Se conservan junto a las cinco base.</small>
                    <?= $error('variantes_admin') ?>
                </label>
            </section>
        </div>

        <aside class="adm-columna">
            <section class="adm-panel" aria-labelledby="adm-clasificacion">
                <header class="adm-panel__cabeza">
                    <div>
                        <p class="adm-eyebrow">Clasificación</p>
                        <h2 id="adm-clasificacion">Contexto</h2>
                    </div>
                </header>

                <label class="adm-campo">
                    <span>Programa</span>
                    <select name="programa_slug">
                        <option value="">Sin programa específico</option>
                        <?php foreach ($programas as $clave => $programa): ?>
                            <option value="<?= $e($clave) ?>"<?= $valores['programa_slug'] === $clave ? ' selected' : '' ?>>
                                <?= $e($programa['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $error('programa_slug') ?>
                </label>

                <label class="adm-campo">
                    <span>Tema</span>
                    <select name="tema_slug">
                        <option value="">Sin tema específico</option>
                        <?php foreach ($temas as $clave => $tema): ?>
                            <option value="<?= $e($clave) ?>"<?= $valores['tema_slug'] === $clave ? ' selected' : '' ?>>
                                <?= $e($tema['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $error('tema_slug') ?>
                </label>

                <label class="adm-campo">
                    <span>Prioridad (0–10)</span>
                    <input type="number" name="prioridad" min="0" max="10" step="1"
                           value="<?= $e((string) $valores['prioridad']) ?>">
                    <small>Las prioridades altas aparecen primero en las sugerencias iniciales.</small>
                    <?= $error('prioridad') ?>
                </label>
            </section>

            <section class="adm-panel" aria-labelledby="adm-publicacion">
                <header class="adm-panel__cabeza">
                    <div>
                        <p class="adm-eyebrow">Publicación</p>
                        <h2 id="adm-publicacion">Estado</h2>
                    </div>
                </header>

                <label class="adm-check">
                    <input type="checkbox" name="activa" value="1"<?= $activa ? ' checked' : '' ?>>
                    <span>Intención activa en el asistente</span>
                </label>

                <div class="adm-dato">
                    <span>Validación</span>
                    <strong>
                        <?php if ($validada): ?>
                            <span class="adm-pastilla adm-pastilla--ok">Validada</span>
                        <?php elseif ($intent !== null): ?>
                            <span class="adm-pastilla adm-pastilla--pendiente">Sin validar</span>
                        <?php else: ?>
                            <span class="adm-pastilla adm-pastilla--apagada">Pendiente</span>
                        <?php endif; ?>
                    </strong>
                </div>

                <?php if (is_array($intent)): ?>
                    <div class="adm-dato">
                        <span>Validada por</span>
                        <strong><?= $e($intent['validada_por'] ?? '—') ?></strong>
                    </div>
                    <div class="adm-dato">
                        <span>Fecha de validación</span>
                        <strong><?= $e($fecha($intent['validada_en'] ?? null)) ?></strong>
                    </div>
                    <div class="adm-dato">
                        <span>Creada</span>
                        <strong><?= $e($fecha($intent['creada_en'] ?? null)) ?></strong>
                    </div>
                    <div class="adm-dato">
                        <span>Última actualización</span>
                        <strong><?= $e($fecha($intent['actualizada_en'] ?? null)) ?></strong>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>

    <div class="adm-form__acciones">
        <button class="adm-boton adm-boton--principal" type="submit"><?= $intent !== null ? 'Guardar cambios' : 'Crear intención' ?></button>
        <a class="adm-boton adm-boton--fantasma" href="<?= $e($intent !== null ? $url('intent', ['id' => (int) $intent['id']]) : $url('intents')) ?>">Cancelar</a>
        <span class="adm-form__nota">Guardar reinicia la validación y actualiza el índice del motor.</span>
    </div>
</form>

<?php if (is_array($intent)): ?>
    <section class="adm-panel adm-panel--acciones" aria-labelledby="adm-mas-acciones">
        <div>
            <p class="adm-eyebrow">Acciones de la intención</p>
            <h2 id="adm-mas-acciones">Validación y estado</h2>
            <p class="adm-panel__texto">
                Validar confirma que revisaste la respuesta y la fuente. Desactivar la retira del asistente
                sin borrar su historial de métricas.
            </p>
        </div>
        <div class="adm-acciones">
            <?php if ($validada): ?>
                <form method="post" action="<?= $e($url('intent-unvalidate')) ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= $e((string) $intent['id']) ?>">
                    <button class="adm-boton adm-boton--suave" type="submit">Quitar validación</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= $e($url('intent-validate')) ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= $e((string) $intent['id']) ?>">
                    <button class="adm-boton adm-boton--suave" type="submit">Marcar como validada</button>
                </form>
            <?php endif; ?>

            <form method="post" action="<?= $e($url('intent-toggle')) ?>">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="id" value="<?= $e((string) $intent['id']) ?>">
                <input type="hidden" name="activa" value="<?= (int) $intent['activa'] === 1 ? '0' : '1' ?>">
                <button class="adm-boton adm-boton--fantasma" type="submit">
                    <?= (int) $intent['activa'] === 1 ? 'Desactivar intención' : 'Activar intención' ?>
                </button>
            </form>

            <form method="post" action="<?= $e($url('intent-delete')) ?>"
                  data-confirmar="¿Eliminar esta intención y todas sus variantes? Esta acción no se puede deshacer.">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="id" value="<?= $e((string) $intent['id']) ?>">
                <button class="adm-boton adm-boton--peligro" type="submit">Eliminar intención</button>
            </form>
        </div>
    </section>
<?php endif; ?>
