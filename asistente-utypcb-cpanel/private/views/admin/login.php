<?php
/** @var callable $e */
/** @var callable $url */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#071a16">
    <title>Acceso administrativo · Asistente Coyote</title>
    <link rel="stylesheet" href="<?= $e($base) ?>/admin.css">
</head>
<body class="adm-login">
<main class="adm-login__marco">
    <section class="adm-login__marca" aria-labelledby="adm-marca-titulo">
        <div class="adm-login__sello" aria-hidden="true">AC</div>
        <p class="adm-eyebrow">Universidad Tecnológica y Politécnica de Coyuca de Benítez</p>
        <h1 id="adm-marca-titulo">Asistente <em>Coyote</em></h1>
        <p class="adm-login__bajada">
            Administra la base de conocimiento, revisa las preguntas que el asistente no pudo responder
            y mantén actualizada la información oficial.
        </p>
        <ul class="adm-login__lista">
            <li>Respuestas verificables con fuente institucional.</li>
            <li>Revisión de preguntas redactadas y métricas de uso.</li>
            <li>Sincronización con los datos publicados del sitio.</li>
        </ul>
        <p class="adm-login__pie">Acceso restringido a personal autorizado.</p>
    </section>

    <section class="adm-login__panel" aria-labelledby="adm-login-titulo">
        <div class="adm-login__caja">
            <p class="adm-eyebrow">Panel interno</p>
            <h2 id="adm-login-titulo">Inicia sesión</h2>
            <p class="adm-login__ayuda">Usa la cuenta administrativa asignada por la universidad.</p>

            <?php if ($motivo === 'sesion'): ?>
                <div class="adm-alerta adm-alerta--info" role="status">
                    Tu sesión expiró o necesitas iniciar sesión para continuar.
                </div>
            <?php elseif ($motivo === 'salir'): ?>
                <div class="adm-alerta adm-alerta--ok" role="status">
                    Cerraste sesión correctamente.
                </div>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="adm-alerta adm-alerta--error" role="alert"><?= $e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= $e($url('login')) ?>" class="adm-form">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <label class="adm-campo">
                    <span>Usuario</span>
                    <input type="text" name="usuario" value="<?= $e($usuario ?? '') ?>" maxlength="60"
                           autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                </label>
                <label class="adm-campo">
                    <span>Contraseña</span>
                    <input type="password" name="password" maxlength="200" autocomplete="current-password" required>
                </label>
                <button class="adm-boton adm-boton--principal adm-boton--ancho" type="submit">Entrar al panel</button>
            </form>

            <p class="adm-login__nota">
                Si no tienes acceso, solicita una cuenta al responsable del asistente en Servicios Escolares.
                No escribas contraseñas en el chat.
            </p>
        </div>
    </section>
</main>
</body>
</html>
