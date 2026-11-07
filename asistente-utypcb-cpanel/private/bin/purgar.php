<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Utypcb\Asistente\Knowledge\Repository;
use Utypcb\Asistente\Support\Config;
use Utypcb\Asistente\Support\Logger;
use Utypcb\Asistente\Support\RateLimiter;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por consola.\n");
    exit(1);
}

$diasNoRespondidas = max(1, (int) Config::get('privacidad.retencion_no_respondidas_dias', 90));
$diasEventos = max(1, (int) Config::get('privacidad.retencion_eventos_dias', 365));

try {
    $resultado = (new Repository())->purgar($diasNoRespondidas, $diasEventos);
    $limites = (new RateLimiter())->limpiar();
    Logger::auditoria('Purga programada de datos', [
        'no_respondidas' => $resultado['no_respondidas'],
        'eventos' => $resultado['eventos'],
        'limites' => $limites,
    ]);
    fwrite(STDOUT, 'Preguntas sin respuesta eliminadas: ' . $resultado['no_respondidas'] . PHP_EOL);
    fwrite(STDOUT, 'Eventos agregados eliminados: ' . $resultado['eventos'] . PHP_EOL);
    fwrite(STDOUT, 'Contadores de límite eliminados: ' . $limites . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo ejecutar la purga: ' . $e->getMessage() . PHP_EOL);
    Logger::error('Falló la purga programada de datos.', ['exception' => get_class($e)]);
    exit(1);
}
