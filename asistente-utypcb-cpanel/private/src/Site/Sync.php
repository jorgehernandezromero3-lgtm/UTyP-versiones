<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Site;

use PDO;
use Throwable;
use Utypcb\Asistente\Support\Config;
use Utypcb\Asistente\Support\Db;
use Utypcb\Asistente\Support\Logger;

final class Sync
{
    private const MAX_BYTES = 2097152;

    public static function estadoFuentes(): array
    {
        $directorio = self::directorio();
        $archivos = Config::get('sitio.archivos', []);
        $variables = Config::get('sitio.variables', []);
        if (!is_array($archivos)) {
            return [];
        }
        if (!is_array($variables)) {
            $variables = [];
        }

        $salida = [];
        foreach ($archivos as $clave => $nombre) {
            $nombre = (string) $nombre;
            $ruta = $directorio !== '' ? $directorio . '/' . $nombre : $nombre;
            $existe = $ruta !== '' && is_file($ruta);
            $real = $existe ? realpath($ruta) : false;
            $legible = $existe
                && is_readable($ruta)
                && $real !== false
                && self::dentroDe($real, $directorio)
                && (int) @filesize($ruta) <= self::MAX_BYTES;

            $salida[] = [
                'clave' => (string) $clave,
                'archivo' => $nombre,
                'variable' => (string) ($variables[$clave] ?? ''),
                'existe' => $existe,
                'legible' => $legible,
                'modificado' => $existe ? date('Y-m-d H:i:s', (int) @filemtime($ruta)) : null,
                'tamano' => $existe ? (int) @filesize($ruta) : 0,
                'ruta' => $ruta,
            ];
        }

        return $salida;
    }

    public static function ejecutar(string $admin = 'sistema'): array
    {
        $pdo = Db::pdo();
        $inicio = date('Y-m-d H:i:s');
        $insertar = $pdo->prepare(
            "INSERT INTO asistente_sync_log (iniciado_en, estado, detalle, actualizados)
             VALUES (:inicio, 'error', :detalle, 0)"
        );
        $insertar->execute([
            ':inicio' => $inicio,
            ':detalle' => 'Sincronización en proceso.',
        ]);
        $logId = (int) $pdo->lastInsertId();

        try {
            $fuentes = self::estadoFuentes();
            $legibles = [];
            $faltantes = [];
            foreach ($fuentes as $fuente) {
                if (!empty($fuente['legible'])) {
                    $legibles[] = $fuente;
                } else {
                    $faltantes[] = (string) $fuente['archivo'];
                }
            }

            if ($legibles === []) {
                $detalle = 'No hay archivos de datos legibles en el directorio configurado.';
                self::cerrarLog($pdo, $logId, 'sin_datos', $detalle, 0);
                return [
                    'estado' => 'sin_datos',
                    'mensaje' => 'No se encontraron archivos de datos del sitio; la base de conocimiento no se modificó.',
                    'actualizados' => 0,
                    'detalle' => $detalle,
                ];
            }

            $datos = [];
            foreach ($legibles as $fuente) {
                $datos[(string) $fuente['clave']] = self::cargar(
                    (string) $fuente['ruta'],
                    (string) $fuente['variable']
                );
            }

            $cambios = [];
            if (isset($datos['noticias'])) {
                $cambios = array_merge($cambios, self::cambiosNoticias($datos['noticias']));
            }
            if (isset($datos['deportes'])) {
                $cambios = array_merge($cambios, self::cambiosDeportes($datos['deportes']));
            }
            if (isset($datos['calendario'])) {
                $cambios = array_merge($cambios, self::cambiosCalendario($datos['calendario']));
            }
            if (isset($datos['carreras'])) {
                $cambios = array_merge($cambios, self::cambiosCarreras($datos['carreras']));
            }

            if ($cambios === []) {
                $detalle = 'Los archivos se leyeron, pero no contienen datos reconocibles para actualizar intenciones.';
                if ($faltantes !== []) {
                    $detalle .= ' Sin leer: ' . implode(', ', array_slice($faltantes, 0, 4)) . '.';
                }
                self::cerrarLog($pdo, $logId, 'sin_datos', $detalle, 0);
                return [
                    'estado' => 'sin_datos',
                    'mensaje' => 'La sincronización no encontró datos reconocibles; no se modificó la base de conocimiento.',
                    'actualizados' => 0,
                    'detalle' => $detalle,
                ];
            }

            $actualizados = self::aplicar($pdo, $cambios);
            $detalle = count($legibles) . ' de ' . count($fuentes) . ' fuentes legibles; ' .
                count($cambios) . ' respuestas derivadas; ' . $actualizados . ' intención(es) actualizada(s).';
            if ($faltantes !== []) {
                $detalle .= ' Sin leer: ' . implode(', ', array_slice($faltantes, 0, 4)) . '.';
            }

            self::cerrarLog($pdo, $logId, 'ok', $detalle, $actualizados);
            Logger::auditoria('Sincronización con datos del sitio', [
                'admin' => $admin,
                'actualizados' => $actualizados,
                'fuentes_legibles' => count($legibles),
            ]);

            $mensaje = $actualizados > 0
                ? 'Sincronización terminada: ' . $actualizados . ' intención(es) actualizada(s) y pendiente(s) de validación.'
                : 'Sincronización terminada sin cambios: las respuestas ya estaban al día.';

            return [
                'estado' => 'ok',
                'mensaje' => $mensaje,
                'actualizados' => $actualizados,
                'detalle' => $detalle,
            ];
        } catch (Throwable $e) {
            $detalle = 'Falló la sincronización: ' . $e->getMessage();
            self::cerrarLog($pdo, $logId, 'error', $detalle, 0);
            Logger::error('Falló la sincronización con los datos del sitio.', [
                'exception' => get_class($e),
            ]);
            return [
                'estado' => 'error',
                'mensaje' => 'No se pudo completar la sincronización. Revisa la bitácora del servidor.',
                'actualizados' => 0,
                'detalle' => $detalle,
            ];
        }
    }

    private static function directorio(): string
    {
        $directorio = (string) Config::get('sitio.directorio_datos', '');
        return rtrim(str_replace('\\', '/', trim($directorio)), '/');
    }

    private static function dentroDe(string $ruta, string $directorio): bool
    {
        if ($directorio === '') {
            return false;
        }
        $base = realpath($directorio);
        if ($base === false) {
            return false;
        }
        $base = rtrim(str_replace('\\', '/', $base), '/');
        $ruta = str_replace('\\', '/', $ruta);
        return strcasecmp($ruta, $base) === 0
            || strcasecmp(substr($ruta, 0, strlen($base) + 1), $base . '/') === 0;
    }

    private static function cargar(string $ruta, string $variable): array
    {
        $tamano = (int) @filesize($ruta);
        if ($tamano <= 0 || $tamano > self::MAX_BYTES) {
            return [];
        }

        $cargar = static function (string $archivo, string $nombre): array {
            $cwd = getcwd();
            $directorio = dirname($archivo);
            ob_start();
            try {
                if ($cwd !== false) {
                    @chdir($directorio);
                }
                $retorno = include $archivo;
                if (is_array($retorno)) {
                    return $retorno;
                }
                $variables = get_defined_vars();
                if (isset($variables[$nombre]) && is_array($variables[$nombre])) {
                    return $variables[$nombre];
                }
            } catch (Throwable $e) {
                return [];
            } finally {
                if ($cwd !== false) {
                    @chdir($cwd);
                }
                ob_end_clean();
            }
            return [];
        };

        $datos = $cargar($ruta, $variable);
        return is_array($datos) ? $datos : [];
    }

    private static function cerrarLog(PDO $pdo, int $id, string $estado, string $detalle, int $actualizados): void
    {
        $detalle = mb_substr($detalle, 0, 500, 'UTF-8');
        $stmt = $pdo->prepare(
            'UPDATE asistente_sync_log
             SET terminado_en = :fin, estado = :estado, detalle = :detalle, actualizados = :actualizados
             WHERE id = :id'
        );
        $stmt->execute([
            ':fin' => date('Y-m-d H:i:s'),
            ':estado' => $estado,
            ':detalle' => $detalle,
            ':actualizados' => max(0, $actualizados),
            ':id' => $id,
        ]);
    }

    private static function aplicar(PDO $pdo, array $cambios): int
    {
        $buscar = $pdo->prepare('SELECT id, respuesta FROM asistente_intents WHERE slug = :slug LIMIT 1');
        $actualizar = $pdo->prepare(
            'UPDATE asistente_intents
             SET respuesta = :respuesta, validada = 0, validada_por = NULL, validada_en = NULL
             WHERE id = :id'
        );

        $total = 0;
        $vistos = [];
        foreach ($cambios as $cambio) {
            $slug = (string) ($cambio['slug'] ?? '');
            $respuesta = (string) ($cambio['respuesta'] ?? '');
            if ($slug === '' || $respuesta === '' || isset($vistos[$slug])) {
                continue;
            }
            $vistos[$slug] = true;

            $buscar->execute([':slug' => $slug]);
            $fila = $buscar->fetch();
            if ($fila === false) {
                continue;
            }
            if (trim((string) $fila['respuesta']) === trim($respuesta)) {
                continue;
            }
            $actualizar->execute([
                ':respuesta' => $respuesta,
                ':id' => (int) $fila['id'],
            ]);
            $total += $actualizar->rowCount() > 0 ? 1 : 0;
        }

        return $total;
    }

    private static function cambiosNoticias(array $datos): array
    {
        $items = self::lista($datos, ['noticias', 'notas', 'hemeroteca', 'items', 'registros', 'publicaciones']);
        $notas = [];
        foreach ($items as $item) {
            $titulo = self::texto(self::campo($item, ['titulo', 'title', 'nombre', 'encabezado']), 180);
            if ($titulo === '') {
                continue;
            }
            $notas[] = [
                'titulo' => $titulo,
                'fecha' => self::fechaDeItem($item),
            ];
        }
        if ($notas === []) {
            return [];
        }

        usort($notas, [self::class, 'ordenarPorFechaDesc']);
        $cambios = [];

        $recientes = self::respuestaLista(
            array_slice($notas, 0, 3),
            'Las notas más recientes publicadas en el sitio oficial son:',
            'Puedes leerlas completas en la hemeroteca de Noticias.'
        );
        if ($recientes !== null) {
            $cambios[] = ['slug' => 'noticias-recientes', 'respuesta' => $recientes];
        }

        $cambios = array_merge($cambios, self::cambiosNoticiaFiltrada(
            $notas,
            'noticias-aceptados',
            static function (string $texto): bool {
                return strpos($texto, 'aceptad') !== false;
            },
            'El sitio ha publicado estas notas relacionadas con las y los aceptados:'
        ));

        $cambios = array_merge($cambios, self::cambiosNoticiaFiltrada(
            $notas,
            'noticias-medio-ambiente',
            static function (string $texto): bool {
                return strpos($texto, 'ambiente') !== false
                    || strpos($texto, 'sustentab') !== false
                    || strpos($texto, 'ecolog') !== false;
            },
            'El sitio ha publicado estas notas relacionadas con el medio ambiente:'
        ));

        $cambios = array_merge($cambios, self::cambiosNoticiaFiltrada(
            $notas,
            'noticias-deportes',
            static function (string $texto): bool {
                return strpos($texto, 'deport') !== false
                    || strpos($texto, 'futbol') !== false
                    || strpos($texto, 'basquet') !== false
                    || strpos($texto, 'atlet') !== false
                    || strpos($texto, 'encarrera') !== false;
            },
            'El sitio ha publicado estas notas deportivas:'
        ));

        return $cambios;
    }

    private static function cambiosNoticiaFiltrada(array $notas, string $slug, callable $filtro, string $intro): array
    {
        $filtradas = [];
        foreach ($notas as $nota) {
            $texto = self::normalizar($nota['titulo']);
            if ($filtro($texto)) {
                $filtradas[] = $nota;
            }
        }
        if ($filtradas === []) {
            return [];
        }
        $respuesta = self::respuestaLista(
            array_slice($filtradas, 0, 3),
            $intro,
            'Consulta la hemeroteca de Noticias para leer las notas completas.'
        );
        return $respuesta === null ? [] : [['slug' => $slug, 'respuesta' => $respuesta]];
    }

    private static function cambiosDeportes(array $datos): array
    {
        $items = self::lista($datos, ['encuentros', 'eventos', 'torneos', 'items', 'registros', 'postales']);
        $eventos = [];
        foreach ($items as $item) {
            $titulo = self::texto(self::campo($item, ['titulo', 'title', 'nombre', 'evento']), 180);
            if ($titulo === '') {
                continue;
            }
            $categoria = self::texto(self::campo($item, ['categoria', 'tipo', 'rama', 'deporte']), 80);
            $eventos[] = [
                'titulo' => $titulo,
                'fecha' => self::fechaDeItem($item),
                'categoria' => $categoria,
            ];
        }
        if ($eventos === []) {
            return [];
        }

        usort($eventos, [self::class, 'ordenarPorFechaDesc']);
        $cambios = [];

        $lista = [];
        foreach (array_slice($eventos, 0, 4) as $evento) {
            $lista[] = $evento;
        }
        $respuesta = self::respuestaLista(
            $lista,
            'La página de Deportes registra estos encuentros y torneos:',
            'Las bases y convocatorias se anuncian en la sección de Deportes y en Noticias.'
        );
        if ($respuesta !== null) {
            $cambios[] = ['slug' => 'deportes-torneos', 'respuesta' => $respuesta];
        }

        $encuentros = [];
        foreach ($eventos as $evento) {
            $texto = self::normalizar($evento['titulo'] . ' ' . $evento['categoria']);
            if (strpos($texto, 'encuentro') !== false) {
                $encuentros[] = $evento;
            }
        }
        if ($encuentros !== []) {
            $respuesta = self::respuestaLista(
                array_slice($encuentros, 0, 3),
                'La página de Deportes publica estas postales del Encuentro Deportivo y Cultural:',
                'El sitio no detalla sedes ni participantes adicionales en esos registros.'
            );
            if ($respuesta !== null) {
                $cambios[] = ['slug' => 'deportes-encuentro', 'respuesta' => $respuesta];
            }
        }

        return $cambios;
    }

    private static function cambiosCalendario(array $datos): array
    {
        $items = self::lista($datos, ['eventos', 'fechas', 'dias', 'calendario', 'items', 'registros']);
        $eventos = [];
        foreach ($items as $item) {
            $titulo = self::texto(self::campo($item, ['titulo', 'title', 'nombre', 'evento', 'descripcion']), 180);
            $fecha = self::fechaDeItem($item);
            if ($titulo === '' || $fecha === null) {
                continue;
            }
            $eventos[] = ['titulo' => $titulo, 'fecha' => $fecha];
        }
        if ($eventos === []) {
            return [];
        }

        $ciclo = self::texto(self::campo($datos, ['ciclo', 'ciclo_escolar', 'periodo']), 40);
        $reglas = [
            'calendario-inicio-cuatrimestre' => [
                'etiqueta' => 'inicio de cuatrimestre',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'inicio') !== false && strpos($texto, 'cuatrimestre') !== false;
                },
            ],
            'calendario-fin-cuatrimestre' => [
                'etiqueta' => 'fin de cuatrimestre',
                'coincide' => static function (string $texto): bool {
                    $fin = strpos($texto, 'fin') !== false
                        || strpos($texto, 'termino') !== false
                        || strpos($texto, 'conclu') !== false;
                    return $fin && strpos($texto, 'cuatrimestre') !== false;
                },
            ],
            'calendario-vacaciones' => [
                'etiqueta' => 'vacaciones y recesos',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'vacacion') !== false || strpos($texto, 'receso') !== false;
                },
            ],
            'calendario-evaluaciones-finales' => [
                'etiqueta' => 'evaluaciones finales',
                'coincide' => static function (string $texto): bool {
                    $evaluacion = strpos($texto, 'evaluacion') !== false
                        || strpos($texto, 'examen') !== false;
                    return $evaluacion && strpos($texto, 'final') !== false;
                },
            ],
            'calendario-extraordinarios' => [
                'etiqueta' => 'evaluaciones extraordinarias',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'extraordinari') !== false;
                },
            ],
            'calendario-reinscripcion' => [
                'etiqueta' => 'reinscripciones',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'reinscrip') !== false;
                },
            ],
            'calendario-dias-sin-clases' => [
                'etiqueta' => 'suspensiones y días conmemorativos',
                'coincide' => static function (string $texto): bool {
                    $suspension = strpos($texto, 'suspension') !== false
                        && strpos($texto, 'labores') !== false;
                    return $suspension || strpos($texto, 'conmemorativ') !== false;
                },
            ],
            'calendario-calificaciones' => [
                'etiqueta' => 'entrega de calificaciones',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'calificacion') !== false;
                },
            ],
            'calendario-induccion' => [
                'etiqueta' => 'curso de inducción',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'induccion') !== false;
                },
            ],
            'calendario-proyecto-integrador' => [
                'etiqueta' => 'proyecto integrador',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'proyecto') !== false && strpos($texto, 'integrador') !== false;
                },
            ],
            'calendario-dia-tsu' => [
                'etiqueta' => 'Día del TSU',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'tsu') !== false && strpos($texto, 'dia') !== false;
                },
            ],
            'calendario-clausura' => [
                'etiqueta' => 'clausuras',
                'coincide' => static function (string $texto): bool {
                    return strpos($texto, 'clausura') !== false;
                },
            ],
        ];

        $cambios = [];
        foreach ($reglas as $slug => $regla) {
            $coincidencias = [];
            foreach ($eventos as $evento) {
                $texto = self::normalizar($evento['titulo']);
                if (!($regla['coincide'])($texto)) {
                    continue;
                }
                $clave = $evento['fecha'] . '|' . $texto;
                if (!isset($coincidencias[$clave])) {
                    $coincidencias[$clave] = $evento;
                }
            }
            if ($coincidencias === []) {
                continue;
            }

            $lista = array_values($coincidencias);
            usort($lista, [self::class, 'ordenarPorFechaAsc']);
            $lista = array_slice($lista, 0, 6);

            $intro = 'De acuerdo con el calendario escolar' . ($ciclo !== '' ? ' ' . $ciclo : '') .
                ', estas son las fechas de ' . $regla['etiqueta'] . ':';
            $respuesta = self::respuestaListaConIntro(
                $lista,
                $intro,
                'Consulta el calendario completo y las demás fechas clave en la página oficial del calendario.'
            );
            if ($respuesta !== null) {
                $cambios[] = ['slug' => $slug, 'respuesta' => $respuesta];
            }
        }

        return $cambios;
    }

    private static function cambiosCarreras(array $datos): array
    {
        $items = self::lista($datos, ['carreras', 'programas', 'items', 'registros', 'oferta']);
        $nombres = [];
        $vistos = [];
        foreach ($items as $item) {
            $nombre = self::texto(self::campo($item, ['nombre', 'carrera', 'titulo', 'name', 'programa']), 160);
            if ($nombre === '' || mb_strlen($nombre, 'UTF-8') < 4) {
                continue;
            }
            $clave = self::normalizar($nombre);
            if ($clave === '' || isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;
            $nombres[] = $nombre;
        }
        if (count($nombres) < 2) {
            return [];
        }

        $lineas = [];
        foreach ($nombres as $nombre) {
            $lineas[] = '• ' . $nombre;
        }
        $respuesta = 'La página oficial de Plan de Estudios lista ' . count($nombres) . " programas:\n\n" .
            implode("\n", $lineas) .
            "\n\nCada programa tiene su perfil, mapa curricular y documentos oficiales en la página de Plan de Estudios.";

        return [['slug' => 'carreras-oferta', 'respuesta' => $respuesta]];
    }

    private static function lista(array $datos, array $claves): array
    {
        foreach ($claves as $clave) {
            if (!isset($datos[$clave]) || !is_array($datos[$clave])) {
                continue;
            }
            $items = self::normalizarLista($datos[$clave], 0);
            if ($items !== []) {
                return $items;
            }
        }
        return self::normalizarLista($datos, 0);
    }

    private static function normalizarLista(array $valor, int $profundidad): array
    {
        if ($valor === [] || $profundidad > 3) {
            return [];
        }

        if (self::esLista($valor)) {
            $items = [];
            foreach ($valor as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (self::tieneCampos($item)) {
                    $items[] = $item;
                } else {
                    $items = array_merge($items, self::normalizarLista($item, $profundidad + 1));
                }
            }
            return array_slice($items, 0, 500);
        }

        $items = [];
        foreach ($valor as $sub) {
            if (!is_array($sub)) {
                continue;
            }
            $items = array_merge($items, self::normalizarLista($sub, $profundidad + 1));
        }
        return array_slice($items, 0, 500);
    }

    private static function esLista(array $valor): bool
    {
        if ($valor === []) {
            return false;
        }
        return array_keys($valor) === range(0, count($valor) - 1);
    }

    private static function tieneCampos(array $item): bool
    {
        $claves = [
            'titulo', 'title', 'nombre', 'name', 'evento', 'carrera', 'programa',
            'fecha', 'fecha_inicio', 'inicio', 'date', 'start', 'dia', 'descripcion',
        ];
        foreach ($claves as $clave) {
            if (array_key_exists($clave, $item)) {
                return true;
            }
        }
        return false;
    }

    private static function campo(array $item, array $claves): string
    {
        foreach ($claves as $clave) {
            if (!array_key_exists($clave, $item)) {
                continue;
            }
            $valor = $item[$clave];
            if (is_string($valor) || is_int($valor) || is_float($valor)) {
                $texto = trim((string) $valor);
                if ($texto !== '') {
                    return $texto;
                }
            } elseif (is_array($valor)) {
                $texto = self::campo($valor, ['texto', 'titulo', 'title', 'nombre', 'valor', 'label']);
                if ($texto !== '') {
                    return $texto;
                }
            }
        }
        return '';
    }

    private static function fechaDeItem(array $item): ?string
    {
        $claves = [
            'fecha', 'fecha_inicio', 'inicio', 'date', 'start', 'dia_fecha',
            'publicado_en', 'fecha_publicacion', 'publicado', 'dtstart',
        ];
        foreach ($claves as $clave) {
            if (!array_key_exists($clave, $item)) {
                continue;
            }
            $valor = $item[$clave];
            if (is_array($valor)) {
                $iso = self::fechaDeItem($valor);
            } else {
                $iso = self::fechaIso($valor);
            }
            if ($iso !== null) {
                return $iso;
            }
        }

        $dia = self::campo($item, ['dia', 'day']);
        $mes = self::campo($item, ['mes', 'month']);
        $anio = self::campo($item, ['anio', 'año', 'year']);
        if ($dia !== '' && $mes !== '') {
            return self::fechaIso($dia . ' ' . $mes . ' ' . ($anio !== '' ? $anio : date('Y')));
        }

        return null;
    }

    private static function fechaIso($valor): ?string
    {
        if (is_int($valor) || is_float($valor)) {
            $ts = (int) $valor;
            return $ts > 315532800 ? date('Y-m-d', $ts) : null;
        }
        if (!is_string($valor)) {
            return null;
        }

        $texto = trim($valor);
        if ($texto === '') {
            return null;
        }
        if (ctype_digit($texto) && strlen($texto) >= 9) {
            $ts = (int) $texto;
            return $ts > 315532800 ? date('Y-m-d', $ts) : null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $texto, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                ? sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3])
                : null;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $texto, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3])
                ? sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1])
                : null;
        }

        $normal = self::normalizarFechaTexto($texto);
        if ($normal === '') {
            return null;
        }
        $ts = strtotime($normal);
        if ($ts === false) {
            return null;
        }
        $anio = (int) date('Y', $ts);
        return $anio >= 2000 && $anio <= 2100 ? date('Y-m-d', $ts) : null;
    }

    private static function normalizarFechaTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);
        $meses = [
            'enero' => 'january', 'febrero' => 'february', 'marzo' => 'march',
            'abril' => 'april', 'mayo' => 'may', 'junio' => 'june',
            'julio' => 'july', 'agosto' => 'august', 'septiembre' => 'september',
            'setiembre' => 'september', 'octubre' => 'october', 'noviembre' => 'november',
            'diciembre' => 'december',
        ];
        foreach ($meses as $espanol => $ingles) {
            $texto = str_replace($espanol, $ingles, $texto);
        }
        $texto = preg_replace(
            '/\b(lunes|martes|miercoles|jueves|viernes|sabado|domingo)\b/u',
            ' ',
            $texto
        );
        $texto = str_replace([' de ', ' del ', '–', '—', ','], ' ', (string) $texto);
        $texto = preg_replace('/\s+/u', ' ', (string) $texto) ?? '';
        $texto = trim($texto);

        $mesesIngles = implode('|', array_values($meses));
        if (preg_match('/(\d{1,2})\s+(' . $mesesIngles . ')\s+(\d{4})/', $texto, $m)) {
            return $m[1] . ' ' . $m[2] . ' ' . $m[3];
        }
        if (preg_match('/(' . $mesesIngles . ')\s+(\d{1,2})\s+(\d{4})/', $texto, $m)) {
            return $m[2] . ' ' . $m[1] . ' ' . $m[3];
        }

        return $texto;
    }

    private static function respuestaLista(array $items, string $intro, string $cierre): ?string
    {
        return self::respuestaListaConIntro($items, $intro, $cierre);
    }

    private static function respuestaListaConIntro(array $items, string $intro, string $cierre): ?string
    {
        $lineas = [];
        foreach ($items as $item) {
            $titulo = self::texto((string) ($item['titulo'] ?? ''), 180);
            if ($titulo === '') {
                continue;
            }
            $fecha = isset($item['fecha']) && is_string($item['fecha']) ? self::fechaLarga($item['fecha']) : '';
            $categoria = self::texto((string) ($item['categoria'] ?? ''), 80);
            $detalle = $fecha !== '' ? $fecha : $categoria;
            $lineas[] = '• ' . ($detalle !== '' ? $detalle . ': ' : '') . $titulo;
        }
        if ($lineas === []) {
            return null;
        }

        return $intro . "\n\n" . implode("\n", $lineas) . "\n\n" . $cierre;
    }

    private static function fechaLarga(string $iso): string
    {
        $ts = strtotime($iso . ' 12:00:00');
        if ($ts === false) {
            return $iso;
        }
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
            'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
        ];
        return $dias[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' de ' .
            $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    }

    private static function ordenarPorFechaAsc(array $a, array $b): int
    {
        return strcmp((string) ($a['fecha'] ?? ''), (string) ($b['fecha'] ?? ''));
    }

    private static function ordenarPorFechaDesc(array $a, array $b): int
    {
        $fa = (string) ($a['fecha'] ?? '');
        $fb = (string) ($b['fecha'] ?? '');
        if ($fa === '' && $fb === '') {
            return strcmp((string) ($a['titulo'] ?? ''), (string) ($b['titulo'] ?? ''));
        }
        if ($fa === '') {
            return 1;
        }
        if ($fb === '') {
            return -1;
        }
        return strcmp($fb, $fa);
    }

    private static function texto(string $texto, int $maximo): string
    {
        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $texto) ?? '';
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';
        $texto = trim($texto);
        if (mb_strlen($texto, 'UTF-8') > $maximo) {
            $texto = rtrim(mb_substr($texto, 0, $maximo - 1, 'UTF-8')) . '…';
        }
        return $texto;
    }

    private static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto) ?? '';
        return trim($texto);
    }
}
