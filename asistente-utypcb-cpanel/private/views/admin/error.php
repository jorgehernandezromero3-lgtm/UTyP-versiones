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
    <title>Error <?= $e((string) $status) ?> · Asistente Coyote</title>
    <link rel="stylesheet" href="<?= $e($base) ?>/admin.css">
</head>
<body class="adm-login">
<main class="adm-error">
    <p class="adm-eyebrow">Panel administrativo</p>
    <p class="adm-error__codigo"><?= $e((string) $status) ?></p>
    <h1><?= $status === 403 ? 'La solicitud no es válida.' : ($status === 404 ? 'No encontramos esa página.' : 'Algo no salió como esperábamos.') ?></h1>
    <p><?= $e($mensaje) ?></p>
    <div class="adm-error__acciones">
        <a class="adm-boton adm-boton--principal" href="<?= $e($url('dashboard')) ?>">Volver al panel</a>
        <a class="adm-boton adm-boton--fantasma" href="<?= $e($url('login')) ?>">Ir al acceso</a>
    </div>
</main>
</body>
</html>
