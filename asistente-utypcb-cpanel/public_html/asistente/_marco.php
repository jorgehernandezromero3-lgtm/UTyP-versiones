<?php
declare(strict_types=1);

/*
 * Marco compartido por demo.php y privacidad.php.
 * No depende de private/ ni de la base de datos: así estas páginas
 * siguen funcionando aunque la configuración del asistente falle.
 */

if (!function_exists('up_base')) {
    function up_base(): string
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '/asistente/demo.php';
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        return ($base === '' || $base === '.') ? '/asistente' : $base;
    }
}

if (!function_exists('up_e')) {
    function up_e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('up_pagina_inicio')) {
    function up_pagina_inicio(
        string $titulo,
        string $descripcion,
        string $activo = '',
        bool $esDemo = false
    ): void {
        $base = up_base();
        $tituloCompleto = $titulo . ' · Asistente Coyote UTyPCB';
        $enlaces = [
            'inicio' => ['Inicio', 'demo.php'],
            'preguntas' => ['Qué preguntar', 'demo.php#preguntas'],
            'como' => ['Cómo funciona', 'demo.php#como-funciona'],
            'privacidad' => ['Aviso de privacidad', 'privacidad.php'],
        ];
        ?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071a16">
    <meta name="description" content="<?= up_e($descripcion) ?>">
    <?php if ($esDemo): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <title><?= up_e($tituloCompleto) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= up_e($base . '/pagina.css') ?>">
</head>
<body class="up-pagina">
<a class="up-salto" href="#contenido">Saltar al contenido</a>

<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute;overflow:hidden">
    <defs>
        <symbol id="i-huella" viewBox="0 0 24 24">
            <path d="M12 12.4c2.8 0 5 2.2 5 4.7 0 2-1.6 3.2-3.4 2.8-1-.2-2.2-.2-3.2 0-1.8.4-3.4-.8-3.4-2.8 0-2.5 2.2-4.7 5-4.7Z"/>
            <path d="M6.4 8.9c.9-.3 1.9.5 2.2 1.8.3 1.2-.3 2.3-1.2 2.5-.9.3-1.9-.5-2.2-1.8-.3-1.2.3-2.3 1.2-2.5Z"/>
            <path d="M10.8 6.3c.9 0 1.6 1 1.6 2.3s-.7 2.3-1.6 2.3-1.6-1-1.6-2.3.7-2.3 1.6-2.3Z"/>
            <path d="M14.6 6.3c.9 0 1.6 1 1.6 2.3s-.7 2.3-1.6 2.3-1.6-1-1.6-2.3.7-2.3 1.6-2.3Z"/>
            <path d="M18.3 8.9c.9.2 1.5 1.3 1.2 2.5-.3 1.3-1.3 2.1-2.2 1.8-.9-.2-1.5-1.3-1.2-2.5.3-1.3 1.3-2.1 2.2-1.8Z"/>
        </symbol>
        <symbol id="i-cerrar" viewBox="0 0 24 24">
            <path d="M6 6l12 12M18 6L6 18"/>
        </symbol>
        <symbol id="i-flecha" viewBox="0 0 24 24">
            <path d="M4 12h15M13 6l6 6-6 6"/>
        </symbol>
        <symbol id="i-destello" viewBox="0 0 24 24">
            <path d="M11 3.5l1.5 4.2 4.2 1.5-4.2 1.5L11 15l-1.5-4.3L5.3 9.2l4.2-1.5L11 3.5Z"/>
            <path d="M18 14.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2Z"/>
        </symbol>
        <symbol id="i-doc" viewBox="0 0 24 24">
            <path d="M7 3.5h6.5L18 8v12.5H7Z"/>
            <path d="M13 3.5V8h5M9.5 12h6M9.5 15.5h6"/>
        </symbol>
        <symbol id="i-calendario" viewBox="0 0 24 24">
            <rect x="3.5" y="5" width="17" height="15" rx="2.5"/>
            <path d="M8 3v4M16 3v4M3.5 9.5h17M8 13h2M14 13h2M8 16.5h2"/>
        </symbol>
        <symbol id="i-beca" viewBox="0 0 24 24">
            <path d="M3 9.2L12 5l9 4.2-9 4.3L3 9.2Z"/>
            <path d="M6.5 11v4.3c0 1.4 2.5 2.7 5.5 2.7s5.5-1.3 5.5-2.7V11M20 9.5v5"/>
        </symbol>
        <symbol id="i-libro" viewBox="0 0 24 24">
            <path d="M4 4.5h5.2c1.6 0 2.8 1.3 2.8 2.9v12.1c0-1.2-1-2.2-2.2-2.2H4Z"/>
            <path d="M20 4.5h-5.2c-1.6 0-2.8 1.3-2.8 2.9v12.1c0-1.2 1-2.2 2.2-2.2H20Z"/>
        </symbol>
        <symbol id="i-escudo" viewBox="0 0 24 24">
            <path d="M12 3.5l7 2.6v5.4c0 4.2-2.9 7.3-7 9-4.1-1.7-7-4.8-7-9V6.1Z"/>
            <path d="M8.8 11.8l2.2 2.2 4.2-4.4"/>
        </symbol>
        <symbol id="i-chat" viewBox="0 0 24 24">
            <path d="M20.5 11.5c0 4.1-3.8 7.4-8.5 7.4-1 0-2-.2-2.9-.4L4 20l1.2-3.5a7 7 0 0 1-1.7-4.7c0-4.1 3.8-7.4 8.5-7.4s8.5 3.3 8.5 7.1Z"/>
            <path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/>
        </symbol>
        <symbol id="i-menu" viewBox="0 0 24 24">
            <path d="M4 7h16M4 12h16M4 17h16"/>
        </symbol>
        <symbol id="i-reloj" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="8.5"/>
            <path d="M12 7.5V12l3 2"/>
        </symbol>
        <symbol id="i-check" viewBox="0 0 24 24">
            <path d="M5 12.5l4.5 4.5L19 7"/>
        </symbol>
        <symbol id="i-externo" viewBox="0 0 24 24">
            <path d="M14 4h6v6M20 4l-8.5 8.5M18 13v5.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H11"/>
        </symbol>
    </defs>
</svg>

<?php if ($esDemo): ?>
<p class="up-demo">
    <strong>Demostración local</strong>
    <span>Asistente Coyote · respuestas desde la base de conocimiento institucional</span>
</p>
<?php endif; ?>

<header class="up-head">
    <div class="up-head__inner">
        <a class="up-marca" href="demo.php" aria-label="Asistente Coyote, ir al inicio">
            <span class="up-marca__sello" aria-hidden="true">
                <svg viewBox="0 0 24 24"><use href="#i-huella"></use></svg>
            </span>
            <span class="up-marca__texto">
                <strong>Asistente Coyote</strong>
                <span>UTyPCB · Coyuca de Benítez</span>
            </span>
        </a>
        <button class="up-head__toggle" type="button" data-menu aria-expanded="false" aria-controls="up-nav" aria-label="Abrir menú">
            <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-menu"></use></svg>
        </button>
        <nav class="up-nav" id="up-nav" aria-label="Navegación principal">
            <?php foreach ($enlaces as $clave => $enlace): ?>
                <a href="<?= up_e($enlace[1]) ?>"<?= $activo === $clave ? ' aria-current="page"' : '' ?>><?= up_e($enlace[0]) ?></a>
            <?php endforeach; ?>
            <a class="up-nav__cta" href="demo.php#preguntas">Probar asistente</a>
        </nav>
    </div>
</header>
        <?php
    }
}

if (!function_exists('up_pagina_fin')) {
    function up_pagina_fin(bool $conWidget = true, bool $esDemo = false): void
    {
        $base = up_base();
        ?>
<footer class="up-foot" role="contentinfo">
    <div class="up-foot__inner">
        <div>
            <a class="up-marca" href="demo.php" aria-label="Asistente Coyote, ir al inicio">
                <span class="up-marca__sello" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><use href="#i-huella"></use></svg>
                </span>
                <span class="up-marca__texto">
                    <strong>Asistente Coyote</strong>
                    <span>Información oficial UTyPCB</span>
                </span>
            </a>
            <p>
                Asistente virtual de la Universidad Tecnológica y Politécnica de Coyuca de Benítez.
                Responde con información publicada en el sitio institucional y cita la fuente de cada dato.
            </p>
        </div>
        <div>
            <h3>Enlaces</h3>
            <ul>
                <li><a href="demo.php">Inicio</a></li>
                <li><a href="demo.php#preguntas">Qué puedes preguntar</a></li>
                <li><a href="demo.php#como-funciona">Cómo funciona</a></li>
                <li><a href="privacidad.php">Aviso de privacidad</a></li>
                <li><a href="https://utypcb.edu.mx/" target="_blank" rel="noopener noreferrer">Sitio oficial UTyPCB</a></li>
            </ul>
        </div>
        <div>
            <h3>Contacto institucional</h3>
            <ul>
                <li><a href="mailto:serviciosescolares@utypcb.edu.mx">serviciosescolares@utypcb.edu.mx</a></li>
                <li><a href="tel:+527811003192">781 100 3192</a></li>
                <li>C. Nueva Mercado Nuevo, Centro,<br>40977 Coyuca de Benítez, Gro.</li>
            </ul>
        </div>
    </div>
    <div class="up-foot__legal">
        <span>© <?= date('Y') ?> UTyPCB · Asistente Coyote</span>
        <span>Hecho con PHP y MariaDB · Sin rastreadores publicitarios</span>
    </div>
</footer>
        <?php
        if ($conWidget) {
            $utypAsistenteBase = $base;
            $utypAsistenteAviso = $base . '/privacidad.php';
            require __DIR__ . '/widget.php';
        }
        if ($esDemo) {
            echo '<script src="' . up_e($base . '/demo.js') . '" defer></script>' . PHP_EOL;
        }
        ?>
</body>
</html>
        <?php
    }
}
