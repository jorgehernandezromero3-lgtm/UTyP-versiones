<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Admin;

use PDO;
use Utypcb\Asistente\Knowledge\Catalog;
use Utypcb\Asistente\Support\Db;
use Utypcb\Asistente\Support\Text;

final class AdminRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo instanceof PDO ? $pdo : Db::pdo();
    }

    public function usuarioPorNombre(string $usuario): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, usuario, password_hash, nombre, rol, activo, ultimo_acceso, creado_en
             FROM asistente_admin_users WHERE usuario = :usuario LIMIT 1'
        );
        $stmt->execute([':usuario' => $usuario]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function usuarioPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, usuario, password_hash, nombre, rol, activo, ultimo_acceso, creado_en
             FROM asistente_admin_users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function marcarAcceso(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE asistente_admin_users SET ultimo_acceso = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function actualizarPassword(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE asistente_admin_users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => $hash, ':id' => $id]);
    }

    public function listarAdmins(): array
    {
        return $this->pdo->query(
            'SELECT id, usuario, nombre, rol, activo, ultimo_acceso, creado_en
             FROM asistente_admin_users
             ORDER BY rol ASC, nombre ASC, id ASC'
        )->fetchAll();
    }

    public function crearAdmin(string $usuario, string $hash, string $nombre): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO asistente_admin_users (usuario, password_hash, nombre, rol, activo)
             VALUES (:usuario, :hash, :nombre, 'admin', 1)"
        );
        $stmt->execute([
            ':usuario' => $usuario,
            ':hash' => $hash,
            ':nombre' => $nombre,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function alternarAdmin(int $id, bool $activo): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asistente_admin_users SET activo = :activo WHERE id = :id'
        );
        $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
    }

    public function contarSuperadminsActivos(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM asistente_admin_users
             WHERE rol = 'superadmin' AND activo = 1"
        )->fetchColumn();
    }

    public function listarAreas(): array
    {
        return $this->pdo->query(
            'SELECT id, slug, nombre FROM asistente_areas ORDER BY nombre ASC'
        )->fetchAll();
    }

    public function listarIntents(string $buscar = '', string $categoria = '', string $estado = '', int $limite = 200): array
    {
        $where = [];
        $params = [];

        if ($buscar !== '') {
            $where[] = '(i.slug LIKE :buscar1 OR i.pregunta LIKE :buscar2 OR i.respuesta LIKE :buscar3)';
            $params[':buscar1'] = $this->like($buscar);
            $params[':buscar2'] = $this->like($buscar);
            $params[':buscar3'] = $this->like($buscar);
        }
        if ($categoria !== '' && isset(Catalog::categorias()[$categoria])) {
            $where[] = 'i.categoria = :categoria';
            $params[':categoria'] = $categoria;
        }
        if ($estado === 'activas') {
            $where[] = 'i.activa = 1';
        } elseif ($estado === 'inactivas') {
            $where[] = 'i.activa = 0';
        } elseif ($estado === 'pendientes') {
            $where[] = 'i.activa = 1 AND i.validada = 0';
        } elseif ($estado === 'validadas') {
            $where[] = 'i.validada = 1';
        }

        $sql = 'SELECT i.id, i.slug, i.pregunta, i.categoria, i.prioridad, i.activa, i.validada,
                       i.validada_por, i.actualizada_en, a.nombre AS area_nombre,
                       (SELECT COUNT(*) FROM asistente_intent_variants v WHERE v.intent_id = i.id) AS variantes
                FROM asistente_intents i
                INNER JOIN asistente_areas a ON a.id = i.area_id';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY i.actualizada_en DESC, i.id DESC LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->bindValue(':limite', max(1, min(500, $limite)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerIntent(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, a.slug AS area_slug, a.nombre AS area_nombre
             FROM asistente_intents i
             INNER JOIN asistente_areas a ON a.id = i.area_id
             WHERE i.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        if ($fila === false) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, texto, texto_norm, origen FROM asistente_intent_variants
             WHERE intent_id = :id ORDER BY origen ASC, id ASC'
        );
        $stmt->execute([':id' => $id]);
        $fila['variantes'] = $stmt->fetchAll();

        $enlaces = json_decode((string) ($fila['enlaces_json'] ?? '[]'), true);
        $fila['enlaces'] = is_array($enlaces) ? $enlaces : [];
        return $fila;
    }

    public function variantesBase(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT texto FROM asistente_intent_variants
             WHERE intent_id = :id AND origen = 'base' ORDER BY id ASC"
        );
        $stmt->execute([':id' => $id]);
        return array_map(static function (array $fila): string {
            return (string) $fila['texto'];
        }, $stmt->fetchAll());
    }

    public function variantesAdmin(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT texto FROM asistente_intent_variants
             WHERE intent_id = :id AND origen = 'admin' ORDER BY id ASC"
        );
        $stmt->execute([':id' => $id]);
        return array_map(static function (array $fila): string {
            return (string) $fila['texto'];
        }, $stmt->fetchAll());
    }

    /**
     * @return array<string,string> campo => mensaje
     */
    public function validarIntent(array $datos, ?int $id): array
    {
        $errores = [];
        $slug = trim((string) ($datos['slug'] ?? ''));
        if (!preg_match('/^[a-z0-9-]{3,100}$/', $slug)) {
            $errores['slug'] = 'Usa de 3 a 100 caracteres: minúsculas, números y guiones.';
        } else {
            $stmt = $this->pdo->prepare('SELECT id FROM asistente_intents WHERE slug = :slug LIMIT 1');
            $stmt->execute([':slug' => $slug]);
            $existente = $stmt->fetch();
            if ($existente !== false && ($id === null || (int) $existente['id'] !== $id)) {
                $errores['slug'] = 'Ya existe una intención con ese identificador.';
            }
        }

        $categorias = Catalog::categorias();
        $categoria = (string) ($datos['categoria'] ?? '');
        if (!isset($categorias[$categoria])) {
            $errores['categoria'] = 'Selecciona una categoría válida.';
        }

        $areaId = (int) ($datos['area_id'] ?? 0);
        $areas = [];
        foreach ($this->listarAreas() as $area) {
            $areas[(int) $area['id']] = true;
        }
        if (!isset($areas[$areaId])) {
            $errores['area_id'] = 'Selecciona un área responsable válida.';
        }

        $pregunta = trim((string) ($datos['pregunta'] ?? ''));
        $largoPregunta = mb_strlen($pregunta, 'UTF-8');
        if ($largoPregunta < 5 || $largoPregunta > 255) {
            $errores['pregunta'] = 'La pregunta debe tener entre 5 y 255 caracteres.';
        }

        $respuesta = trim((string) ($datos['respuesta'] ?? ''));
        $largoRespuesta = mb_strlen($respuesta, 'UTF-8');
        if ($largoRespuesta < 20) {
            $errores['respuesta'] = 'La respuesta debe tener al menos 20 caracteres.';
        } elseif ($largoRespuesta > 20000) {
            $errores['respuesta'] = 'La respuesta supera el máximo de 20,000 caracteres.';
        }

        $fuenteTitulo = trim((string) ($datos['fuente_titulo'] ?? ''));
        if ($fuenteTitulo === '' || mb_strlen($fuenteTitulo, 'UTF-8') > 160) {
            $errores['fuente_titulo'] = 'Escribe el título de la fuente (máximo 160 caracteres).';
        }

        if (Catalog::urlSegura((string) ($datos['fuente_url'] ?? '')) === null) {
            $errores['fuente_url'] = 'Escribe una URL oficial completa que empiece con http:// o https://.';
        }

        $enlaces = $datos['enlaces'] ?? [];
        if (!is_array($enlaces) || count($enlaces) > 5) {
            $errores['enlaces'] = 'Puedes agregar hasta 5 enlaces adicionales.';
        } else {
            foreach ($enlaces as $enlace) {
                if (!is_array($enlace) || Catalog::urlSegura((string) ($enlace['url'] ?? '')) === null) {
                    $errores['enlaces'] = 'Revisa los enlaces: título y URL completa.';
                    break;
                }
                if (mb_strlen(trim((string) ($enlace['titulo'] ?? '')), 'UTF-8') > 120) {
                    $errores['enlaces'] = 'El título de un enlace es demasiado largo.';
                    break;
                }
            }
        }

        $claves = $datos['palabras_clave'] ?? [];
        if (!is_array($claves) || count($claves) < 2) {
            $errores['palabras_clave'] = 'Agrega al menos dos palabras clave separadas por coma.';
        } else {
            if (count($claves) > 30) {
                $errores['palabras_clave'] = 'Usa como máximo 30 palabras clave.';
            }
            foreach ($claves as $clave) {
                $largo = mb_strlen(trim((string) $clave), 'UTF-8');
                if ($largo < 2 || $largo > 80) {
                    $errores['palabras_clave'] = 'Cada palabra clave debe tener entre 2 y 80 caracteres.';
                    break;
                }
            }
        }

        $programa = $datos['programa_slug'] ?? null;
        if ($programa !== null && $programa !== '' && !isset(Catalog::programas()[(string) $programa])) {
            $errores['programa_slug'] = 'Selecciona un programa válido.';
        }
        $tema = $datos['tema_slug'] ?? null;
        if ($tema !== null && $tema !== '' && !isset(Catalog::temas()[(string) $tema])) {
            $errores['tema_slug'] = 'Selecciona un tema válido.';
        }

        $prioridad = (int) ($datos['prioridad'] ?? 0);
        if ($prioridad < 0 || $prioridad > 10) {
            $errores['prioridad'] = 'La prioridad debe estar entre 0 y 10.';
        }

        $variantes = $datos['variantes'] ?? [];
        if (!is_array($variantes) || count($variantes) !== 5) {
            $errores['variantes'] = 'Una intención debe conservar exactamente 5 variantes base.';
        } else {
            $normales = [];
            foreach ($variantes as $indice => $variante) {
                $texto = trim((string) $variante);
                $norm = Text::normalizar($texto);
                if (mb_strlen($texto, 'UTF-8') < 2 || mb_strlen($texto, 'UTF-8') > 255 || $norm === '') {
                    $errores['variantes'] = 'Cada una de las 5 variantes debe tener entre 2 y 255 caracteres.';
                    break;
                }
                if (isset($normales[$norm])) {
                    $errores['variantes'] = 'Las variantes base no pueden repetirse entre sí.';
                    break;
                }
                $normales[$norm] = true;
                $duplicado = $this->buscarVarianteGlobal($norm, $id);
                if ($duplicado !== null) {
                    $errores['variantes'] = 'Una variante ya pertenece a «' . $duplicado . '».';
                    break;
                }
            }
        }

        $admin = $datos['variantes_admin'] ?? [];
        if (!is_array($admin) || count($admin) > 10) {
            $errores['variantes_admin'] = 'Puedes agregar hasta 10 variantes adicionales.';
        } else {
            $normales = [];
            if (is_array($variantes)) {
                foreach ($variantes as $variante) {
                    $normales[Text::normalizar((string) $variante)] = true;
                }
            }
            foreach ($admin as $variante) {
                $texto = trim((string) $variante);
                if ($texto === '') {
                    continue;
                }
                $norm = Text::normalizar($texto);
                if (mb_strlen($texto, 'UTF-8') < 2 || mb_strlen($texto, 'UTF-8') > 255 || $norm === '') {
                    $errores['variantes_admin'] = 'Cada variante adicional debe tener entre 2 y 255 caracteres.';
                    break;
                }
                if (isset($normales[$norm])) {
                    $errores['variantes_admin'] = 'Una variante adicional repite una variante base o adicional.';
                    break;
                }
                $normales[$norm] = true;
                $duplicado = $this->buscarVarianteGlobal($norm, $id);
                if ($duplicado !== null) {
                    $errores['variantes_admin'] = 'Una variante adicional ya pertenece a «' . $duplicado . '».';
                    break;
                }
            }
        }

        return $errores;
    }

    private function buscarVarianteGlobal(string $norm, ?int $id): ?string
    {
        $sql = 'SELECT i.slug FROM asistente_intent_variants v
                INNER JOIN asistente_intents i ON i.id = v.intent_id
                WHERE v.texto_norm = :norm';
        $params = [':norm' => $norm];
        if ($id !== null) {
            $sql .= ' AND v.intent_id <> :id';
            $params[':id'] = $id;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        return $fila === false ? null : (string) $fila['slug'];
    }

    public function guardarIntent(array $datos, ?int $id): int
    {
        return Db::transaction(function (PDO $pdo) use ($datos, $id): int {
            $parametros = [
                ':slug' => (string) $datos['slug'],
                ':categoria' => (string) $datos['categoria'],
                ':area_id' => (int) $datos['area_id'],
                ':pregunta' => (string) $datos['pregunta'],
                ':respuesta' => (string) $datos['respuesta'],
                ':fuente_titulo' => (string) $datos['fuente_titulo'],
                ':fuente_url' => (string) $datos['fuente_url'],
                ':enlaces' => json_encode(
                    array_values($datos['enlaces'] ?? []),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                ':claves' => implode(', ', $datos['palabras_clave'] ?? []),
                ':programa' => !empty($datos['programa_slug']) ? (string) $datos['programa_slug'] : null,
                ':tema' => !empty($datos['tema_slug']) ? (string) $datos['tema_slug'] : null,
                ':prioridad' => (int) ($datos['prioridad'] ?? 0),
                ':activa' => !empty($datos['activa']) ? 1 : 0,
            ];

            if ($id === null) {
                $stmt = $pdo->prepare(
                    'INSERT INTO asistente_intents
                        (slug, categoria, area_id, pregunta, respuesta, fuente_titulo, fuente_url, enlaces_json,
                         palabras_clave, programa_slug, tema_slug, prioridad, validada, activa)
                     VALUES
                        (:slug, :categoria, :area_id, :pregunta, :respuesta, :fuente_titulo, :fuente_url, :enlaces,
                         :claves, :programa, :tema, :prioridad, 0, :activa)'
                );
                $stmt->execute($parametros);
                $id = (int) $pdo->lastInsertId();
            } else {
                $parametros[':id'] = $id;
                $stmt = $pdo->prepare(
                    'UPDATE asistente_intents SET
                        slug = :slug, categoria = :categoria, area_id = :area_id, pregunta = :pregunta,
                        respuesta = :respuesta, fuente_titulo = :fuente_titulo, fuente_url = :fuente_url,
                        enlaces_json = :enlaces, palabras_clave = :claves, programa_slug = :programa,
                        tema_slug = :tema, prioridad = :prioridad, activa = :activa,
                        validada = 0, validada_por = NULL, validada_en = NULL
                     WHERE id = :id'
                );
                $stmt->execute($parametros);
            }

            $pdo->prepare("DELETE FROM asistente_intent_variants WHERE intent_id = :id AND origen = 'base'")
                ->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM asistente_intent_variants WHERE intent_id = :id AND origen = 'admin'")
                ->execute([':id' => $id]);

            $insertar = $pdo->prepare(
                'INSERT INTO asistente_intent_variants (intent_id, texto, texto_norm, origen)
                 VALUES (:intent_id, :texto, :norm, :origen)'
            );
            foreach ($datos['variantes'] as $texto) {
                $insertar->execute([
                    ':intent_id' => $id,
                    ':texto' => trim((string) $texto),
                    ':norm' => Text::normalizar((string) $texto),
                    ':origen' => 'base',
                ]);
            }
            foreach ($datos['variantes_admin'] ?? [] as $texto) {
                if (trim((string) $texto) === '') {
                    continue;
                }
                $insertar->execute([
                    ':intent_id' => $id,
                    ':texto' => trim((string) $texto),
                    ':norm' => Text::normalizar((string) $texto),
                    ':origen' => 'admin',
                ]);
            }

            return $id;
        });
    }

    public function alternarActiva(int $id, bool $activa): void
    {
        $stmt = $this->pdo->prepare('UPDATE asistente_intents SET activa = :activa WHERE id = :id');
        $stmt->execute([':activa' => $activa ? 1 : 0, ':id' => $id]);
    }

    public function eliminarIntent(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM asistente_intents WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function marcarValidada(int $id, string $admin): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asistente_intents SET validada = 1, validada_por = :admin, validada_en = CURDATE() WHERE id = :id'
        );
        $stmt->execute([':admin' => mb_substr($admin, 0, 120, 'UTF-8'), ':id' => $id]);
    }

    public function quitarValidacion(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asistente_intents SET validada = 0, validada_por = NULL, validada_en = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    public function resumen(): array
    {
        $salida = [
            'intenciones' => 0,
            'activas' => 0,
            'validadas' => 0,
            'variantes' => 0,
            'pendientes' => 0,
            'no_respondidas' => 0,
            'revisadas' => 0,
            'eventos_30d' => 0,
            'aciertos_30d' => 0,
            'precision_30d' => 0.0,
            'ms_promedio_30d' => 0,
        ];

        $fila = $this->pdo->query(
            'SELECT COUNT(*) AS total, SUM(activa = 1) AS activas, SUM(activa = 1 AND validada = 1) AS validadas
             FROM asistente_intents'
        )->fetch();
        if (is_array($fila)) {
            $salida['intenciones'] = (int) ($fila['total'] ?? 0);
            $salida['activas'] = (int) ($fila['activas'] ?? 0);
            $salida['validadas'] = (int) ($fila['validadas'] ?? 0);
        }

        $salida['variantes'] = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM asistente_intent_variants v
             INNER JOIN asistente_intents i ON i.id = v.intent_id WHERE i.activa = 1'
        )->fetchColumn();

        $fila = $this->pdo->query(
            'SELECT COUNT(*) AS total, SUM(revisada = 0) AS pendientes, SUM(revisada = 1) AS revisadas
             FROM asistente_unanswered'
        )->fetch();
        if (is_array($fila)) {
            $salida['no_respondidas'] = (int) ($fila['total'] ?? 0);
            $salida['pendientes'] = (int) ($fila['pendientes'] ?? 0);
            $salida['revisadas'] = (int) ($fila['revisadas'] ?? 0);
        }

        $fila = $this->pdo->query(
            'SELECT COUNT(*) AS total, SUM(acierto = 1) AS aciertos, AVG(ms) AS promedio
             FROM asistente_events WHERE creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        )->fetch();
        if (is_array($fila)) {
            $salida['eventos_30d'] = (int) ($fila['total'] ?? 0);
            $salida['aciertos_30d'] = (int) ($fila['aciertos'] ?? 0);
            $salida['ms_promedio_30d'] = (int) round((float) ($fila['promedio'] ?? 0));
            if ($salida['eventos_30d'] > 0) {
                $salida['precision_30d'] = round(100 * $salida['aciertos_30d'] / $salida['eventos_30d'], 1);
            }
        }

        return $salida;
    }

    public function listarNoRespondidas(string $filtro = 'pendientes', string $buscar = '', int $limite = 100): array
    {
        $where = [];
        $params = [];
        if ($filtro === 'pendientes') {
            $where[] = 'u.revisada = 0';
        } elseif ($filtro === 'revisadas') {
            $where[] = 'u.revisada = 1';
        }
        if ($buscar !== '') {
            $where[] = 'u.pregunta LIKE :buscar';
            $params[':buscar'] = $this->like($buscar);
        }

        $sql = 'SELECT u.id, u.pregunta, u.veces, u.primera_vez, u.ultima_vez, u.revisada,
                       u.revisada_en, u.intent_id, u.nota, i.slug AS intent_slug, i.pregunta AS intent_pregunta
                FROM asistente_unanswered u
                LEFT JOIN asistente_intents i ON i.id = u.intent_id';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY u.revisada ASC, u.ultima_vez DESC, u.id DESC LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->bindValue(':limite', max(1, min(300, $limite)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarNoRespondidas(): array
    {
        $fila = $this->pdo->query(
            'SELECT COUNT(*) AS total, SUM(revisada = 0) AS pendientes, SUM(revisada = 1) AS revisadas
             FROM asistente_unanswered'
        )->fetch();
        return [
            'total' => (int) ($fila['total'] ?? 0),
            'pendientes' => (int) ($fila['pendientes'] ?? 0),
            'revisadas' => (int) ($fila['revisadas'] ?? 0),
        ];
    }

    public function obtenerNoRespondida(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM asistente_unanswered WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function actualizarNoRespondida(int $id, bool $revisada, string $nota): void
    {
        $nota = trim($nota);
        $stmt = $this->pdo->prepare(
            'UPDATE asistente_unanswered SET
                revisada = :revisada,
                revisada_en = IF(:revisada2 = 1, NOW(), NULL),
                nota = :nota
             WHERE id = :id'
        );
        $stmt->execute([
            ':revisada' => $revisada ? 1 : 0,
            ':revisada2' => $revisada ? 1 : 0,
            ':nota' => $nota !== '' ? mb_substr($nota, 0, 500, 'UTF-8') : null,
            ':id' => $id,
        ]);
    }

    public function vincularNoRespondida(int $id, int $intentId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asistente_unanswered SET revisada = 1, revisada_en = NOW(), intent_id = :intent_id WHERE id = :id'
        );
        $stmt->execute([':intent_id' => $intentId, ':id' => $id]);
    }

    public function eliminarNoRespondida(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM asistente_unanswered WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function listarSync(int $limite = 20): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM asistente_sync_log ORDER BY id DESC LIMIT :limite');
        $stmt->bindValue(':limite', max(1, min(100, $limite)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function ultimaSync(): ?array
    {
        $fila = $this->pdo->query('SELECT * FROM asistente_sync_log ORDER BY id DESC LIMIT 1')->fetch();
        return $fila === false ? null : $fila;
    }

    private function like(string $texto): string
    {
        $texto = mb_substr(trim($texto), 0, 100, 'UTF-8');
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto) . '%';
    }
}
