<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

use RuntimeException;

final class View
{
    public static function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function render(string $vista, array $datos = []): void
    {
        $archivo = self::ruta($vista);
        extract($datos, EXTR_SKIP);
        $e = static function ($valor): string {
            return self::e($valor);
        };
        require $archivo;
    }

    public static function capturar(string $vista, array $datos = []): string
    {
        ob_start();
        try {
            self::render($vista, $datos);
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    private static function ruta(string $vista): string
    {
        if (!preg_match('/^[a-z0-9_\/-]+$/', $vista)) {
            throw new RuntimeException('Vista no válida.');
        }
        $archivo = UTYP_ASISTENTE_PRIVATE . '/views/' . $vista . '.php';
        if (!is_file($archivo)) {
            throw new RuntimeException('No existe la vista solicitada.');
        }
        return $archivo;
    }
}
