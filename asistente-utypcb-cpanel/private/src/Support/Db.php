<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

use PDO;
use RuntimeException;
use Throwable;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = (string) Config::get('db.dsn');
        $user = (string) Config::get('db.usuario');
        $password = (string) Config::get('db.password');

        try {
            self::$pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (Throwable $e) {
            Logger::error('No se pudo conectar a MariaDB.', ['exception' => get_class($e)]);
            throw new RuntimeException('No hay conexión con la base de datos.');
        }

        return self::$pdo;
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
