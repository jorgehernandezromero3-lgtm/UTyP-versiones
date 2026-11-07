<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

use RuntimeException;

final class Http
{
    public static function json($datos, int $codigo = 200, bool $terminar = true): void
    {
        if (!headers_sent()) {
            http_response_code($codigo);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: no-referrer');
        }

        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo $json === false ? '{"ok":false,"error":"No se pudo preparar la respuesta."}' : $json;

        if ($terminar) {
            exit;
        }
    }

    public static function error(int $codigo, string $mensaje, array $extra = [], bool $terminar = true): void
    {
        self::json(array_merge(['ok' => false, 'error' => $mensaje], $extra), $codigo, $terminar);
    }

    public static function exigirMetodo(string $metodo): void
    {
        $actual = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($actual !== strtoupper($metodo)) {
            if (!headers_sent()) {
                header('Allow: ' . strtoupper($metodo));
            }
            throw new HttpException(405, 'Método no permitido.');
        }
    }

    public static function leerJson(int $maximoBytes = 8192): array
    {
        $tipo = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if ($tipo !== '' && stripos($tipo, 'application/json') === false) {
            throw new HttpException(415, 'Formato de solicitud no soportado.');
        }

        $crudo = file_get_contents('php://input');
        if ($crudo === false || $crudo === '') {
            throw new HttpException(400, 'La solicitud está vacía.');
        }
        if (strlen($crudo) > $maximoBytes) {
            throw new HttpException(413, 'La solicitud es demasiado grande.');
        }

        $datos = json_decode($crudo, true);
        if (!is_array($datos)) {
            throw new HttpException(400, 'La solicitud no es un JSON válido.');
        }

        return $datos;
    }

    public static function ip(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    public static function hashIdentidad(string $valor): string
    {
        return hash_hmac('sha256', $valor, (string) Config::get('seguridad.ip_salt', ''));
    }

    public static function sesionChat(): void
    {
        self::iniciarSesion(
            (string) Config::get('seguridad.chat_session_name', 'UTYPCHAT'),
            (string) Config::get('seguridad.chat_session_path', '/')
        );
    }

    public static function sesionAdmin(): void
    {
        self::iniciarSesion(
            (string) Config::get('seguridad.admin_session_name', 'UTYPADM'),
            (string) Config::get('seguridad.admin_session_path', '/asistente/admin')
        );
    }

    private static function iniciarSesion(string $nombre, string $ruta): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (headers_sent()) {
            throw new RuntimeException('No se puede iniciar la sesión: ya se enviaron encabezados.');
        }

        $nombre = preg_replace('/[^A-Za-z0-9]/', '', $nombre) ?? 'UTYPSESS';
        if ($nombre === '') {
            $nombre = 'UTYPSESS';
        }

        session_name($nombre);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $ruta !== '' ? $ruta : '/',
            'domain' => '',
            'secure' => self::esHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_cache_limiter('nocache');
        session_start();
    }

    public static function regenerarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('La sesión debe iniciarse antes de generar el token CSRF.');
        }
        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf']) || strlen($_SESSION['csrf']) !== 64) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function exigirCsrf(?string $enviado = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new HttpException(403, 'La sesión no está activa. Recarga la página.');
        }

        if ($enviado === null) {
            $enviado = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }
        if ($enviado === '') {
            $enviado = (string) ($_POST['_csrf'] ?? '');
        }

        $esperado = $_SESSION['csrf'] ?? '';
        if (!is_string($esperado) || strlen($esperado) !== 64 || !hash_equals($esperado, $enviado)) {
            throw new HttpException(403, 'La sesión expiró o la solicitud no es válida. Recarga la página.');
        }
    }

    public static function exigirOrigen(): void
    {
        $origen = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origen === '') {
            $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            if ($referer !== '') {
                $partes = parse_url($referer);
                if (is_array($partes) && isset($partes['scheme'], $partes['host'])) {
                    $origen = $partes['scheme'] . '://' . $partes['host'];
                }
            }
        }

        if ($origen === '') {
            return;
        }

        if (strtolower($origen) === 'null') {
            $fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
            if ($fetchSite === 'same-origin' || $fetchSite === 'none') {
                return;
            }
            Logger::aviso('Origen opaco rechazado', [
                'fetch_site' => $fetchSite,
            ]);
            throw new HttpException(403, 'Origen no permitido.');
        }

        $hostOrigen = parse_url($origen, PHP_URL_HOST);
        if (!is_string($hostOrigen) || $hostOrigen === '') {
            Logger::aviso('Origen rechazado', [
                'origen' => mb_substr($origen, 0, 120, 'UTF-8'),
                'fetch_site' => (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''),
            ]);
            throw new HttpException(403, 'Origen no permitido.');
        }

        $permitidos = [];
        $hostActual = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $hostActual = preg_replace('/:\d+$/', '', $hostActual) ?? '';
        if ($hostActual !== '') {
            $permitidos[] = strtolower($hostActual);
        }

        $sitio = (string) Config::get('sitio.url', '');
        $hostSitio = parse_url($sitio, PHP_URL_HOST);
        if (is_string($hostSitio) && $hostSitio !== '') {
            $permitidos[] = strtolower($hostSitio);
        }

        if (!in_array(strtolower($hostOrigen), $permitidos, true)) {
            Logger::aviso('Origen no permitido', [
                'host_origen' => mb_substr(strtolower($hostOrigen), 0, 120, 'UTF-8'),
                'fetch_site' => (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''),
            ]);
            throw new HttpException(403, 'Origen no permitido.');
        }
    }

    public static function esHttps(): bool
    {
        $https = (string) ($_SERVER['HTTPS'] ?? '');
        if ($https !== '' && strtolower($https) !== 'off') {
            return true;
        }
        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    public static function cabecerasSeguridad(bool $conCsp = false): void
    {
        if (headers_sent()) {
            return;
        }
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        if ($conCsp) {
            header(
                "Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; " .
                "frame-ancestors 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; " .
                "script-src 'self'; connect-src 'self'"
            );
        }
    }

    public static function redirigir(string $url): void
    {
        $url = str_replace(["\r", "\n"], '', $url);
        if (headers_sent()) {
            throw new RuntimeException('No se puede redirigir: ya se enviaron encabezados.');
        }
        header('Location: ' . $url, true, 302);
        exit;
    }

    public static function textoPost(string $clave, int $maximo = 500, string $defecto = ''): string
    {
        $valor = $_POST[$clave] ?? $defecto;
        if (!is_string($valor)) {
            return $defecto;
        }
        $valor = str_replace("\0", '', trim($valor));
        if (mb_strlen($valor, 'UTF-8') > $maximo) {
            $valor = mb_substr($valor, 0, $maximo, 'UTF-8');
        }
        return $valor;
    }
}
