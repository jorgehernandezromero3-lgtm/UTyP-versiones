<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Utypcb\Asistente\Site\Sync;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por consola.\n");
    exit(1);
}

$resultado = Sync::ejecutar('cron');
fwrite(STDOUT, (string) $resultado['mensaje'] . PHP_EOL);
fwrite(STDOUT, 'Detalle: ' . (string) $resultado['detalle'] . PHP_EOL);
fwrite(STDOUT, 'Actualizados: ' . (int) $resultado['actualizados'] . PHP_EOL);
exit($resultado['estado'] === 'error' ? 1 : 0);
