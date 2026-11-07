<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

final class Logger
{
    private const CLAVES_SENSIBLES = [
        'pregunta', 'texto', 'mensaje', 'mensaje_usuario', 'ip', 'correo',
        'password', 'contrasena', 'token', 'csrf', 'autorizacion',
    ];

    public static function error(string $mensaje, array $contexto = []): void
    {
        self::escribir('error.log', 'ERROR', $mensaje, $contexto);
    }

    public static function aviso(string $mensaje, array $contexto = []): void
    {
        self::escribir('error.log', 'AVISO', $mensaje, $contexto);
    }

    public static function auditoria(string $accion, array $contexto = []): void
    {
        self::escribir('auditoria.log', 'AUDITORIA', $accion, $contexto);
    }

    private static function escribir(string $archivo, string $nivel, string $mensaje, array $contexto): void
    {
        $directorio = UTYP_ASISTENTE_PRIVATE . '/logs';
        if (!is_dir($directorio)) {
            @mkdir($directorio, 0750, true);
        }

        $linea = sprintf(
            '[%s] %s %s',
            date('Y-m-d H:i:s'),
            $nivel,
            self::limpiarTexto($mensaje)
        );

        if ($contexto !== []) {
            $json = json_encode(
                self::sanear($contexto),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            );
            if ($json !== false) {
                $linea .= ' ' . $json;
            }
        }

        $linea .= PHP_EOL;
        $destino = $directorio . '/' . $archivo;
        if (@file_put_contents($destino, $linea, FILE_APPEND | LOCK_EX) === false) {
            error_log(trim($linea));
        }
    }

    private static function limpiarTexto(string $mensaje): string
    {
        $mensaje = str_replace(["\r", "\n", "\t"], ' ', $mensaje);
        if (mb_strlen($mensaje, 'UTF-8') > 500) {
            $mensaje = mb_substr($mensaje, 0, 500, 'UTF-8') . '…';
        }
        return $mensaje;
    }

    private static function sanear(array $datos): array
    {
        $salida = [];
        foreach ($datos as $clave => $valor) {
            $claveMin = mb_strtolower((string) $clave, 'UTF-8');
            if (self::esSensible($claveMin)) {
                $salida[$clave] = '[omitido]';
                continue;
            }
            if (is_array($valor)) {
                $salida[$clave] = self::sanear($valor);
            } elseif (is_object($valor)) {
                $salida[$clave] = '[objeto ' . get_class($valor) . ']';
            } elseif (is_string($valor)) {
                $salida[$clave] = self::limpiarTexto($valor);
            } elseif (is_bool($valor) || is_int($valor) || is_float($valor) || $valor === null) {
                $salida[$clave] = $valor;
            } else {
                $salida[$clave] = '[' . gettype($valor) . ']';
            }
        }
        return $salida;
    }

    private static function esSensible(string $clave): bool
    {
        foreach (self::CLAVES_SENSIBLES as $sensible) {
            if (strpos($clave, $sensible) !== false) {
                return true;
            }
        }
        return false;
    }
}
