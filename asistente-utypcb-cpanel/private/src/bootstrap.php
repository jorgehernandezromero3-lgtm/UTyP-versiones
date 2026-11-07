<?php
declare(strict_types=1);

if (!defined('UTYP_ASISTENTE_PRIVATE')) {
    define('UTYP_ASISTENTE_PRIVATE', dirname(__DIR__));
}
if (!defined('UTYP_ASISTENTE_SRC')) {
    define('UTYP_ASISTENTE_SRC', __DIR__);
}

spl_autoload_register(function (string $class): void {
    $prefix = 'Utypcb\\Asistente\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = UTYP_ASISTENTE_SRC . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$configFile = getenv('UTYP_ASISTENTE_CONFIG');
if ($configFile === false || $configFile === '') {
    $configFile = UTYP_ASISTENTE_PRIVATE . '/config.php';
}

try {
    \Utypcb\Asistente\Support\Config::load($configFile);
} catch (\Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'No se pudo cargar la configuración: ' . $e->getMessage() . PHP_EOL);
    } else {
        header('Content-Type: application/json; charset=utf-8', true, 503);
        echo '{"ok":false,"error":"Servicio no disponible."}';
    }
    exit(1);
}

date_default_timezone_set((string) \Utypcb\Asistente\Support\Config::get('app.zona_horaria', 'America/Mexico_City'));

$debug = (bool) \Utypcb\Asistente\Support\Config::get('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
$logDir = UTYP_ASISTENTE_PRIVATE . '/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-error.log');
}
