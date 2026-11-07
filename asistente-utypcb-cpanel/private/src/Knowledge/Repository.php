<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Knowledge;

use PDO;
use Throwable;
use Utypcb\Asistente\Support\Config;
use Utypcb\Asistente\Support\Db;
use Utypcb\Asistente\Support\Logger;
use Utypcb\Asistente\Support\Text;

final class Repository
{
    private PDO $pdo;
    private ?array $intenciones = null;
    private ?array $areas = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo instanceof PDO ? $pdo : Db::pdo();
    }

    /**
     * @return array<int,array>
     */
    public function intenciones(): array
    {
        if ($this->intenciones !== null) {
            return $this->intenciones;
        }

        $sql = 'SELECT i.*, a.slug AS area_slug, a.nombre AS area_nombre,
                       a.correo AS area_correo, a.telefono AS area_telefono, a.url_contacto AS area_url
                FROM asistente_intents i
                INNER JOIN asistente_areas a ON a.id = i.area_id
                WHERE i.activa = 1
                ORDER BY i.prioridad DESC, i.id ASC';
        $filas = $this->pdo->query($sql)->fetchAll();

        $variantes = $this->pdo->query(
            'SELECT v.intent_id, v.texto, v.texto_norm
             FROM asistente_intent_variants v
             INNER JOIN asistente_intents i ON i.id = v.intent_id
             WHERE i.activa = 1
             ORDER BY v.id ASC'
        )->fetchAll();

        $porIntencion = [];
        foreach ($variantes as $variante) {
            $id = (int) $variante['intent_id'];
            $porIntencion[$id][] = [
                'texto' => (string) $variante['texto'],
                'texto_norm' => (string) $variante['texto_norm'],
            ];
        }

        $salida = [];
        foreach ($filas as $fila) {
            $id = (int) $fila['id'];
            $enlaces = json_decode((string) ($fila['enlaces_json'] ?? '[]'), true);
            if (!is_array($enlaces)) {
                $enlaces = [];
            }

            $claves = array_values(array_filter(array_map('trim', explode(',', (string) ($fila['palabras_clave'] ?? '')))));

            $salida[] = [
                'id' => $id,
                'slug' => (string) $fila['slug'],
                'categoria' => (string) $fila['categoria'],
                'area_id' => (int) $fila['area_id'],
                'area_slug' => (string) $fila['area_slug'],
                'area_nombre' => (string) $fila['area_nombre'],
                'area_correo' => $fila['area_correo'],
                'area_telefono' => $fila['area_telefono'],
                'area_url' => $fila['area_url'],
                'pregunta' => (string) $fila['pregunta'],
                'respuesta' => (string) $fila['respuesta'],
                'fuente_titulo' => (string) $fila['fuente_titulo'],
                'fuente_url' => (string) $fila['fuente_url'],
                'enlaces' => $enlaces,
                'palabras_clave' => $claves,
                'programa_slug' => $fila['programa_slug'] !== null ? (string) $fila['programa_slug'] : null,
                'tema_slug' => $fila['tema_slug'] !== null ? (string) $fila['tema_slug'] : null,
                'prioridad' => (int) $fila['prioridad'],
                'validada' => (int) $fila['validada'] === 1,
                'variantes' => $porIntencion[$id] ?? [],
            ];
        }

        $this->intenciones = $salida;
        return $this->intenciones;
    }

    public function intencionPorSlug(string $slug): ?array
    {
        foreach ($this->intenciones() as $intencion) {
            if ($intencion['slug'] === $slug) {
                return $intencion;
            }
        }
        return null;
    }

    public function areas(): array
    {
        if ($this->areas !== null) {
            return $this->areas;
        }

        $filas = $this->pdo->query(
            'SELECT id, slug, nombre, correo, telefono, url_contacto
             FROM asistente_areas ORDER BY id ASC'
        )->fetchAll();

        $salida = [];
        foreach ($filas as $fila) {
            $salida[(string) $fila['slug']] = [
                'id' => (int) $fila['id'],
                'slug' => (string) $fila['slug'],
                'nombre' => (string) $fila['nombre'],
                'correo' => $fila['correo'] !== null ? (string) $fila['correo'] : null,
                'telefono' => $fila['telefono'] !== null ? (string) $fila['telefono'] : null,
                'url_contacto' => $fila['url_contacto'] !== null ? (string) $fila['url_contacto'] : null,
            ];
        }

        $this->areas = $salida;
        return $this->areas;
    }

    public function areaPorSlug(string $slug): ?array
    {
        $areas = $this->areas();
        return $areas[$slug] ?? null;
    }

    public function areaParaTema(?string $tema): ?array
    {
        $mapa = [
            'admision' => 'servicios-escolares',
            'costos' => 'servicios-escolares',
            'calendario' => 'servicios-escolares',
            'becas' => 'servicios-escolares',
            'tramites' => 'servicios-escolares',
            'titulacion' => 'servicios-escolares',
            'servicios' => 'servicios-escolares',
            'oferta' => 'servicios-escolares',
            'plan-estudios' => 'servicios-escolares',
            'estadias' => 'servicios-escolares',
            'campo-laboral' => 'servicios-escolares',
            'deportes' => 'departamento-deportes',
            'noticias' => 'comunicacion',
            'transparencia' => 'transparencia',
            'contacto' => 'contacto-general',
            'institucional' => 'contacto-general',
        ];

        $areas = $this->areas();
        $slug = $mapa[$tema ?? ''] ?? 'servicios-escolares';
        if (isset($areas[$slug])) {
            return $areas[$slug];
        }
        return $areas !== [] ? reset($areas) : null;
    }

    public function sugerenciasIniciales(int $limite = 4): array
    {
        $limite = max(1, min(8, $limite));
        $stmt = $this->pdo->prepare(
            'SELECT slug, pregunta, categoria
             FROM asistente_intents
             WHERE activa = 1 AND prioridad >= 8
             ORDER BY prioridad DESC, id ASC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $fila): array {
            return [
                'slug' => (string) $fila['slug'],
                'pregunta' => (string) $fila['pregunta'],
                'categoria' => (string) $fila['categoria'],
            ];
        }, $stmt->fetchAll());
    }

    public function registrarEvento(?int $intentId, bool $acierto, float $confianza, int $ms): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO asistente_events (intent_id, acierto, confianza, ms)
                 VALUES (:intent_id, :acierto, :confianza, :ms)'
            );
            $stmt->execute([
                ':intent_id' => $intentId !== null && $intentId > 0 ? $intentId : null,
                ':acierto' => $acierto ? 1 : 0,
                ':confianza' => round(max(0.0, min(1.0, $confianza)), 3),
                ':ms' => max(0, $ms),
            ]);
        } catch (Throwable $e) {
            Logger::error('No se pudo registrar el evento del asistente.', ['exception' => get_class($e)]);
        }
    }

    public function registrarNoRespondida(string $pregunta, string $normalizada, string $ipHash): bool
    {
        if (!(bool) Config::get('privacidad.registrar_no_respondidas', true)) {
            return false;
        }

        $pregunta = mb_substr($pregunta, 0, 300, 'UTF-8');
        $normalizada = mb_substr($normalizada !== '' ? $normalizada : Text::normalizar($pregunta), 0, 300, 'UTF-8');
        if ($normalizada === '') {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO asistente_unanswered (pregunta, pregunta_norm, ip_hash)
                 VALUES (:pregunta, :norm, :ip_hash)
                 ON DUPLICATE KEY UPDATE
                    veces = veces + 1,
                    ultima_vez = CURRENT_TIMESTAMP'
            );
            $stmt->execute([
                ':pregunta' => $pregunta,
                ':norm' => $normalizada,
                ':ip_hash' => substr($ipHash, 0, 64),
            ]);
            return true;
        } catch (Throwable $e) {
            Logger::error('No se pudo registrar la pregunta no respondida.', ['exception' => get_class($e)]);
            return false;
        }
    }

    /**
     * @return array{no_respondidas:int,eventos:int}
     */
    public function purgar(int $diasNoRespondidas, int $diasEventos): array
    {
        $salida = ['no_respondidas' => 0, 'eventos' => 0];

        $corte = date('Y-m-d H:i:s', time() - max(1, $diasNoRespondidas) * 86400);
        $stmt = $this->pdo->prepare('DELETE FROM asistente_unanswered WHERE ultima_vez < :corte');
        $stmt->execute([':corte' => $corte]);
        $salida['no_respondidas'] = $stmt->rowCount();

        $corte = date('Y-m-d H:i:s', time() - max(1, $diasEventos) * 86400);
        $stmt = $this->pdo->prepare('DELETE FROM asistente_events WHERE creada_en < :corte');
        $stmt->execute([':corte' => $corte]);
        $salida['eventos'] = $stmt->rowCount();

        return $salida;
    }

    public function resumen(): array
    {
        $salida = [
            'intenciones' => 0,
            'validadas' => 0,
            'variantes' => 0,
            'pendientes' => 0,
            'eventos_30d' => 0,
            'aciertos_30d' => 0,
            'ms_promedio_30d' => 0,
        ];

        $salida['intenciones'] = (int) $this->pdo->query('SELECT COUNT(*) FROM asistente_intents WHERE activa = 1')->fetchColumn();
        $salida['validadas'] = (int) $this->pdo->query('SELECT COUNT(*) FROM asistente_intents WHERE activa = 1 AND validada = 1')->fetchColumn();
        $salida['variantes'] = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM asistente_intent_variants v INNER JOIN asistente_intents i ON i.id = v.intent_id WHERE i.activa = 1'
        )->fetchColumn();
        $salida['pendientes'] = (int) $this->pdo->query('SELECT COUNT(*) FROM asistente_unanswered WHERE revisada = 0')->fetchColumn();

        $fila = $this->pdo->query(
            'SELECT COUNT(*) AS total, SUM(acierto = 1) AS aciertos, AVG(ms) AS promedio
             FROM asistente_events WHERE creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        )->fetch();
        if (is_array($fila)) {
            $salida['eventos_30d'] = (int) ($fila['total'] ?? 0);
            $salida['aciertos_30d'] = (int) ($fila['aciertos'] ?? 0);
            $salida['ms_promedio_30d'] = (int) round((float) ($fila['promedio'] ?? 0));
        }

        return $salida;
    }
}
