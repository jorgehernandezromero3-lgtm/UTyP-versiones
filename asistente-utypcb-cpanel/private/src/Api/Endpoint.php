<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Api;

use Throwable;
use Utypcb\Asistente\Support\Http;
use Utypcb\Asistente\Support\HttpException;
use Utypcb\Asistente\Support\Logger;

final class Endpoint
{
    public static function ejecutar(callable $accion): void
    {
        try {
            Http::cabecerasSeguridad();
            $accion();
        } catch (HttpException $e) {
            Http::error($e->status(), $e->publicMessage());
        } catch (Throwable $e) {
            Logger::error('Fallo en la API del asistente.', ['exception' => get_class($e)]);
            Http::error(500, 'Ocurrió un error al procesar tu pregunta. Inténtalo de nuevo.');
        }
    }
}
