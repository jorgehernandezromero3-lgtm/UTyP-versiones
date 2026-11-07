<?php
/** @var callable $e */
/** @var callable $url */
$errores = isset($errores) && is_array($errores) ? $errores : [];
$campoError = static function (string $campo) use ($errores, $e): string {
    return isset($errores[$campo])
        ? '<p class="adm-error-campo" role="alert">' . $e($errores[$campo]) . '</p>'
        : '';
};
$fecha = static function (?string $valor): string {
    if ($valor === null || $valor === '') {
        return 'Sin registro';
    }
    $ts = strtotime($valor);
    return $ts === false ? '—' : date('d/m/Y H:i', $ts);
};
?>
<section class="adm-barra">
    <div>
        <p class="adm-eyebrow">Seguridad</p>
        <h2>Mi cuenta administrativa</h2>
        <p class="adm-barra__texto">Cambia tu contraseña periódicamente y no la compartas con otras personas.</p>
    </div>
</section>

<div class="adm-grid adm-grid--principal">
    <section class="adm-panel" aria-labelledby="adm-datos-cuenta">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Identidad</p>
                <h2 id="adm-datos-cuenta">Datos de acceso</h2>
            </div>
        </header>
        <div class="adm-dato">
            <span>Nombre</span>
            <strong><?= $e($usuario['nombre'] ?? '') ?></strong>
        </div>
        <div class="adm-dato">
            <span>Usuario</span>
            <strong><?= $e($usuario['usuario'] ?? '') ?></strong>
        </div>
        <div class="adm-dato">
            <span>Último acceso</span>
            <strong><?= $e($fecha($usuario['ultimo_acceso'] ?? null)) ?></strong>
        </div>
        <div class="adm-dato">
            <span>Sesión</span>
            <strong>Se cierra por inactividad</strong>
        </div>
        <p class="adm-panel__texto">
            La sesión administrativa usa una cookie HttpOnly y SameSite=Lax. Los formularios están
            protegidos con un token CSRF y los intentos de acceso tienen límite por IP y usuario.
        </p>
    </section>

    <section class="adm-panel" aria-labelledby="adm-cambiar-password">
        <header class="adm-panel__cabeza">
            <div>
                <p class="adm-eyebrow">Contraseña</p>
                <h2 id="adm-cambiar-password">Actualizar credencial</h2>
            </div>
        </header>

        <form method="post" action="<?= $e($url('cuenta-save')) ?>" class="adm-form">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

            <label class="adm-campo">
                <span>Contraseña actual</span>
                <input type="password" name="actual" maxlength="200" autocomplete="current-password" required>
                <?= $campoError('actual') ?>
            </label>

            <label class="adm-campo">
                <span>Nueva contraseña</span>
                <input type="password" name="nueva" minlength="12" maxlength="200" autocomplete="new-password" required>
                <small>Entre 12 y 200 caracteres. Usa una contraseña única para este panel.</small>
                <?= $campoError('nueva') ?>
            </label>

            <label class="adm-campo">
                <span>Confirmar nueva contraseña</span>
                <input type="password" name="confirmar" minlength="12" maxlength="200" autocomplete="new-password" required>
                <?= $campoError('confirmar') ?>
            </label>

            <button class="adm-boton adm-boton--principal" type="submit">Actualizar contraseña</button>
        </form>
    </section>
</div>
