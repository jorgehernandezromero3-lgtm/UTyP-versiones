<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Api;

use Utypcb\Asistente\Knowledge\Matcher;
use Utypcb\Asistente\Knowledge\Repository;
use Utypcb\Asistente\Knowledge\Responder;
use Utypcb\Asistente\Support\Config;
use Utypcb\Asistente\Support\Http;
use Utypcb\Asistente\Support\HttpException;
use Utypcb\Asistente\Support\Privacy;
use Utypcb\Asistente\Support\RateLimiter;
use Utypcb\Asistente\Support\Text;

final class ChatApi
{
    private const TIPOS_CONVERSACIONALES = [
        'saludo', 'gracias', 'despedida', 'identidad', 'capacidades', 'aclaracion',
    ];

    public static function estado(): void
    {
        Endpoint::ejecutar(static function (): void {
            Http::exigirMetodo('GET');
            Http::sesionChat();

            $repo = new Repository();
            $sugerencias = $repo->sugerenciasIniciales(4);
            $maximo = max(10, (int) Config::get('motor.max_mensaje', 300));
            $minimo = max(1, (int) Config::get('motor.min_mensaje', 2));

            Http::json([
                'ok' => true,
                'csrf' => Http::csrfToken(),
                'limites' => [
                    'min_mensaje' => $minimo,
                    'max_mensaje' => $maximo,
                ],
                'privacidad' => [
                    'url' => (string) Config::get('privacidad.aviso_url', '/asistente/privacidad.php'),
                    'responsable' => (string) Config::get('privacidad.responsable', ''),
                ],
                'sugerencias' => $sugerencias,
            ]);
        });
    }

    public static function chat(): void
    {
        Endpoint::ejecutar(static function (): void {
            Http::exigirMetodo('POST');
            Http::exigirOrigen();
            Http::sesionChat();
            Http::exigirCsrf();

            $datos = Http::leerJson(4096);
            $accion = isset($datos['accion']) && is_string($datos['accion']) ? $datos['accion'] : 'mensaje';

            if ($accion === 'reiniciar') {
                unset($_SESSION['chat_ctx'], $_SESSION['chat_ultimo']);
                Http::json(['ok' => true, 'reiniciado' => true]);
            }
            if ($accion !== 'mensaje') {
                throw new HttpException(422, 'La acción solicitada no es válida.');
            }

            $mensaje = self::mensaje($datos['mensaje'] ?? null);
            self::limitar();

            $inicio = microtime(true);
            $repo = new Repository();
            $contexto = isset($_SESSION['chat_ctx']) && is_array($_SESSION['chat_ctx'])
                ? $_SESSION['chat_ctx']
                : [];

            $resultado = (new Matcher($repo))->resolver($mensaje, $contexto);
            $respuesta = (new Responder($repo))->construir($resultado);
            $ms = (int) round((microtime(true) - $inicio) * 1000);

            $_SESSION['chat_ctx'] = $resultado['contexto'];
            $_SESSION['chat_ultimo'] = time();
            self::registrar($repo, $resultado, $respuesta, $mensaje, $ms);

            Http::json($respuesta);
        });
    }

    private static function mensaje($valor): string
    {
        if (!is_string($valor)) {
            throw new HttpException(422, 'Escribe tu pregunta como texto.');
        }

        $mensaje = trim(str_replace("\0", '', $valor));
        $mensaje = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $mensaje) ?? $mensaje;
        $mensaje = preg_replace("/\n{3,}/u", "\n\n", $mensaje) ?? $mensaje;

        $minimo = max(1, (int) Config::get('motor.min_mensaje', 2));
        $maximo = max(10, (int) Config::get('motor.max_mensaje', 300));
        $largo = mb_strlen($mensaje, 'UTF-8');

        if ($largo < $minimo) {
            throw new HttpException(422, 'Escribe una pregunta un poco más larga para poder ayudarte.');
        }
        if ($largo > $maximo) {
            throw new HttpException(422, 'Tu pregunta es demasiado larga. El máximo es de ' . $maximo . ' caracteres.');
        }

        return $mensaje;
    }

    private static function limitar(): void
    {
        $limites = Config::get('seguridad.limites', []);
        $sesion = is_array($limites['chat_sesion'] ?? null)
            ? $limites['chat_sesion']
            : ['limite' => 20, 'ventana' => 300];
        $ip = is_array($limites['chat_ip'] ?? null)
            ? $limites['chat_ip']
            : ['limite' => 120, 'ventana' => 600];

        $limitador = new RateLimiter();
        $identidad = session_id() !== '' ? session_id() : 'sin-sesion';
        $resultadoSesion = $limitador->consumir(
            'chat_sesion',
            $identidad,
            (int) ($sesion['limite'] ?? 20),
            (int) ($sesion['ventana'] ?? 300)
        );
        $resultadoIp = $limitador->consumir(
            'chat_ip',
            Http::ip(),
            (int) ($ip['limite'] ?? 120),
            (int) ($ip['ventana'] ?? 600)
        );

        if ($resultadoSesion['permitido'] && $resultadoIp['permitido']) {
            return;
        }

        $espera = max($resultadoSesion['reintentar'], $resultadoIp['reintentar']);
        if (!headers_sent()) {
            header('Retry-After: ' . $espera);
        }
        throw new HttpException(
            429,
            'Has hecho muchas preguntas en poco tiempo. Espera ' . $espera . ' segundos e inténtalo de nuevo.'
        );
    }

    private static function registrar(
        Repository $repo,
        array $resultado,
        array $respuesta,
        string $mensaje,
        int $ms
    ): void {
        $tipo = (string) ($resultado['tipo'] ?? 'sin_respuesta');
        if (in_array($tipo, self::TIPOS_CONVERSACIONALES, true)) {
            return;
        }

        $acierto = $tipo === 'intencion';
        $intentId = $acierto && isset($resultado['intent']['id']) ? (int) $resultado['intent']['id'] : null;
        $repo->registrarEvento($intentId, $acierto, (float) ($respuesta['confianza'] ?? 0.0), $ms);

        if ($tipo === 'sin_respuesta') {
            $redactado = Privacy::redactar($mensaje);
            $repo->registrarNoRespondida(
                $redactado,
                Text::normalizar($redactado),
                Http::hashIdentidad(Http::ip())
            );
        }
    }
}
