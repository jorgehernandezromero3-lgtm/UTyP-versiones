<?php
/** @var callable $e */
/** @var callable $url */
/** @var array $admins */
/** @var array $errores */
/** @var array $valores */
/** @var string $modo */
/** @var int $resetId */
$errores = isset($errores) && is_array($errores) ? $errores : [];
$valores = isset($valores) && is_array($valores) ? $valores : ['usuario' => '', 'nombre' => ''];
$modo = $modo ?? 'crear';
$resetId = (int) ($resetId ?? 0);
$campoError = static function (string $campo) use ($errores, $e): string {
    return isset($errores[$campo])
        ? '<p class="adm-error-campo" role="alert">' . $e($errores[$campo]) . '</p>'
        : '';
};
$fecha = static function (?string $valor): string {
    if ($valor === null || $valor === '') {
        return 'Sin acceso';
    }
    $ts = strtotime($valor);
    return $ts === false ? '—' : date('d/m/Y H:i', $ts);
};
$propia = static function (array $fila) use ($usuario): bool {
    return (int) $fila['id'] === (int) ($usuario['id'] ?? 0);
};
$otros = array_values(array_filter($admins, static function (array $fila) use ($propia): bool {
    return !$propia($fila);
}));
?>
<section class="adm-barra">
    <div>
        <p class="adm-eyebrow">Equipo de revisión</p>
        <h2><?= $e((string) count($admins)) ?> cuentas administrativas</h2>
        <p class="adm-barra__texto">La cuenta principal puede registrar administradores de apoyo y restablecer sus contraseñas.</p>
    </div>
</section>

<div class="adm-tabla-wrap adm-panel adm-panel--tabla">
    <table class="adm-tabla">
        <thead>
        <tr>
            <th scope="col">Administrador</th>
            <th scope="col">Rol</th>
            <th scope="col">Estado</th>
            <th scope="col">Último acceso</th>
            <th scope="col">Creado</th>
            <th scope="col"><span class="adm-oculto">Acciones</span></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $fila): ?>
            <tr>
                <td class="adm-tabla__principal">
                    <?= $e((string) $fila['nombre']) ?>
                    <small><?= $e((string) $fila['usuario']) ?><?= $propia($fila) ? ' · tu sesión' : '' ?></small>
                </td>
                <td>
                    <span class="adm-pastilla<?= $fila['rol'] === 'superadmin' ? ' adm-pastilla--ok' : '' ?>">
                        <?= $e($fila['rol'] === 'superadmin' ? 'Cuenta principal' : 'Apoyo') ?>
                    </span>
                </td>
                <td>
                    <span class="adm-pastilla<?= (int) $fila['activo'] === 1 ? ' adm-pastilla--ok' : '' ?>">
                        <?= (int) $fila['activo'] === 1 ? 'Activa' : 'Inactiva' ?>
                    </span>
                </td>
                <td><?= $e($fecha($fila['ultimo_acceso'] ?? null)) ?></td>
                <td><?= $e($fecha($fila['creado_en'] ?? null)) ?></td>
                <td>
                    <div class="adm-acciones">
                        <?php if ($propia($fila)): ?>
                            <span class="adm-tabla__nota">Gestiona tu contraseña en Mi cuenta.</span>
                        <?php else: ?>
                            <form method="post" action="<?= $e($url('admin-toggle')) ?>">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                                <input type="hidden" name="activo" value="<?= (int) $fila['activo'] === 1 ? '0' : '1' ?>">
                                <button class="adm-boton adm-boton--pequeno <?= (int) $fila['activo'] === 1 ? 'adm-boton--peligro' : 'adm-boton--suave' ?>" type="submit">
                                    <?= (int) $fila['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                            <a class="adm-boton adm-boton--pequeno adm-boton--suave" href="<?= $e($url('admins', ['reset' => (int) $fila['id']])) ?>#restablecer">
                                Restablecer contraseña
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="adm-grid adm-grid--dos">
    <section class="adm-panel" aria-labelledby="adm-nuevo-admin">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Alta de apoyo</p>
                <h2 id="adm-nuevo-admin">Registrar administrador</h2>
            </div>
        </header>
        <p class="adm-panel__texto">
            La cuenta se crea como <strong>administrador de apoyo</strong>: puede revisar intenciones,
            preguntas sin respuesta y sincronización, pero no gestionar otras cuentas.
        </p>

        <form method="post" action="<?= $e($url('admin-create')) ?>" class="adm-form" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

            <label class="adm-campo">
                <span>Usuario</span>
                <input type="text" name="usuario" value="<?= $e((string) ($valores['usuario'] ?? '')) ?>"
                       maxlength="60" pattern="[a-z0-9._-]{3,60}" autocapitalize="none" spellcheck="false" required>
                <small>Minúsculas, sin espacios. Ejemplo: apoyo.revision</small>
                <?= $modo === 'crear' ? $campoError('usuario') : '' ?>
            </label>

            <label class="adm-campo">
                <span>Nombre visible</span>
                <input type="text" name="nombre" value="<?= $e((string) ($valores['nombre'] ?? '')) ?>"
                       minlength="3" maxlength="120" required>
                <?= $modo === 'crear' ? $campoError('nombre') : '' ?>
            </label>

            <label class="adm-campo">
                <span>Contraseña inicial</span>
                <input type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required>
                <small>Entre 12 y 200 caracteres. Compártela por un medio seguro.</small>
                <?= $modo === 'crear' ? $campoError('password') : '' ?>
            </label>

            <label class="adm-campo">
                <span>Confirmar contraseña</span>
                <input type="password" name="confirmar" minlength="12" maxlength="200" autocomplete="new-password" required>
                <?= $modo === 'crear' ? $campoError('confirmar') : '' ?>
            </label>

            <button class="adm-boton adm-boton--principal" type="submit">Crear cuenta de apoyo</button>
        </form>
    </section>

    <section class="adm-panel" id="restablecer" aria-labelledby="adm-reset-password">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Soporte</p>
                <h2 id="adm-reset-password">Restablecer contraseña</h2>
            </div>
        </header>
        <p class="adm-panel__texto">
            Si un administrador de apoyo olvida su contraseña, asígnale una nueva y compártela por un medio seguro.
        </p>

        <?php if ($otros === []): ?>
            <p class="adm-vacio">Aún no hay otras cuentas para restablecer.</p>
        <?php else: ?>
            <form method="post" action="<?= $e($url('admin-password')) ?>" class="adm-form" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

                <label class="adm-campo">
                    <span>Cuenta</span>
                    <select name="id" required>
                        <option value="">Selecciona una cuenta</option>
                        <?php foreach ($otros as $fila): ?>
                            <option value="<?= (int) $fila['id'] ?>"<?= $resetId === (int) $fila['id'] ? ' selected' : '' ?>>
                                <?= $e((string) $fila['nombre']) ?> · <?= $e((string) $fila['usuario']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $modo === 'reset' ? $campoError('id') : '' ?>
                </label>

                <label class="adm-campo">
                    <span>Nueva contraseña</span>
                    <input type="password" name="nueva" minlength="12" maxlength="200" autocomplete="new-password" required>
                    <?= $modo === 'reset' ? $campoError('nueva') : '' ?>
                </label>

                <label class="adm-campo">
                    <span>Confirmar nueva contraseña</span>
                    <input type="password" name="confirmar" minlength="12" maxlength="200" autocomplete="new-password" required>
                    <?= $modo === 'reset' ? $campoError('confirmar') : '' ?>
                </label>

                <button class="adm-boton adm-boton--suave" type="submit">Actualizar contraseña</button>
            </form>
        <?php endif; ?>
    </section>
</div>
