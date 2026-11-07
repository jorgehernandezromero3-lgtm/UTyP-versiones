<?php
declare(strict_types=1);

use Utypcb\Asistente\Knowledge\Matcher;
use Utypcb\Asistente\Knowledge\Responder;

require __DIR__ . '/../src/bootstrap.php';

$frases = array_slice($argv, 1);
if (!$frases) {
    $frases = [
        'cuanto cuesta la ficha',
        'hola',
        'y cuanto cuesta?',
        'donde estan ubicados',
        'cual es el telefono',
        'que carreras tienen',
    ];
}

$matcher = new Matcher();
$responder = new Responder();
$contexto = [];

foreach ($frases as $frase) {
    $inicio = microtime(true);
    $r = $matcher->resolver($frase, $contexto);
    $respuesta = $responder->construir($r);
    $ms = (microtime(true) - $inicio) * 1000;

    printf("\n> %s\n", $frase);
    printf("  tipo=%s acierto=%s confianza=%.3f ms=%.1f\n",
        $respuesta['tipo'],
        $respuesta['ok'] ? 'si' : 'no',
        $respuesta['confianza'],
        $ms
    );
    printf("  %s\n", mb_substr($respuesta['mensaje'], 0, 220));
    if (!empty($respuesta['fuente']['url'])) {
        printf("  fuente: %s\n", $respuesta['fuente']['titulo'] . ' - ' . $respuesta['fuente']['url']);
    }
    if (!empty($respuesta['sugerencias'])) {
        printf("  sugerencias:\n");
        foreach (array_slice($respuesta['sugerencias'], 0, 5) as $s) {
            printf("    - %s\n", $s['pregunta']);
        }
    }
    $contexto = $respuesta['contexto'] ?? $contexto;
}
