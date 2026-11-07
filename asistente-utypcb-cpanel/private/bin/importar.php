<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Utypcb\Asistente\Knowledge\Catalog;
use Utypcb\Asistente\Knowledge\Seed;
use Utypcb\Asistente\Support\Db;
use Utypcb\Asistente\Support\Logger;
use Utypcb\Asistente\Support\Text;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por consola.\n");
    exit(1);
}

$actualizar = in_array('--actualizar', $argv, true);
$dryRun = in_array('--dry-run', $argv, true) || in_array('--simular', $argv, true);
$directorio = dirname(UTYP_ASISTENTE_PRIVATE) . '/database/seed';

try {
    $areas = Seed::areas($directorio);
    $intenciones = Seed::intenciones($directorio);
} catch (Throwable $e) {
    fwrite(STDERR, 'Semilla inválida: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$errores = [];
$areasPorSlug = [];
$variantesGlobales = [];

$validarUrl = static function ($url): bool {
    return $url === null || $url === '' || Catalog::urlSegura((string) $url) !== null;
};

foreach ($areas as $indice => $area) {
    $fila = $indice + 1;
    if (!is_array($area)) {
        $errores[] = 'Área ' . $fila . ': estructura inválida.';
        continue;
    }
    $slug = (string) ($area['slug'] ?? '');
    if (!preg_match('/^[a-z0-9-]{3,60}$/', $slug)) {
        $errores[] = 'Área ' . $fila . ': slug inválido.';
        continue;
    }
    if (isset($areasPorSlug[$slug])) {
        $errores[] = 'Área duplicada: ' . $slug;
    }
    if (trim((string) ($area['nombre'] ?? '')) === '') {
        $errores[] = 'Área ' . $slug . ': falta el nombre.';
    }
    if (!empty($area['correo']) && Catalog::correoSeguro((string) $area['correo']) === null) {
        $errores[] = 'Área ' . $slug . ': correo inválido.';
    }
    if (!$validarUrl($area['url_contacto'] ?? null)) {
        $errores[] = 'Área ' . $slug . ': URL inválida.';
    }
    $areasPorSlug[$slug] = true;
}

$categorias = Catalog::categorias();
$programas = Catalog::programas();
$temas = Catalog::temas();
$slugsVistos = [];
$totalVariantes = 0;

foreach ($intenciones as $indice => $intencion) {
    $fila = $indice + 1;
    $slug = (string) ($intencion['slug'] ?? '');
    $etiqueta = $slug !== '' ? $slug : ('#' . $fila);

    if (!preg_match('/^[a-z0-9-]{3,100}$/', $slug)) {
        $errores[] = $etiqueta . ': slug inválido.';
        continue;
    }
    if (isset($slugsVistos[$slug])) {
        $errores[] = $etiqueta . ': slug duplicado.';
    }
    $slugsVistos[$slug] = true;

    $categoria = (string) ($intencion['categoria'] ?? '');
    if (!isset($categorias[$categoria])) {
        $errores[] = $etiqueta . ': categoría desconocida (' . $categoria . ').';
    }
    $areaSlug = (string) ($intencion['area'] ?? '');
    if (!isset($areasPorSlug[$areaSlug])) {
        $errores[] = $etiqueta . ': área inexistente (' . $areaSlug . ').';
    }

    $pregunta = (string) ($intencion['pregunta'] ?? '');
    if (mb_strlen(trim($pregunta), 'UTF-8') < 5 || mb_strlen($pregunta, 'UTF-8') > 255) {
        $errores[] = $etiqueta . ': pregunta fuera de rango.';
    }

    $respuesta = (string) ($intencion['respuesta'] ?? '');
    if (mb_strlen(trim($respuesta), 'UTF-8') < 20) {
        $errores[] = $etiqueta . ': respuesta demasiado corta.';
    }

    if (trim((string) ($intencion['fuente_titulo'] ?? '')) === '') {
        $errores[] = $etiqueta . ': falta el título de la fuente.';
    }
    if (Catalog::urlSegura((string) ($intencion['fuente_url'] ?? '')) === null) {
        $errores[] = $etiqueta . ': URL de fuente inválida.';
    }

    $programa = $intencion['programa'] ?? null;
    if ($programa !== null && !isset($programas[(string) $programa])) {
        $errores[] = $etiqueta . ': programa inexistente (' . $programa . ').';
    }
    $tema = $intencion['tema'] ?? null;
    if ($tema !== null && !isset($temas[(string) $tema])) {
        $errores[] = $etiqueta . ': tema inexistente (' . $tema . ').';
    }

    $prioridad = (int) ($intencion['prioridad'] ?? 0);
    if ($prioridad < 0 || $prioridad > 10) {
        $errores[] = $etiqueta . ': prioridad fuera de 0–10.';
    }

    $claves = $intencion['palabras_clave'] ?? [];
    if (!is_array($claves) || count($claves) < 2) {
        $errores[] = $etiqueta . ': se requieren al menos 2 palabras clave.';
    } else {
        foreach ($claves as $clave) {
            if (!is_string($clave) || trim($clave) === '') {
                $errores[] = $etiqueta . ': palabra clave vacía.';
                break;
            }
        }
    }

    $enlaces = $intencion['enlaces'] ?? [];
    if (!is_array($enlaces)) {
        $errores[] = $etiqueta . ': enlaces debe ser un arreglo.';
    } else {
        foreach ($enlaces as $enlace) {
            if (!is_array($enlace) || Catalog::urlSegura(isset($enlace['url']) ? (string) $enlace['url'] : null) === null) {
                $errores[] = $etiqueta . ': enlace inválido.';
                break;
            }
        }
    }

    $variantes = $intencion['variantes'] ?? [];
    if (!is_array($variantes) || count($variantes) !== 5) {
        $errores[] = $etiqueta . ': debe tener exactamente 5 variantes.';
        continue;
    }

    $normales = [];
    foreach ($variantes as $variante) {
        if (!is_string($variante)) {
            $errores[] = $etiqueta . ': variante no textual.';
            continue;
        }
        $variante = trim($variante);
        $norm = Text::normalizar($variante);
        if (mb_strlen($variante, 'UTF-8') < 2 || mb_strlen($variante, 'UTF-8') > 255 || $norm === '') {
            $errores[] = $etiqueta . ': variante fuera de rango.';
            continue;
        }
        if (mb_strlen($norm, 'UTF-8') > 255) {
            $errores[] = $etiqueta . ': variante normalizada demasiado larga.';
            continue;
        }
        if (isset($normales[$norm])) {
            $errores[] = $etiqueta . ': variantes repetidas después de normalizar ("' . $norm . '").';
        }
        if (isset($variantesGlobales[$norm])) {
            $errores[] = $etiqueta . ': variante idéntica a otra intención (' . $variantesGlobales[$norm] . ').';
        }
        $normales[$norm] = true;
        $variantesGlobales[$norm] = $slug;
        $totalVariantes++;
    }
}

if ($errores !== []) {
    fwrite(STDERR, "Se encontraron " . count($errores) . " errores en la semilla:\n");
    foreach (array_slice($errores, 0, 50) as $error) {
        fwrite(STDERR, '  - ' . $error . PHP_EOL);
    }
    if (count($errores) > 50) {
        fwrite(STDERR, '  … y ' . (count($errores) - 50) . " más.\n");
    }
    exit(1);
}

echo 'Áreas: ' . count($areas) . PHP_EOL;
echo 'Intenciones: ' . count($intenciones) . PHP_EOL;
echo 'Variantes: ' . $totalVariantes . PHP_EOL;
if ($dryRun) {
    echo "Validación correcta (simulación; no se escribió en la base).\n";
    exit(0);
}

$pdo = Db::pdo();
$nuevas = 0;
$actualizadas = 0;
$omitidas = 0;
$variantesInsertadas = 0;

try {
    $pdo->beginTransaction();

    $stmtArea = $pdo->prepare(
        'INSERT INTO asistente_areas (slug, nombre, correo, telefono, url_contacto)
         VALUES (:slug, :nombre, :correo, :telefono, :url)
         ON DUPLICATE KEY UPDATE ' .
        ($actualizar
            ? 'nombre = VALUES(nombre), correo = VALUES(correo), telefono = VALUES(telefono), url_contacto = VALUES(url_contacto)'
            : 'slug = VALUES(slug)')
    );

    foreach ($areas as $area) {
        $stmtArea->execute([
            ':slug' => (string) $area['slug'],
            ':nombre' => (string) $area['nombre'],
            ':correo' => !empty($area['correo']) ? (string) $area['correo'] : null,
            ':telefono' => !empty($area['telefono']) ? (string) $area['telefono'] : null,
            ':url' => !empty($area['url_contacto']) ? (string) $area['url_contacto'] : null,
        ]);
    }

    $mapaAreas = [];
    foreach ($pdo->query('SELECT id, slug FROM asistente_areas')->fetchAll() as $filaArea) {
        $mapaAreas[(string) $filaArea['slug']] = (int) $filaArea['id'];
    }

    $buscar = $pdo->prepare('SELECT id FROM asistente_intents WHERE slug = :slug');

    $insertar = $pdo->prepare(
        'INSERT INTO asistente_intents
            (slug, categoria, area_id, pregunta, respuesta, fuente_titulo, fuente_url, enlaces_json,
             palabras_clave, programa_slug, tema_slug, prioridad, validada, activa)
         VALUES
            (:slug, :categoria, :area_id, :pregunta, :respuesta, :fuente_titulo, :fuente_url, :enlaces,
             :claves, :programa, :tema, :prioridad, 0, 1)'
    );

    $actualizarStmt = $pdo->prepare(
        'UPDATE asistente_intents SET
            categoria = :categoria, area_id = :area_id, pregunta = :pregunta, respuesta = :respuesta,
            fuente_titulo = :fuente_titulo, fuente_url = :fuente_url, enlaces_json = :enlaces,
            palabras_clave = :claves, programa_slug = :programa, tema_slug = :tema,
            prioridad = :prioridad, validada = 0, validada_por = NULL, validada_en = NULL, activa = 1
         WHERE id = :id'
    );

    $borrarBase = $pdo->prepare("DELETE FROM asistente_intent_variants WHERE intent_id = :id AND origen = 'base'");
    $insertarVariante = $pdo->prepare(
        "INSERT IGNORE INTO asistente_intent_variants (intent_id, texto, texto_norm, origen)
         VALUES (:intent_id, :texto, :norm, 'base')"
    );

    foreach ($intenciones as $intencion) {
        $slug = (string) $intencion['slug'];
        $buscar->execute([':slug' => $slug]);
        $existente = $buscar->fetch();

        if ($existente !== false && !$actualizar) {
            $omitidas++;
            continue;
        }

        $parametros = [
            ':categoria' => (string) $intencion['categoria'],
            ':area_id' => $mapaAreas[(string) $intencion['area']],
            ':pregunta' => (string) $intencion['pregunta'],
            ':respuesta' => (string) $intencion['respuesta'],
            ':fuente_titulo' => (string) $intencion['fuente_titulo'],
            ':fuente_url' => (string) $intencion['fuente_url'],
            ':enlaces' => json_encode($intencion['enlaces'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':claves' => implode(', ', $intencion['palabras_clave']),
            ':programa' => $intencion['programa'] !== null ? (string) $intencion['programa'] : null,
            ':tema' => $intencion['tema'] !== null ? (string) $intencion['tema'] : null,
            ':prioridad' => (int) $intencion['prioridad'],
        ];

        if ($existente === false) {
            $insertar->execute(array_merge($parametros, [':slug' => $slug]));
            $intentId = (int) $pdo->lastInsertId();
            $nuevas++;
        } else {
            $intentId = (int) $existente['id'];
            $actualizarStmt->execute(array_merge($parametros, [':id' => $intentId]));
            $actualizadas++;
        }

        $borrarBase->execute([':id' => $intentId]);
        foreach ($intencion['variantes'] as $variante) {
            $insertarVariante->execute([
                ':intent_id' => $intentId,
                ':texto' => trim((string) $variante),
                ':norm' => Text::normalizar((string) $variante),
            ]);
            $variantesInsertadas += $insertarVariante->rowCount();
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'No se pudo importar: ' . $e->getMessage() . PHP_EOL);
    Logger::error('Falló la importación de la semilla.', ['exception' => get_class($e)]);
    exit(1);
}

Logger::auditoria('Importación de semilla', [
    'nuevas' => $nuevas,
    'actualizadas' => $actualizadas,
    'omitidas' => $omitidas,
    'variantes' => $variantesInsertadas,
]);

echo 'Nuevas: ' . $nuevas . PHP_EOL;
echo 'Actualizadas: ' . $actualizadas . PHP_EOL;
echo 'Omitidas (ya existían): ' . $omitidas . PHP_EOL;
echo 'Variantes insertadas: ' . $variantesInsertadas . PHP_EOL;
echo "Importación terminada.\n";
