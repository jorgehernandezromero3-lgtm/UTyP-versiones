<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Admin;

use Utypcb\Asistente\Support\Config;
use Utypcb\Asistente\Support\Http;
use Utypcb\Asistente\Support\HttpException;
use Utypcb\Asistente\Support\Logger;
use Utypcb\Asistente\Support\RateLimiter;

final class Auth
{
    private const HASH_FICTICIO = '$2y$12$gJVFTLOxytJiUkz4qtznk.8Janh7eL/SJGI1PtR01EZbIfXEb97FK';

    private AdminRepository $repo;

    public function __construct(?AdminRepository $repo = null)
    {
        $this->repo = $repo instanceof AdminRepository ? $repo : new AdminRepository();
    }

    public function requerir(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new HttpException(401, 'La sesión administrativa no está activa.');
        }

        $id = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
        $ultimo = isset($_SESSION['admin_ultimo']) ? (int) $_SESSION['admin_ultimo'] : 0;
        if ($id <= 0 || $ultimo <= 0) {
            throw new HttpException(401, 'Inicia sesión para continuar.');
        }

        $inactividad = max(5, (int) Config::get('seguridad.admin_idle_minutos', 30)) * 60;
        if (time() - $ultimo > $inactividad) {
            $this->salir(false);
            throw new HttpException(401, 'La sesión expiró por inactividad.');
        }

        $usuario = $this->repo->usuarioPorId($id);
        if ($usuario === null || (int) $usuario['activo'] !== 1) {
            $this->salir(false);
            throw new HttpException(401, 'La cuenta administrativa ya no está disponible.');
        }

        $_SESSION['admin_ultimo'] = time();
        return $usuario;
    }

    /**
     * @return array{ok:bool,error?:string,reintentar?:int}
     */
    public function intentar(string $usuario, string $password): array
    {
        $usuario = mb_strtolower(trim($usuario), 'UTF-8');
        if ($usuario === '' || $password === '') {
            return ['ok' => false, 'error' => 'Escribe tu usuario y contraseña.'];
        }

        $limites = Config::get('seguridad.limites.admin_login', []);
        $maximo = is_array($limites) ? max(1, (int) ($limites['limite'] ?? 5)) : 5;
        $ventana = is_array($limites) ? max(30, (int) ($limites['ventana'] ?? 900)) : 900;

        $limitador = new RateLimiter();
        $porIp = $limitador->consumir('admin_login_ip', Http::ip(), $maximo, $ventana);
        $porUsuario = $limitador->consumir(
            'admin_login_usuario',
            $usuario . '|' . Http::ip(),
            $maximo,
            $ventana
        );

        if (!$porIp['permitido'] || !$porUsuario['permitido']) {
            Logger::auditoria('Acceso administrativo bloqueado por límite de intentos', [
                'usuario' => $usuario,
            ]);
            return [
                'ok' => false,
                'error' => 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.',
                'reintentar' => max($porIp['reintentar'], $porUsuario['reintentar']),
            ];
        }

        $registro = $this->repo->usuarioPorNombre($usuario);
        $hash = $registro !== null ? (string) $registro['password_hash'] : self::HASH_FICTICIO;
        $correcta = password_verify($password, $hash);
        if ($registro === null || !$correcta) {
            Logger::auditoria('Intento de acceso administrativo fallido', ['usuario' => $usuario]);
            return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
        }
        if ((int) $registro['activo'] !== 1) {
            Logger::auditoria('Acceso administrativo de cuenta inactiva', ['usuario' => $usuario]);
            return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->repo->actualizarPassword((int) $registro['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Http::regenerarSesion();
        $_SESSION = [];
        $_SESSION['admin_id'] = (int) $registro['id'];
        $_SESSION['admin_usuario'] = (string) $registro['usuario'];
        $_SESSION['admin_nombre'] = (string) $registro['nombre'];
        $_SESSION['admin_ultimo'] = time();
        $_SESSION['admin_inicio'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        $this->repo->marcarAcceso((int) $registro['id']);
        Logger::auditoria('Acceso administrativo correcto', ['usuario' => $registro['usuario']]);

        return ['ok' => true];
    }

    public function salir(bool $registrar = true): void
    {
        if ($registrar) {
            $usuario = $_SESSION['admin_usuario'] ?? '';
            if (is_string($usuario) && $usuario !== '') {
                Logger::auditoria('Cierre de sesión administrativa', ['usuario' => $usuario]);
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    [
                        'expires' => time() - 42000,
                        'path' => (string) ($params['path'] ?? '/'),
                        'domain' => (string) ($params['domain'] ?? ''),
                        'secure' => (bool) ($params['secure'] ?? false),
                        'httponly' => (bool) ($params['httponly'] ?? true),
                        'samesite' => (string) ($params['samesite'] ?? 'Lax'),
                    ]
                );
            }
            session_destroy();
        }
    }

    public function flash(string $tipo, string $mensaje): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $tipo = in_array($tipo, ['ok', 'error', 'info'], true) ? $tipo : 'info';
        $_SESSION['admin_flash'] = [
            'tipo' => $tipo,
            'mensaje' => mb_substr($mensaje, 0, 500, 'UTF-8'),
        ];
    }

    public function tomarFlash(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);
        if (!is_array($flash) || !isset($flash['tipo'], $flash['mensaje'])) {
            return null;
        }
        return [
            'tipo' => in_array($flash['tipo'], ['ok', 'error', 'info'], true) ? $flash['tipo'] : 'info',
            'mensaje' => (string) $flash['mensaje'],
        ];
    }
}
