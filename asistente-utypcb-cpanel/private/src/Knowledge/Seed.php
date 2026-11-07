<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Knowledge;

use RuntimeException;

final class Seed
{
    public static function areas(string $directorio): array
    {
        $archivo = rtrim($directorio, '/\\') . '/areas.php';
        if (!is_file($archivo)) {
            throw new RuntimeException('No existe el archivo de áreas.');
        }
        $datos = require $archivo;
        if (!is_array($datos)) {
            throw new RuntimeException('El archivo de áreas no devuelve un arreglo.');
        }
        return $datos;
    }

    public static function intenciones(string $directorio): array
    {
        $archivos = glob(rtrim($directorio, '/\\') . '/*.php') ?: [];
        sort($archivos, SORT_STRING);

        $salida = [];
        $vistos = [];
        foreach ($archivos as $archivo) {
            if (basename($archivo) === 'areas.php') {
                continue;
            }
            $datos = require $archivo;
            if (!is_array($datos)) {
                throw new RuntimeException('El archivo ' . basename($archivo) . ' no devuelve un arreglo.');
            }
            foreach ($datos as $intencion) {
                if (!is_array($intencion)) {
                    throw new RuntimeException('Hay una intención inválida en ' . basename($archivo) . '.');
                }
                $slug = (string) ($intencion['slug'] ?? '');
                if ($slug === '') {
                    throw new RuntimeException('Hay una intención sin slug en ' . basename($archivo) . '.');
                }
                if (isset($vistos[$slug])) {
                    throw new RuntimeException('Slug duplicado en la semilla: ' . $slug);
                }
                $vistos[$slug] = true;
                $intencion['_archivo'] = basename($archivo);
                $salida[] = $intencion;
            }
        }

        return $salida;
    }
}
