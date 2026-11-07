<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

final class Privacy
{
    public static function redactar(string $texto): string
    {
        $texto = preg_replace('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/u', '[correo]', $texto) ?? $texto;
        $texto = preg_replace('/\b[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d\b/iu', '[CURP]', $texto) ?? $texto;
        $texto = preg_replace(
            '/\b(?:\+?52[\s.-]?)?(?:\(\d{2,3}\)[\s.-]?)?\d{3}[\s.-]?\d{3}[\s.-]?\d{4}\b/u',
            '[teléfono]',
            $texto
        ) ?? $texto;
        $texto = preg_replace('/\b\d{8,}\b/u', '[número]', $texto) ?? $texto;
        return trim($texto);
    }
}
