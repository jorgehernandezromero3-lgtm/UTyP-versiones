<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Knowledge;

use Utypcb\Asistente\Support\Config;
use Utypcb\Asistente\Support\Text;

final class Matcher
{
    private const TOKENS_GENERICOS = [
        'cuanto' => true,
        'costo' => true,
        'pago' => true,
    ];

    private Repository $repo;
    private float $umbral;
    private float $margen;
    private int $maxSugerencias;
    private int $maxContexto;
    private int $ttlContexto;
    private ?array $indice = null;
    private array $idf = [];
    private int $totalVariantes = 0;

    public function __construct(?Repository $repo = null)
    {
        $this->repo = $repo instanceof Repository ? $repo : new Repository();
        $this->umbral = (float) Config::get('motor.umbral', 0.40);
        $this->margen = (float) Config::get('motor.margen', 0.02);
        $this->maxSugerencias = (int) Config::get('motor.max_sugerencias', 4);
        $this->maxContexto = max(1, (int) Config::get('motor.max_contexto', 6));
        $this->ttlContexto = max(5, (int) Config::get('motor.contexto_ttl_minutos', 30)) * 60;
    }

    public function resolver(string $mensaje, array $contexto = []): array
    {
        $normalizada = Text::normalizar($mensaje);
        $tokens = Text::tokens($mensaje);
        $ctx = $this->contextoVigente($contexto);

        $programaDetectado = Catalog::detectarPrograma($normalizada);
        $temaDetectado = Catalog::detectarTema($normalizada, $tokens);

        $conversacional = $this->detectarConversacion($normalizada, $tokens);
        if ($conversacional !== null) {
            $ctx['actualizado'] = time();
            return $this->resultado(
                $conversacional,
                null,
                0.0,
                $this->sugerenciasIniciales(),
                $ctx,
                $programaDetectado,
                $temaDetectado,
                $normalizada
            );
        }

        if ($programaDetectado !== null) {
            if ($ctx['programa'] !== null && $ctx['programa'] !== $programaDetectado) {
                $ctx['tema'] = null;
            }
            $ctx['programa'] = $programaDetectado;
        }

        if ($tokens === []) {
            $ctx['actualizado'] = time();
            return $this->resultado(
                'aclaracion',
                null,
                0.0,
                $this->sugerenciasIniciales(),
                $ctx,
                $programaDetectado,
                $temaDetectado,
                $normalizada
            );
        }

        if ($this->esPreguntaDeResultado($normalizada)) {
            $ctx['actualizado'] = time();
            return $this->resultado(
                'sin_respuesta',
                null,
                0.0,
                $this->sugerenciasIniciales(),
                $ctx,
                $programaDetectado,
                $temaDetectado,
                $normalizada
            );
        }

        $this->construirIndice();
        $programaEfectivo = $programaDetectado !== null ? $programaDetectado : $ctx['programa'];
        $seguimiento = $this->esSeguimiento($normalizada, $tokens);

        $resultados = [];
        foreach ($this->indice as $entrada) {
            $puntuado = $this->puntuarEntrada($entrada, $normalizada, $tokens, $programaEfectivo, $temaDetectado, $ctx, $seguimiento);
            if ($puntuado === null) {
                continue;
            }
            $resultados[] = $puntuado;
        }

        usort($resultados, static function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                return $b['intent']['prioridad'] <=> $a['intent']['prioridad'];
            }
            return $a['score'] < $b['score'] ? 1 : -1;
        });

        if ($resultados === []) {
            $ctx['actualizado'] = time();
            return $this->resultado('sin_respuesta', null, 0.0, [], $ctx, $programaDetectado, $temaDetectado, $normalizada);
        }

        $mejor = $resultados[0];
        if ($mejor['score'] < $this->umbral) {
            $ctx['actualizado'] = time();
            $sugerencias = $this->sugerenciasDe($resultados, null, 0.28);
            return $this->resultado('sin_respuesta', null, $mejor['score'], $sugerencias, $ctx, $programaDetectado, $temaDetectado, $normalizada);
        }

        $segundo = $resultados[1] ?? null;
        $empateExacto = $segundo !== null
            && $mejor['variante_norm'] === $normalizada
            && $segundo['variante_norm'] === $normalizada
            && abs($mejor['score'] - $segundo['score']) < 0.005;
        $ambiguo = $segundo !== null
            && (($mejor['score'] < 0.92 && ($mejor['score'] - $segundo['score']) < $this->margen) || $empateExacto);

        if ($ambiguo) {
            $ctx['actualizado'] = time();
            $sugerencias = $this->sugerenciasDe($resultados, null, 0.30);
            return $this->resultado('ambigua', $mejor['intent'], $mejor['score'], $sugerencias, $ctx, $programaDetectado, $temaDetectado, $normalizada);
        }

        $intent = $mejor['intent'];
        $ctx['intent'] = $intent['id'];
        if ($intent['programa_slug'] !== null) {
            $ctx['programa'] = $intent['programa_slug'];
        }
        if ($intent['tema_slug'] !== null) {
            $ctx['tema'] = $intent['tema_slug'];
        } elseif ($temaDetectado !== null) {
            $ctx['tema'] = $temaDetectado;
        }
        $ctx['actualizado'] = time();
        $ctx['historial'][] = [
            'intent' => $intent['id'],
            'programa' => $ctx['programa'],
            'tema' => $ctx['tema'],
            'ts' => time(),
        ];
        if (count($ctx['historial']) > $this->maxContexto) {
            $ctx['historial'] = array_slice($ctx['historial'], -$this->maxContexto);
        }

        $sugerencias = $this->sugerenciasDe($resultados, $intent['id'], 0.32);
        return $this->resultado('intencion', $intent, $mejor['score'], $sugerencias, $ctx, $programaDetectado, $temaDetectado, $normalizada);
    }

    private function resultado(
        string $tipo,
        ?array $intent,
        float $confianza,
        array $sugerencias,
        array $contexto,
        ?string $programa,
        ?string $tema,
        string $normalizada
    ): array {
        return [
            'tipo' => $tipo,
            'intent' => $intent,
            'confianza' => round($confianza, 3),
            'sugerencias' => $sugerencias,
            'contexto' => $contexto,
            'programa' => $programa,
            'tema' => $tema,
            'normalizada' => $normalizada,
        ];
    }

    private function construirIndice(): void
    {
        if ($this->indice !== null) {
            return;
        }

        $intenciones = $this->repo->intenciones();
        $df = [];
        $indice = [];

        foreach ($intenciones as $intent) {
            $variantes = [];
            foreach ($intent['variantes'] as $variante) {
                $norm = $variante['texto_norm'] !== '' ? $variante['texto_norm'] : Text::normalizar($variante['texto']);
                $tokens = Text::tokens($variante['texto']);
                $set = array_fill_keys($tokens, true);
                foreach ($tokens as $token) {
                    $df[$token] = ($df[$token] ?? 0) + 1;
                }
                $variantes[] = [
                    'texto' => $variante['texto'],
                    'norm' => $norm,
                    'tokens' => $set,
                    'tokens_lista' => $tokens,
                    'trigramas' => array_fill_keys(Text::trigramas($norm), true),
                    'idf_suma' => 0.0,
                ];
                $this->totalVariantes++;
            }

            $claves = [];
            foreach ($intent['palabras_clave'] as $frase) {
                $fraseNorm = Text::normalizar($frase);
                if ($fraseNorm === '') {
                    continue;
                }
                $claves[] = [
                    'frase' => $fraseNorm,
                    'tokens_lista' => Text::tokens($frase),
                ];
            }

            $indice[] = [
                'intent' => $intent,
                'variantes' => $variantes,
                'claves' => $claves,
            ];
        }

        $this->totalVariantes = max(1, $this->totalVariantes);
        $this->idf = [];
        foreach ($df as $token => $frecuencia) {
            $this->idf[$token] = min(6.0, log(($this->totalVariantes + 1) / ($frecuencia + 1)) + 1.0);
        }

        foreach ($indice as &$entrada) {
            foreach ($entrada['variantes'] as &$variante) {
                $suma = 0.0;
                foreach ($variante['tokens_lista'] as $token) {
                    $suma += $this->pesoIdf($token);
                }
                $variante['idf_suma'] = $suma;
            }
        }

        $this->indice = $indice;
    }

    private function puntuarEntrada(
        array $entrada,
        string $normalizada,
        array $tokens,
        ?string $programaEfectivo,
        ?string $temaDetectado,
        array $ctx,
        bool $seguimiento
    ): ?array {
        $qIdf = [];
        $qIdfSuma = 0.0;
        foreach ($tokens as $token) {
            $peso = $this->pesoIdf($token);
            $qIdf[$token] = $peso;
            $qIdfSuma += $peso;
        }
        $qTrigramas = array_fill_keys(Text::trigramas($normalizada), true);

        $mejor = null;
        foreach ($entrada['variantes'] as $variante) {
            $puntaje = $this->puntuarVariante($qIdf, $qIdfSuma, $qTrigramas, $normalizada, $variante);
            if ($puntaje === null) {
                continue;
            }
            if ($mejor === null || $puntaje > $mejor['score']) {
                $mejor = [
                    'score' => $puntaje,
                    'variante_norm' => $variante['norm'],
                    'variante_texto' => $variante['texto'],
                    'variante' => $variante,
                ];
            }
        }

        if ($mejor === null) {
            return null;
        }

        if (!$this->tieneAncla($tokens, $mejor['variante'])) {
            return null;
        }

        $score = $mejor['score'];
        $score += $this->puntajeClaves($entrada['claves'], $tokens, $normalizada);

        $intent = $entrada['intent'];
        if ($intent['programa_slug'] !== null) {
            if ($programaEfectivo !== null && $intent['programa_slug'] !== $programaEfectivo) {
                $score -= 0.25;
            } elseif ($programaEfectivo !== null) {
                $score += 0.08;
            }
        }

        if ($temaDetectado !== null && $intent['tema_slug'] === $temaDetectado) {
            $score += 0.04;
        }
        if ($seguimiento && $ctx['tema'] !== null && $intent['tema_slug'] === $ctx['tema']) {
            $score += 0.04;
        }
        $score += min(0.02, $intent['prioridad'] * 0.002);
        $score = max(0.0, min(1.0, $score));

        return [
            'intent' => $intent,
            'score' => $score,
            'variante_norm' => $mejor['variante_norm'],
            'variante_texto' => $mejor['variante_texto'],
        ];
    }

    private function puntuarVariante(array $qIdf, float $qIdfSuma, array $qTrigramas, string $normalizada, array $variante): ?float
    {
        $coincide = 0.0;
        foreach ($qIdf as $token => $peso) {
            $token = (string) $token;
            if (isset($variante['tokens'][$token])) {
                $coincide += $peso;
                continue;
            }

            $parcial = 0.0;
            foreach ($variante['tokens_lista'] as $candidato) {
                if (Text::coincidenciaDifusa($token, $candidato)) {
                    $similitud = Text::parecido($token, $candidato);
                    if ($similitud > $parcial) {
                        $parcial = $similitud;
                    }
                }
            }
            if ($parcial > 0.0) {
                $coincide += $peso * $parcial;
            }
        }

        if ($coincide <= 0.0) {
            return null;
        }

        $recall = $qIdfSuma > 0 ? $coincide / $qIdfSuma : 0.0;
        $precision = $variante['idf_suma'] > 0 ? $coincide / $variante['idf_suma'] : 0.0;
        $suma = $recall + $precision;
        $f1 = $suma > 0 ? (2 * $recall * $precision) / $suma : 0.0;
        $cobertura = 0.65 * $recall + 0.35 * $f1;

        $comunes = count(array_intersect_key($qTrigramas, $variante['trigramas']));
        $totalTrigramas = count($qTrigramas) + count($variante['trigramas']);
        $trigrama = $totalTrigramas > 0 ? (2 * $comunes) / $totalTrigramas : 0.0;

        $bonusFrase = 0.0;
        if ($normalizada === $variante['norm']) {
            $bonusFrase = 0.15;
        } elseif (mb_strlen($normalizada, 'UTF-8') >= 6 && Text::fraseContiene($variante['norm'], $normalizada)) {
            $bonusFrase = 0.06;
        } elseif (mb_strlen($variante['norm'], 'UTF-8') >= 6 && Text::fraseContiene($normalizada, $variante['norm'])) {
            $bonusFrase = 0.08;
        }

        return min(1.0, 0.70 * $cobertura + 0.20 * $trigrama + $bonusFrase);
    }

    private function puntajeClaves(array $claves, array $tokens, string $normalizada): float
    {
        $bonus = 0.0;
        $vistos = [];
        foreach ($claves as $clave) {
            if ($clave['frase'] !== '' && Text::fraseContiene($normalizada, $clave['frase'])) {
                $bonus += 0.04;
                continue;
            }
            foreach ($clave['tokens_lista'] as $token) {
                if (isset($vistos[$token])) {
                    continue;
                }
                if (in_array($token, $tokens, true)) {
                    $bonus += 0.03;
                    $vistos[$token] = true;
                }
            }
        }
        return min(0.10, $bonus);
    }

    /**
     * Si la pregunta trae una palabra que el catálogo no conoce, exigir que
     * también traiga un término institucional que no sea genérico. Evita que
     * «cuánto cuesta una pizza» se responda con el costo de la colegiatura.
     */
    private function tieneAncla(array $tokens, array $variante): bool
    {
        $hayDesconocido = false;
        foreach ($tokens as $token) {
            if (!isset($this->idf[$token])) {
                $hayDesconocido = true;
                break;
            }
        }
        if (!$hayDesconocido) {
            return true;
        }

        foreach ($tokens as $token) {
            if (isset(self::TOKENS_GENERICOS[$token])) {
                continue;
            }
            if (isset($variante['tokens'][$token])) {
                return true;
            }
            foreach ($variante['tokens_lista'] as $candidato) {
                if (Text::coincidenciaDifusa($token, $candidato)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pesoIdf(string $token): float
    {
        return $this->idf[$token] ?? 6.0;
    }

    private function sugerenciasDe(array $resultados, ?int $excluirIntent, float $minimo): array
    {
        $sugerencias = [];
        $idsVistos = [];
        foreach ($resultados as $resultado) {
            if (count($sugerencias) >= $this->maxSugerencias) {
                break;
            }
            if ($resultado['score'] < $minimo) {
                continue;
            }
            $id = $resultado['intent']['id'];
            if ($excluirIntent !== null && $id === $excluirIntent) {
                continue;
            }
            if (isset($idsVistos[$id])) {
                continue;
            }
            $idsVistos[$id] = true;
            $sugerencias[] = [
                'slug' => $resultado['intent']['slug'],
                'pregunta' => $resultado['intent']['pregunta'],
                'categoria' => $resultado['intent']['categoria'],
                'confianza' => round($resultado['score'], 3),
            ];
        }
        return $sugerencias;
    }

    private function sugerenciasIniciales(): array
    {
        $max = min(3, $this->maxSugerencias);
        return $this->repo->sugerenciasIniciales($max);
    }

    private function detectarConversacion(string $normalizada, array $tokens): ?string
    {
        if ($tokens === []) {
            if (preg_match('/^(hola|ola|holi|buen dia|buenos dias|buenas tardes|buenas noches|que tal|hey|saludos|que onda)( utypcb| asistente| coyote)?$/', $normalizada) === 1) {
                return 'saludo';
            }
            if (preg_match('/^(muchas )?gracias( por (la )?ayuda| por todo)?$/', $normalizada) === 1) {
                return 'gracias';
            }
            if (preg_match('/^(adios|hasta luego|nos vemos|bye|hasta pronto)$/', $normalizada) === 1) {
                return 'despedida';
            }
            if (preg_match('/^ayuda$/', $normalizada) === 1) {
                return 'capacidades';
            }
        }

        if (preg_match('/(quien eres|que eres|como te llamas|eres un robot|eres humano|que es este chat|quien te (hizo|creo)|para que sirves)/', $normalizada) === 1) {
            return 'identidad';
        }
        if (preg_match('/(que puedes hacer|en que me puedes ayudar|que sabes hacer|que informacion (tienes|manejas)|como funcionas|opciones|menu)/', $normalizada) === 1) {
            return 'capacidades';
        }

        return null;
    }

    /**
     * El catálogo no publica marcadores ni resultados de partidos. Detectarlos
     * evita responder con la ficha informativa del equipo y aparentar que sí.
     */
    private function esPreguntaDeResultado(string $normalizada): bool
    {
        if (preg_match('/\b(gano|ganaron|perdio|perdieron|empato|empataron|marcador|resultado)\b/', $normalizada) !== 1) {
            return false;
        }

        return preg_match('/\b(partido|juego|final|futbol|basquetbol|torneo|seleccion|equipo|coyotes)\b/', $normalizada) === 1;
    }

    private function esSeguimiento(string $normalizada, array $tokens): bool
    {
        if (preg_match('/^(y|e|entonces|ademas|tambien|ahora|bueno|ok|okay|otra|otro)\b/', $normalizada) === 1) {
            return true;
        }
        return count($tokens) <= 2;
    }

    private function contextoVigente(array $contexto): array
    {
        $base = [
            'programa' => null,
            'tema' => null,
            'intent' => 0,
            'historial' => [],
            'actualizado' => time(),
        ];
        if ($contexto === []) {
            return $base;
        }

        $actualizado = (int) ($contexto['actualizado'] ?? 0);
        if ($actualizado > 0 && time() - $actualizado > $this->ttlContexto) {
            return $base;
        }

        $programa = isset($contexto['programa']) && is_string($contexto['programa']) ? $contexto['programa'] : null;
        if ($programa !== null && Catalog::nombrePrograma($programa) === null) {
            $programa = null;
        }
        $tema = isset($contexto['tema']) && is_string($contexto['tema']) ? $contexto['tema'] : null;
        if ($tema !== null && Catalog::nombreTema($tema) === null) {
            $tema = null;
        }

        $historial = [];
        if (isset($contexto['historial']) && is_array($contexto['historial'])) {
            foreach (array_slice($contexto['historial'], -$this->maxContexto) as $item) {
                if (is_array($item) && isset($item['intent'])) {
                    $historial[] = [
                        'intent' => (int) $item['intent'],
                        'programa' => isset($item['programa']) && is_string($item['programa']) ? $item['programa'] : null,
                        'tema' => isset($item['tema']) && is_string($item['tema']) ? $item['tema'] : null,
                        'ts' => (int) ($item['ts'] ?? time()),
                    ];
                }
            }
        }

        return [
            'programa' => $programa,
            'tema' => $tema,
            'intent' => (int) ($contexto['intent'] ?? 0),
            'historial' => $historial,
            'actualizado' => $actualizado > 0 ? $actualizado : time(),
        ];
    }
}
