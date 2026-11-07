<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

use PDO;

final class RateLimiter
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo instanceof PDO ? $pdo : Db::pdo();
    }

    /**
     * @return array{permitido:bool,restantes:int,reintentar:int,limite:int,ventana:int}
     */
    public function consumir(string $alcance, string $identidad, int $maximo, int $ventana): array
    {
        $maximo = max(1, $maximo);
        $ventana = max(1, $ventana);
        $ahora = time();
        $reset = $ahora + $ventana;
        $bucket = Http::hashIdentidad($alcance . '|' . $identidad);

        $sql = 'INSERT INTO asistente_rate_limits (bucket, hits, reset_at)
                VALUES (:bucket, 1, :reset)
                ON DUPLICATE KEY UPDATE
                    hits = IF(reset_at <= :ahora, 1, hits + 1),
                    reset_at = IF(reset_at <= :ahora2, :reset2, reset_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':bucket' => $bucket,
            ':reset' => $reset,
            ':ahora' => $ahora,
            ':ahora2' => $ahora,
            ':reset2' => $reset,
        ]);

        $consulta = $this->pdo->prepare('SELECT hits, reset_at FROM asistente_rate_limits WHERE bucket = :bucket');
        $consulta->execute([':bucket' => $bucket]);
        $fila = $consulta->fetch();
        $hits = (int) ($fila['hits'] ?? 1);
        $resetEn = (int) ($fila['reset_at'] ?? $reset);

        if (mt_rand(1, 200) === 1) {
            $this->limpiar();
        }

        $permitido = $hits <= $maximo;
        return [
            'permitido' => $permitido,
            'restantes' => max(0, $maximo - $hits),
            'reintentar' => $permitido ? 0 : max(1, $resetEn - $ahora),
            'limite' => $maximo,
            'ventana' => $ventana,
        ];
    }

    public function limpiar(int $antiguedadSegundos = 3600): int
    {
        $limite = time() - max(0, $antiguedadSegundos);
        $stmt = $this->pdo->prepare('DELETE FROM asistente_rate_limits WHERE reset_at < :limite LIMIT 1000');
        $stmt->execute([':limite' => $limite]);
        return $stmt->rowCount();
    }
}
