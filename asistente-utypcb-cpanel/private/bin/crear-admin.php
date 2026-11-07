<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Utypcb\Asistente\Support\Db;
use Utypcb\Asistente\Support\Logger;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por consola.\n");
    exit(1);
}

$opciones = getopt('', ['usuario:', 'nombre:', 'rol:', 'ayuda']);
if (isset($opciones['ayuda'])) {
    fwrite(STDOUT, "Uso: php private/bin/crear-admin.php [--usuario=USUARIO] [--nombre=NOMBRE] [--rol=admin|superadmin]\n");
    fwrite(STDOUT, "La contraseña se solicita de forma oculta o se toma de UTYP_ADMIN_PASSWORD.\n");
    fwrite(STDOUT, "Por defecto se crea una cuenta de apoyo (rol admin).\n");
    exit(0);
}

$usuario = isset($opciones['usuario']) ? (string) $opciones['usuario'] : '';
$nombre = isset($opciones['nombre']) ? (string) $opciones['nombre'] : '';
$rol = isset($opciones['rol']) ? (string) $opciones['rol'] : 'admin';
if (!in_array($rol, ['admin', 'superadmin'], true)) {
    fwrite(STDERR, "El rol debe ser admin o superadmin.\n");
    exit(1);
}

if ($usuario === '') {
    fwrite(STDOUT, 'Usuario administrativo (minúsculas, sin espacios): ');
    $usuario = trim((string) fgets(STDIN));
}
$usuario = mb_strtolower(trim($usuario), 'UTF-8');
if (!preg_match('/^[a-z0-9._-]{3,60}$/', $usuario)) {
    fwrite(STDERR, "El usuario debe tener entre 3 y 60 caracteres: letras minúsculas, números, punto, guion o guion bajo.\n");
    exit(1);
}

if ($nombre === '') {
    fwrite(STDOUT, 'Nombre visible: ');
    $nombre = trim((string) fgets(STDIN));
}
$nombre = trim($nombre);
if (mb_strlen($nombre, 'UTF-8') < 3 || mb_strlen($nombre, 'UTF-8') > 120) {
    fwrite(STDERR, "El nombre visible debe tener entre 3 y 120 caracteres.\n");
    exit(1);
}

$password = getenv('UTYP_ADMIN_PASSWORD');
if ($password === false || $password === '') {
    $password = leerOculta('Contraseña (mínimo 12 caracteres): ');
    $confirmar = leerOculta('Confirma la contraseña: ');
    if (!hash_equals($password, $confirmar)) {
        fwrite(STDERR, "Las contraseñas no coinciden.\n");
        exit(1);
    }
}
$largo = mb_strlen($password, 'UTF-8');
if ($largo < 12 || $largo > 200) {
    fwrite(STDERR, "La contraseña debe tener entre 12 y 200 caracteres.\n");
    exit(1);
}

try {
    $pdo = Db::pdo();
    $buscar = $pdo->prepare('SELECT id FROM asistente_admin_users WHERE usuario = :usuario LIMIT 1');
    $buscar->execute([':usuario' => $usuario]);
    $existente = $buscar->fetch();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existente !== false) {
        $stmt = $pdo->prepare(
            'UPDATE asistente_admin_users
             SET password_hash = :hash, nombre = :nombre, rol = :rol, activo = 1
             WHERE id = :id'
        );
        $stmt->execute([
            ':hash' => $hash,
            ':nombre' => $nombre,
            ':rol' => $rol,
            ':id' => (int) $existente['id'],
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO asistente_admin_users (usuario, password_hash, nombre, rol, activo)
             VALUES (:usuario, :hash, :nombre, :rol, 1)'
        );
        $stmt->execute([
            ':usuario' => $usuario,
            ':hash' => $hash,
            ':nombre' => $nombre,
            ':rol' => $rol,
        ]);
    }

    Logger::auditoria('Cuenta administrativa creada o actualizada desde consola', [
        'usuario' => $usuario,
    ]);
    fwrite(STDOUT, "Cuenta administrativa lista: " . $usuario . " (" . $rol . ")" . PHP_EOL);
    fwrite(STDOUT, "Accede al panel y cambia la contraseña si fue proporcionada por otra persona.\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo crear la cuenta administrativa: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function leerOculta(string $etiqueta): string
{
    if (DIRECTORY_SEPARATOR === '\\') {
        $comando = 'powershell -NoProfile -Command "$s = Read-Host -AsSecureString ' .
            escapeshellarg($etiqueta) .
            '; $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); ' .
            '[Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)"';
        $salida = @shell_exec($comando);
        if (is_string($salida) && trim($salida) !== '') {
            return trim($salida);
        }
        return leerVisible($etiqueta);
    }

    $stty = @shell_exec('command -v stty 2>/dev/null');
    if (is_string($stty) && trim($stty) !== '') {
        fwrite(STDOUT, $etiqueta);
        @shell_exec('stty -echo');
        $valor = (string) fgets(STDIN);
        @shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
        return rtrim($valor, "\r\n");
    }

    return leerVisible($etiqueta);
}

function leerVisible(string $etiqueta): string
{
    fwrite(STDOUT, $etiqueta);
    $valor = (string) fgets(STDIN);
    return rtrim($valor, "\r\n");
}
