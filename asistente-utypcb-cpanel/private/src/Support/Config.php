<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

use RuntimeException;

final class Config
{
    private static array $data = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            throw new RuntimeException('Falta private/config.php. Usa config.example.php como base.');
        }
        $data = require $file;
        if (!is_array($data)) {
            throw new RuntimeException('config.php debe devolver un arreglo.');
        }
        self::$data = $data;
        self::validate();
    }

    public static function get(string $key, $default = null)
    {
        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function all(): array
    {
        return self::$data;
    }

    public static function debug(): bool
    {
        return (bool) self::get('app.debug', false);
    }

    private static function validate(): void
    {
        $required = ['dsn', 'usuario', 'password'];
        foreach ($required as $key) {
            if (!array_key_exists($key, self::$data['db'] ?? [])) {
                throw new RuntimeException('Configuración de base de datos incompleta.');
            }
        }

        $salt = (string) self::get('seguridad.ip_salt', '');
        if (strlen($salt) < 32 || strpos($salt, 'CAMBIA') !== false) {
            throw new RuntimeException('Configura una clave ip_salt aleatoria de al menos 32 caracteres.');
        }

        $limits = self::get('seguridad.limites', []);
        if (!is_array($limits) || !isset($limits['chat_sesion'], $limits['chat_ip'], $limits['admin_login'])) {
            throw new RuntimeException('Faltan los límites de solicitudes en la configuración.');
        }
    }
}
