<?php
declare(strict_types=1);

use Utypcb\Asistente\Knowledge\Catalog;
use Utypcb\Asistente\Knowledge\Matcher;
use Utypcb\Asistente\Knowledge\Repository;
use Utypcb\Asistente\Knowledge\Responder;

require __DIR__ . '/../src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por consola.\n");
    exit(1);
}

$archivo = dirname(__DIR__, 2) . '/tests/preguntas_100.php';
if (!is_file($archivo)) {
    fwrite(STDERR, 'No existe el archivo de preguntas: ' . $archivo . PHP_EOL);
    exit(1);
}

$preguntas = require $archivo;
if (!is_array($preguntas) || count($preguntas) !== 100) {
    fwrite(STDERR, 'El conjunto de evaluación debe contener exactamente 100 preguntas.' . PHP_EOL);
    exit(1);
}

$repoValidacion = new Repository();
foreach ($preguntas as $indice => $pregunta) {
    if (!isset($pregunta['q'], $pregunta['slug'], $pregunta['categoria'])) {
        fwrite(STDERR, 'Pregunta #' . ($indice + 1) . ': estructura incompleta.' . PHP_EOL);
        exit(1);
    }
    if ($repoValidacion->intencionPorSlug((string) $pregunta['slug']) === null) {
        fwrite(STDERR, 'Pregunta #' . ($indice + 1) . ': intención inexistente (' . $pregunta['slug'] . ').' . PHP_EOL);
        exit(1);
    }
}

$objetivo = 85.0;
$categorias = Catalog::categorias();

$calentar = static function (Matcher $matcher, Responder $responder): void {
    $r = $matcher->resolver('cuanto cuesta la ficha', []);
    $responder->construir($r);
};

$medir = static function (Matcher $matcher, Responder $responder, string $texto, array $contexto): array {
    $inicio = microtime(true);
    $resultado = $matcher->resolver($texto, $contexto);
    $respuesta = $responder->construir($resultado);
    $ms = (microtime(true) - $inicio) * 1000.0;

    return [
        'resultado' => $resultado,
        'respuesta' => $respuesta,
        'ms' => $ms,
    ];
};

$matcherFrio = new Matcher();
$responderFrio = new Responder();
$inicioFrio = microtime(true);
$resultadoFrio = $matcherFrio->resolver('que carreras ofrece la utypcb', []);
$responderFrio->construir($resultadoFrio);
$msFrio = (microtime(true) - $inicioFrio) * 1000.0;

$matcher = new Matcher();
$responder = new Responder();
$calentar($matcher, $responder);

$resultados = [];
$aciertos = 0;
$porCategoria = [];
$contextosTotal = 0;
$contextosAciertos = 0;
$tiempos = [];

foreach ($preguntas as $indice => $pregunta) {
    $contexto = [];
    foreach (($pregunta['antes'] ?? []) as $previo) {
        $rAntes = $matcher->resolver((string) $previo, $contexto);
        $contexto = $rAntes['contexto'];
    }

    $corrida = $medir($matcher, $responder, (string) $pregunta['q'], $contexto);
    $resultado = $corrida['resultado'];
    $respuesta = $corrida['respuesta'];
    $intent = is_array($resultado['intent']) ? $resultado['intent'] : null;
    $obtenido = $intent['slug'] ?? null;
    $correcto = $resultado['tipo'] === 'intencion' && $obtenido === (string) $pregunta['slug'];
    $esContexto = isset($pregunta['antes']) && $pregunta['antes'] !== [];

    $aciertos += $correcto ? 1 : 0;
    $categoria = (string) $pregunta['categoria'];
    $porCategoria[$categoria] = $porCategoria[$categoria] ?? ['total' => 0, 'aciertos' => 0];
    $porCategoria[$categoria]['total']++;
    $porCategoria[$categoria]['aciertos'] += $correcto ? 1 : 0;

    if ($esContexto) {
        $contextosTotal++;
        $contextosAciertos += $correcto ? 1 : 0;
    }

    $tiempos[] = $corrida['ms'];

    $resultados[] = [
        'n' => $indice + 1,
        'categoria' => $categoria,
        'pregunta' => (string) $pregunta['q'],
        'esperado' => (string) $pregunta['slug'],
        'obtenido' => $obtenido,
        'tipo' => (string) $resultado['tipo'],
        'confianza' => (float) $resultado['confianza'],
        'ms' => round($corrida['ms'], 2),
        'correcto' => $correcto,
        'contexto' => $esContexto,
    ];
}

sort($tiempos);
$total = count($resultados);
$precision = $total > 0 ? ($aciertos / $total) * 100.0 : 0.0;
$promedio = array_sum($tiempos) / max(1, count($tiempos));
$p95 = $tiempos[(int) ceil(0.95 * count($tiempos)) - 1] ?? 0.0;
$maximo = $tiempos[count($tiempos) - 1] ?? 0.0;
$cumple = $precision >= $objetivo;

$fallos = array_values(array_filter($resultados, static fn(array $r): bool => !$r['correcto']));

$resumen = [
    'generado_en' => date('c'),
    'objetivo_precision' => $objetivo,
    'total' => $total,
    'aciertos' => $aciertos,
    'precision' => round($precision, 2),
    'cumple' => $cumple,
    'contexto' => [
        'total' => $contextosTotal,
        'aciertos' => $contextosAciertos,
    ],
    'latencia' => [
        'frio_ms' => round($msFrio, 2),
        'promedio_ms' => round($promedio, 2),
        'p95_ms' => round($p95, 2),
        'maximo_ms' => round($maximo, 2),
    ],
    'por_categoria' => $porCategoria,
    'fallos' => $fallos,
    'resultados' => $resultados,
];

$directorio = dirname(__DIR__, 2) . '/tests/resultados';
if (!is_dir($directorio)) {
    mkdir($directorio, 0775, true);
}
file_put_contents(
    $directorio . '/evaluacion_100.json',
    json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
);

$lineas = [];
$lineas[] = '# Evaluación del motor de respuestas';
$lineas[] = '';
$lineas[] = '- Fecha: ' . date('Y-m-d H:i:s');
$lineas[] = '- Preguntas evaluadas: ' . $total;
$lineas[] = '- Objetivo de precisión: ' . number_format($objetivo, 1, '.', '') . '%';
$lineas[] = '- Resultado: **' . $aciertos . '/' . $total . ' (' . number_format($precision, 1, '.', '') . '%)**';
$lineas[] = '- Verificación del objetivo: **' . ($cumple ? 'CUMPLE' : 'NO CUMPLE') . '**';
$lineas[] = '';
$lineas[] = '## Precisión por categoría';
$lineas[] = '';
$lineas[] = '| Categoría | Aciertos | Total | Precisión |';
$lineas[] = '| --- | ---: | ---: | ---: |';
foreach ($porCategoria as $cat => $datos) {
    $pct = $datos['total'] > 0 ? ($datos['aciertos'] / $datos['total']) * 100.0 : 0.0;
    $lineas[] = '| ' . ($categorias[$cat] ?? $cat) . ' | ' . $datos['aciertos'] . ' | ' . $datos['total'] . ' | ' . number_format($pct, 1, '.', '') . '% |';
}
$lineas[] = '';
$lineas[] = '## Contexto conversacional';
$lineas[] = '';
$lineas[] = '- Casos con seguimiento: ' . $contextosTotal;
$lineas[] = '- Aciertos: ' . $contextosAciertos;
$lineas[] = '';
$lineas[] = '## Latencia';
$lineas[] = '';
$lineas[] = '- Primera respuesta (índice en frío): ' . number_format($msFrio, 1, '.', '') . ' ms';
$lineas[] = '- Promedio: ' . number_format($promedio, 1, '.', '') . ' ms';
$lineas[] = '- Percentil 95: ' . number_format($p95, 1, '.', '') . ' ms';
$lineas[] = '- Máximo: ' . number_format($maximo, 1, '.', '') . ' ms';
$lineas[] = '- Límite solicitado: 2000 ms';
$lineas[] = '';
$lineas[] = '## Preguntas no acertadas';
$lineas[] = '';
if ($fallos === []) {
    $lineas[] = 'Ninguna.';
} else {
    $lineas[] = '| # | Pregunta | Esperado | Obtenido | Tipo | Confianza |';
    $lineas[] = '| ---: | --- | --- | --- | --- | ---: |';
    foreach ($fallos as $fallo) {
        $lineas[] = '| ' . $fallo['n'] . ' | ' . str_replace('|', '\\|', $fallo['pregunta']) . ' | '
            . $fallo['esperado'] . ' | ' . ($fallo['obtenido'] ?? '—') . ' | ' . $fallo['tipo'] . ' | '
            . number_format($fallo['confianza'], 3, '.', '') . ' |';
    }
}
$lineas[] = '';
file_put_contents($directorio . '/evaluacion_100.md', implode(PHP_EOL, $lineas) . PHP_EOL);

echo 'Preguntas: ' . $total . PHP_EOL;
echo 'Aciertos: ' . $aciertos . ' (' . number_format($precision, 1, '.', '') . '%)' . PHP_EOL;
echo 'Objetivo: ' . number_format($objetivo, 1, '.', '') . '% -> ' . ($cumple ? 'CUMPLE' : 'NO CUMPLE') . PHP_EOL;
echo 'Contexto: ' . $contextosAciertos . '/' . $contextosTotal . PHP_EOL;
echo 'Latencia fría: ' . number_format($msFrio, 1, '.', '') . ' ms | promedio: ' . number_format($promedio, 1, '.', '') . ' ms | p95: ' . number_format($p95, 1, '.', '') . ' ms' . PHP_EOL;
echo 'Evidencia: tests/resultados/evaluacion_100.json y evaluacion_100.md' . PHP_EOL;

if ($fallos !== []) {
    echo PHP_EOL . 'Fallos:' . PHP_EOL;
    foreach ($fallos as $fallo) {
        printf(
            "  %3d. [%s] esperado=%s obtenido=%s tipo=%s conf=%.3f\n",
            $fallo['n'],
            $fallo['categoria'],
            $fallo['esperado'],
            $fallo['obtenido'] ?? '—',
            $fallo['tipo'],
            $fallo['confianza']
        );
    }
}

exit($cumple ? 0 : 1);
