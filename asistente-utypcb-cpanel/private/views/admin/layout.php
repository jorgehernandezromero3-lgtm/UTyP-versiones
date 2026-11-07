<?php
/** @var callable $e */
/** @var callable $url */
/** @var array $usuario */
/** @var array|null $flash */
$nombre = (string) ($usuario['nombre'] ?? 'Administrador');
$inicial = mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'), 'UTF-8');
$pendientes = (int) ($pendientes ?? 0);
$esSuperadmin = (string) ($usuario['rol'] ?? 'admin') === 'superadmin';
$nav = [
    'dashboard' => ['Panel general', 'Resumen y pendientes'],
    'intents' => ['Intenciones', 'Base de conocimiento'],
    'unanswered' => ['Sin respuesta', 'Preguntas por revisar'],
    'sync' => ['Sincronización', 'Datos del sitio y retención'],
    'cuenta' => ['Mi cuenta', 'Seguridad de acceso'],
];
if ($esSuperadmin) {
    $nav['admins'] = ['Administradores', 'Altas y accesos de apoyo'];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#071a16">
    <title><?= $e($titulo) ?> · Asistente Coyote</title>
    <link rel="stylesheet" href="<?= $e($base) ?>/admin.css">
    <script src="<?= $e($base) ?>/admin.js" defer></script>
</head>
<body class="adm">
<a class="adm-skip" href="#contenido">Saltar al contenido</a>
<div class="adm-shell">
    <aside class="adm-sidebar">
        <a class="adm-marca" href="<?= $e($url('dashboard')) ?>">
            <span class="adm-marca__sello" aria-hidden="true">AC</span>
            <span class="adm-marca__texto">
                <strong>Asistente Coyote</strong>
                <small>UTyPCB · Administración</small>
            </span>
        </a>

        <nav class="adm-nav" aria-label="Navegación administrativa">
            <?php foreach ($nav as $clave => $item): ?>
                <a href="<?= $e($url($clave)) ?>"<?= $activo === $clave ? ' aria-current="page"' : '' ?>>
                    <span><?= $e($item[0]) ?></span>
                    <?php if ($clave === 'unanswered' && $pendientes > 0): ?>
                        <b class="adm-nav__badge" aria-label="<?= $e((string) $pendientes) ?> pendientes"><?= $e((string) $pendientes) ?></b>
                    <?php else: ?>
                        <small><?= $e($item[1]) ?></small>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="adm-sidebar__pie">
            <div class="adm-usuario">
                <span class="adm-avatar" aria-hidden="true"><?= $e($inicial) ?></span>
                <span>
                    <strong><?= $e($nombre) ?></strong>
                    <small><?= $e($usuario['usuario'] ?? '') ?></small>
                </span>
            </div>
            <form method="post" action="<?= $e($url('logout')) ?>">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="adm-boton adm-boton--fantasma adm-boton--ancho" type="submit">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="adm-main">
        <header class="adm-top">
            <div>
                <p class="adm-eyebrow">Administración del asistente</p>
                <h1><?= $e($titulo) ?></h1>
            </div>
            <div class="adm-top__acciones">
                <a class="adm-boton adm-boton--suave" href="<?= $e($url('intent')) ?>">Nueva intención</a>
                <a class="adm-boton adm-boton--suave" href="<?= $e($url('unanswered')) ?>">
                    Revisar pendientes<?= $pendientes > 0 ? ' (' . $e((string) $pendientes) . ')' : '' ?>
                </a>
            </div>
        </header>

        <?php if (is_array($flash)): ?>
            <div class="adm-alerta adm-alerta--<?= $e($flash['tipo']) ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>">
                <?= $e($flash['mensaje']) ?>
            </div>
        <?php endif; ?>

        <main id="contenido" class="adm-contenido">
            <?= $contenido ?>
        </main>

        <footer class="adm-pie">
            <p>Asistente Coyote · UTyPCB · Uso interno</p>
            <p>Las preguntas sin respuesta se muestran redactadas. No compartas datos personales.</p>
        </footer>
    </div>
</div>
</body>
</html>
