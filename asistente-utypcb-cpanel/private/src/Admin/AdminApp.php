<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Admin;

use Throwable;
use Utypcb\Asistente\Knowledge\Catalog;
use Utypcb\Asistente\Knowledge\Repository;
use Utypcb\Asistente\Site\Sync;
use Utypcb\Asistente\Support\Config;
use Utypcb\Asistente\Support\Http;
use Utypcb\Asistente\Support\HttpException;
use Utypcb\Asistente\Support\Logger;
use Utypcb\Asistente\Support\RateLimiter;
use Utypcb\Asistente\Support\Text;
use Utypcb\Asistente\Support\View;

final class AdminApp
{
    private AdminRepository $repo;
    private Auth $auth;
    private array $usuario = [];
    private string $base = '/asistente/admin';

    private function __construct()
    {
        $this->repo = new AdminRepository();
        $this->auth = new Auth($this->repo);
    }

    public static function run(): void
    {
        (new self())->manejar();
    }

    private function manejar(): void
    {
        Http::cabecerasSeguridad(true);
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('X-Robots-Tag: noindex, nofollow');
        }

        $this->base = $this->detectarBase();

        try {
            Http::sesionAdmin();
            $ruta = isset($_GET['r']) && is_string($_GET['r']) ? $_GET['r'] : 'dashboard';
            if (!preg_match('/^[a-z-]{3,30}$/', $ruta)) {
                throw new HttpException(404, 'La página solicitada no existe.');
            }

            if ($ruta === 'login') {
                $this->login();
                return;
            }

            try {
                $this->usuario = $this->auth->requerir();
            } catch (HttpException $e) {
                if ($e->status() === 401) {
                    $this->redirigir('login', ['motivo' => 'sesion']);
                }
                throw $e;
            }

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                Http::exigirOrigen();
                Http::exigirCsrf();
                $this->accion($ruta);
                return;
            }

            $this->pagina($ruta);
        } catch (HttpException $e) {
            $this->mostrarError($e->status(), $e->publicMessage());
        } catch (Throwable $e) {
            Logger::error('Fallo en el panel administrativo.', [
                'exception' => get_class($e),
                'ruta' => isset($_GET['r']) ? (string) $_GET['r'] : '',
            ]);
            $this->mostrarError(500, 'Ocurrió un error inesperado. Revisa la bitácora del servidor.');
        }
    }

    private function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if (isset($_SESSION['admin_id'])) {
                try {
                    $this->auth->requerir();
                    $this->redirigir('dashboard');
                } catch (HttpException $e) {
                    if ($e->status() !== 401) {
                        throw $e;
                    }
                }
            }
            $this->renderLogin(null, null);
            return;
        }

        Http::exigirOrigen();
        Http::exigirCsrf();

        $usuario = Http::textoPost('usuario', 60);
        $password = isset($_POST['password']) && is_string($_POST['password'])
            ? $_POST['password']
            : '';
        if (strlen($password) > 200) {
            $password = substr($password, 0, 200);
        }

        $resultado = $this->auth->intentar($usuario, $password);
        if ($resultado['ok'] === true) {
            $this->auth->flash('ok', 'Bienvenido al panel del Asistente Coyote.');
            $this->redirigir('dashboard');
        }

        if (!empty($resultado['reintentar']) && !headers_sent()) {
            header('Retry-After: ' . (int) $resultado['reintentar']);
            http_response_code(429);
        }
        $this->renderLogin($resultado['error'] ?? 'No se pudo iniciar sesión.', $usuario);
    }

    private function renderLogin(?string $error, ?string $usuario): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Http::sesionAdmin();
        }
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        $motivo = isset($_GET['motivo']) && is_string($_GET['motivo']) ? $_GET['motivo'] : '';
        View::render('admin/login', [
            'base' => $this->base,
            'csrf' => Http::csrfToken(),
            'url' => function (string $r = 'login', array $p = []): string {
                return $this->url($r, $p);
            },
            'error' => $error,
            'usuario' => $usuario,
            'motivo' => in_array($motivo, ['sesion', 'salir'], true) ? $motivo : '',
        ]);
    }

    private function pagina(string $ruta): void
    {
        if ($ruta === 'dashboard') {
            $resumen = $this->repo->resumen();
            $this->render('dashboard', [
                'resumen' => $resumen,
                'ultimasPendientes' => $this->repo->listarNoRespondidas('pendientes', '', 6),
                'ultimaSync' => $this->repo->ultimaSync(),
            ], 'dashboard', 'Panel general');
            return;
        }

        if ($ruta === 'intents') {
            $buscar = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 80, 'UTF-8') : '';
            $categoria = isset($_GET['categoria']) && is_string($_GET['categoria']) ? $_GET['categoria'] : '';
            if ($categoria !== '' && !isset(Catalog::categorias()[$categoria])) {
                $categoria = '';
            }
            $estado = isset($_GET['estado']) && is_string($_GET['estado']) ? $_GET['estado'] : 'todas';
            if (!in_array($estado, ['todas', 'activas', 'inactivas', 'pendientes', 'validadas'], true)) {
                $estado = 'todas';
            }
            $this->render('intents', [
                'intents' => $this->repo->listarIntents($buscar, $categoria, $estado, 250),
                'buscar' => $buscar,
                'categoria' => $categoria,
                'estado' => $estado,
                'categorias' => Catalog::categorias(),
            ], 'intents', 'Intenciones');
            return;
        }

        if ($ruta === 'intent') {
            $this->paginaIntent();
            return;
        }

        if ($ruta === 'unanswered') {
            $filtro = isset($_GET['filtro']) && is_string($_GET['filtro']) ? $_GET['filtro'] : 'pendientes';
            if (!in_array($filtro, ['pendientes', 'revisadas', 'todas'], true)) {
                $filtro = 'pendientes';
            }
            $buscar = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 80, 'UTF-8') : '';
            $this->render('unanswered', [
                'noRespondidas' => $this->repo->listarNoRespondidas($filtro, $buscar, 150),
                'conteos' => $this->repo->contarNoRespondidas(),
                'filtro' => $filtro,
                'buscar' => $buscar,
            ], 'unanswered', 'Preguntas sin respuesta');
            return;
        }

        if ($ruta === 'sync') {
            $this->render('sync', [
                'ultimaSync' => $this->repo->ultimaSync(),
                'syncLog' => $this->repo->listarSync(20),
                'fuentes' => Sync::estadoFuentes(),
                'fuentesConfig' => Config::get('sitio.archivos', []),
                'directorio' => (string) Config::get('sitio.directorio_datos', ''),
                'retencionNoRespondidas' => (int) Config::get('privacidad.retencion_no_respondidas_dias', 90),
                'retencionEventos' => (int) Config::get('privacidad.retencion_eventos_dias', 365),
            ], 'sync', 'Sincronización y retención');
            return;
        }

        if ($ruta === 'cuenta') {
            $this->render('cuenta', [], 'cuenta', 'Mi cuenta');
            return;
        }

        if ($ruta === 'admins') {
            $this->exigirSuperadmin();
            $resetId = isset($_GET['reset']) ? (int) $_GET['reset'] : 0;
            $this->renderAdmins([], [], 'crear', $resetId);
            return;
        }

        throw new HttpException(404, 'La página solicitada no existe.');
    }

    private function paginaIntent(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $intent = null;
        if ($id > 0) {
            $intent = $this->repo->obtenerIntent($id);
            if ($intent === null) {
                $this->auth->flash('error', 'La intención solicitada no existe.');
                $this->redirigir('intents');
            }
            $valores = $this->valoresDesdeDb($intent);
        } else {
            $valores = $this->valoresNuevos();
        }

        $noRespondida = null;
        if ($id === 0 && isset($_GET['desde'])) {
            $desde = (int) $_GET['desde'];
            if ($desde > 0) {
                $noRespondida = $this->repo->obtenerNoRespondida($desde);
                if ($noRespondida !== null) {
                    $valores['pregunta'] = (string) $noRespondida['pregunta'];
                    if ($valores['variantes'][0] === '') {
                        $valores['variantes'][0] = (string) $noRespondida['pregunta'];
                    }
                }
            }
        }

        $this->render('intent_form', [
            'intent' => $intent,
            'valores' => $valores,
            'errores' => [],
            'noRespondida' => $noRespondida,
            'esNuevo' => $id === 0,
            'categorias' => Catalog::categorias(),
            'areas' => $this->repo->listarAreas(),
            'programas' => Catalog::programas(),
            'temas' => Catalog::temas(),
        ], 'intents', $id > 0 ? 'Editar intención' : 'Nueva intención');
    }

    private function valoresNuevos(): array
    {
        return [
            'slug' => '',
            'categoria' => '',
            'area_id' => 0,
            'pregunta' => '',
            'respuesta' => '',
            'fuente_titulo' => '',
            'fuente_url' => '',
            'enlaces' => '',
            'palabras_clave' => '',
            'programa_slug' => '',
            'tema_slug' => '',
            'prioridad' => 5,
            'activa' => 1,
            'variantes' => ['', '', '', '', ''],
            'variantes_admin' => '',
        ];
    }

    private function valoresDesdeDb(array $intent): array
    {
        $base = [];
        $admin = [];
        foreach ($intent['variantes'] as $variante) {
            if ((string) $variante['origen'] === 'base') {
                $base[] = (string) $variante['texto'];
            } else {
                $admin[] = (string) $variante['texto'];
            }
        }
        while (count($base) < 5) {
            $base[] = '';
        }
        $base = array_slice($base, 0, 5);

        $enlaces = [];
        foreach ($intent['enlaces'] as $enlace) {
            if (!is_array($enlace)) {
                continue;
            }
            $enlaces[] = trim((string) ($enlace['titulo'] ?? '')) . ' | ' . trim((string) ($enlace['url'] ?? ''));
        }

        return [
            'slug' => (string) $intent['slug'],
            'categoria' => (string) $intent['categoria'],
            'area_id' => (int) $intent['area_id'],
            'pregunta' => (string) $intent['pregunta'],
            'respuesta' => (string) $intent['respuesta'],
            'fuente_titulo' => (string) $intent['fuente_titulo'],
            'fuente_url' => (string) $intent['fuente_url'],
            'enlaces' => implode("\n", $enlaces),
            'palabras_clave' => (string) ($intent['palabras_clave'] ?? ''),
            'programa_slug' => (string) ($intent['programa_slug'] ?? ''),
            'tema_slug' => (string) ($intent['tema_slug'] ?? ''),
            'prioridad' => (int) $intent['prioridad'],
            'activa' => (int) $intent['activa'],
            'variantes' => $base,
            'variantes_admin' => implode("\n", $admin),
        ];
    }

    private function accion(string $ruta): void
    {
        if ($ruta === 'logout') {
            $this->auth->salir();
            $this->redirigir('login', ['motivo' => 'salir']);
            return;
        }

        if ($ruta === 'intent-save') {
            $this->accionGuardarIntent();
            return;
        }

        if ($ruta === 'intent-toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            $intent = $this->repo->obtenerIntent($id);
            if ($intent === null) {
                $this->auth->flash('error', 'La intención solicitada no existe.');
                $this->redirigir('intents');
            }
            $activa = (string) ($_POST['activa'] ?? '0') === '1';
            $this->repo->alternarActiva($id, $activa);
            Logger::auditoria('Cambio de estado de intención', [
                'id' => $id,
                'slug' => $intent['slug'],
                'activa' => $activa,
                'admin' => $this->usuario['usuario'],
            ]);
            $this->auth->flash('ok', $activa ? 'La intención quedó activa.' : 'La intención quedó desactivada.');
            $this->redirigir('intents');
            return;
        }

        if ($ruta === 'intent-delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $intent = $this->repo->obtenerIntent($id);
            if ($intent === null) {
                $this->auth->flash('error', 'La intención solicitada no existe.');
                $this->redirigir('intents');
            }
            $this->repo->eliminarIntent($id);
            Logger::auditoria('Intención eliminada', [
                'id' => $id,
                'slug' => $intent['slug'],
                'admin' => $this->usuario['usuario'],
            ]);
            $this->auth->flash('ok', 'La intención se eliminó de la base de conocimiento.');
            $this->redirigir('intents');
            return;
        }

        if ($ruta === 'intent-validate' || $ruta === 'intent-unvalidate') {
            $id = (int) ($_POST['id'] ?? 0);
            $intent = $this->repo->obtenerIntent($id);
            if ($intent === null) {
                $this->auth->flash('error', 'La intención solicitada no existe.');
                $this->redirigir('intents');
            }
            if ($ruta === 'intent-validate') {
                $this->repo->marcarValidada($id, (string) $this->usuario['nombre']);
                $this->auth->flash('ok', 'La intención quedó marcada como validada.');
            } else {
                $this->repo->quitarValidacion($id);
                $this->auth->flash('info', 'Se retiró la validación de la intención.');
            }
            Logger::auditoria('Cambio de validación de intención', [
                'id' => $id,
                'slug' => $intent['slug'],
                'accion' => $ruta,
                'admin' => $this->usuario['usuario'],
            ]);
            $this->redirigir('intent', ['id' => $id]);
            return;
        }

        if ($ruta === 'unanswered-update') {
            $id = (int) ($_POST['id'] ?? 0);
            $registro = $this->repo->obtenerNoRespondida($id);
            if ($registro === null) {
                $this->auth->flash('error', 'La pregunta ya no existe.');
                $this->redirigir('unanswered');
            }
            $revisada = isset($_POST['revisada']);
            $nota = Http::textoPost('nota', 500);
            $this->repo->actualizarNoRespondida($id, $revisada, $nota);
            Logger::auditoria('Revisión de pregunta sin respuesta', [
                'id' => $id,
                'revisada' => $revisada,
                'admin' => $this->usuario['usuario'],
            ]);
            $this->auth->flash('ok', 'Se guardó la revisión de la pregunta.');
            $this->redirigir('unanswered');
            return;
        }

        if ($ruta === 'unanswered-delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $this->repo->eliminarNoRespondida($id);
            Logger::auditoria('Pregunta sin respuesta eliminada', [
                'id' => $id,
                'admin' => $this->usuario['usuario'],
            ]);
            $this->auth->flash('ok', 'La pregunta se eliminó del registro.');
            $this->redirigir('unanswered');
            return;
        }

        if ($ruta === 'sync-run') {
            $this->accionSync();
            return;
        }

        if ($ruta === 'sync-purge') {
            $diasNoRespondidas = (int) Config::get('privacidad.retencion_no_respondidas_dias', 90);
            $diasEventos = (int) Config::get('privacidad.retencion_eventos_dias', 365);
            $repo = new Repository();
            $resultado = $repo->purgar($diasNoRespondidas, $diasEventos);
            (new RateLimiter())->limpiar();
            Logger::auditoria('Purga manual de datos', [
                'no_respondidas' => $resultado['no_respondidas'],
                'eventos' => $resultado['eventos'],
                'admin' => $this->usuario['usuario'],
            ]);
            $this->auth->flash(
                'ok',
                'Purga terminada: ' . $resultado['no_respondidas'] . ' preguntas y ' .
                $resultado['eventos'] . ' eventos eliminados.'
            );
            $this->redirigir('sync');
            return;
        }

        if ($ruta === 'cuenta-save') {
            $this->accionCuenta();
            return;
        }

        if ($ruta === 'admin-create') {
            $this->accionCrearAdmin();
            return;
        }

        if ($ruta === 'admin-toggle') {
            $this->accionAlternarAdmin();
            return;
        }

        if ($ruta === 'admin-password') {
            $this->accionResetPasswordAdmin();
            return;
        }

        throw new HttpException(404, 'La acción solicitada no existe.');
    }

    private function accionGuardarIntent(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $intent = $id > 0 ? $this->repo->obtenerIntent($id) : null;
        if ($id > 0 && $intent === null) {
            throw new HttpException(404, 'La intención que intentas editar no existe.');
        }

        $datos = $this->datosDesdePost();
        $errores = $this->repo->validarIntent($datos, $id > 0 ? $id : null);
        $noRespondida = null;
        $noRespondidaId = (int) ($_POST['no_respondida_id'] ?? 0);
        if ($noRespondidaId > 0) {
            $noRespondida = $this->repo->obtenerNoRespondida($noRespondidaId);
        }

        if ($errores !== []) {
            if (!headers_sent()) {
                http_response_code(422);
            }
            $this->render('intent_form', [
                'intent' => $intent,
                'valores' => $datos,
                'errores' => $errores,
                'noRespondida' => $noRespondida,
                'esNuevo' => $id === 0,
                'categorias' => Catalog::categorias(),
                'areas' => $this->repo->listarAreas(),
                'programas' => Catalog::programas(),
                'temas' => Catalog::temas(),
            ], 'intents', $id > 0 ? 'Editar intención' : 'Nueva intención');
            return;
        }

        try {
            $guardado = $this->repo->guardarIntent($datos, $id > 0 ? $id : null);
        } catch (Throwable $e) {
            Logger::error('No se pudo guardar la intención.', [
                'exception' => get_class($e),
                'detalle' => $e->getMessage(),
                'id' => $id,
            ]);
            $errores['general'] = 'No se pudo guardar. Revisa que el identificador y las variantes no estén duplicados.';
            if (!headers_sent()) {
                http_response_code(422);
            }
            $this->render('intent_form', [
                'intent' => $intent,
                'valores' => $datos,
                'errores' => $errores,
                'noRespondida' => $noRespondida,
                'esNuevo' => $id === 0,
                'categorias' => Catalog::categorias(),
                'areas' => $this->repo->listarAreas(),
                'programas' => Catalog::programas(),
                'temas' => Catalog::temas(),
            ], 'intents', $id > 0 ? 'Editar intención' : 'Nueva intención');
            return;
        }

        if ($noRespondidaId > 0) {
            $this->repo->vincularNoRespondida($noRespondidaId, $guardado);
        }

        Logger::auditoria($id > 0 ? 'Intención actualizada' : 'Intención creada', [
            'id' => $guardado,
            'slug' => $datos['slug'],
            'admin' => $this->usuario['usuario'],
        ]);
        $this->auth->flash('ok', $id > 0 ? 'Los cambios se guardaron.' : 'La intención se creó correctamente.');
        $this->redirigir('intent', ['id' => $guardado]);
    }

    private function datosDesdePost(): array
    {
        $variantes = [];
        $entrantes = $_POST['variantes'] ?? [];
        if (is_array($entrantes)) {
            for ($i = 0; $i < 5; $i++) {
                $variantes[] = isset($entrantes[$i]) && is_string($entrantes[$i])
                    ? mb_substr(trim($entrantes[$i]), 0, 255, 'UTF-8')
                    : '';
            }
        } else {
            $variantes = ['', '', '', '', ''];
        }

        $admin = [];
        $textoAdmin = isset($_POST['variantes_admin']) && is_string($_POST['variantes_admin'])
            ? $_POST['variantes_admin']
            : '';
        foreach (preg_split('/\r\n|\r|\n/', $textoAdmin) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea !== '') {
                $admin[] = mb_substr($linea, 0, 255, 'UTF-8');
            }
        }

        $enlaces = [];
        $textoEnlaces = isset($_POST['enlaces']) && is_string($_POST['enlaces']) ? $_POST['enlaces'] : '';
        foreach (preg_split('/\r\n|\r|\n/', $textoEnlaces) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }
            $partes = explode('|', $linea, 2);
            $titulo = trim($partes[0]);
            $url = trim($partes[1] ?? '');
            if ($url === '') {
                $url = $titulo;
                $titulo = 'Más información';
            }
            $enlaces[] = [
                'titulo' => mb_substr($titulo, 0, 120, 'UTF-8'),
                'url' => mb_substr($url, 0, 500, 'UTF-8'),
            ];
        }

        $claves = [];
        $textoClaves = isset($_POST['palabras_clave']) && is_string($_POST['palabras_clave'])
            ? $_POST['palabras_clave']
            : '';
        foreach (preg_split('/[,;\r\n]+/', $textoClaves) ?: [] as $clave) {
            $clave = trim($clave);
            if ($clave === '') {
                continue;
            }
            $clave = mb_substr($clave, 0, 80, 'UTF-8');
            $norm = Text::normalizar($clave);
            if ($norm !== '' && !isset($claves[$norm])) {
                $claves[$norm] = $clave;
            }
        }

        $programa = Http::textoPost('programa_slug', 60);
        $tema = Http::textoPost('tema_slug', 60);

        return [
            'slug' => mb_strtolower(Http::textoPost('slug', 100), 'UTF-8'),
            'categoria' => Http::textoPost('categoria', 60),
            'area_id' => (int) ($_POST['area_id'] ?? 0),
            'pregunta' => Http::textoPost('pregunta', 255),
            'respuesta' => Http::textoPost('respuesta', 20000),
            'fuente_titulo' => Http::textoPost('fuente_titulo', 160),
            'fuente_url' => Http::textoPost('fuente_url', 500),
            'enlaces' => array_slice($enlaces, 0, 6),
            'palabras_clave' => array_slice(array_values($claves), 0, 31),
            'programa_slug' => $programa !== '' ? $programa : null,
            'tema_slug' => $tema !== '' ? $tema : null,
            'prioridad' => (int) ($_POST['prioridad'] ?? 0),
            'activa' => isset($_POST['activa']) ? 1 : 0,
            'variantes' => $variantes,
            'variantes_admin' => array_slice($admin, 0, 11),
        ];
    }

    private function accionSync(): void
    {
        try {
            $resultado = Sync::ejecutar($this->usuario['usuario']);
            $estado = (string) ($resultado['estado'] ?? 'error');
            $mensaje = (string) ($resultado['mensaje'] ?? 'Sincronización terminada.');
            if ($estado === 'ok') {
                $this->auth->flash('ok', $mensaje);
            } elseif ($estado === 'sin_datos') {
                $this->auth->flash('info', $mensaje);
            } else {
                $this->auth->flash('error', $mensaje);
            }
            Logger::auditoria('Sincronización manual', [
                'estado' => $estado,
                'actualizados' => (int) ($resultado['actualizados'] ?? 0),
                'admin' => $this->usuario['usuario'],
            ]);
        } catch (Throwable $e) {
            Logger::error('Falló la sincronización manual.', ['exception' => get_class($e)]);
            $this->auth->flash('error', 'No se pudo completar la sincronización. Revisa la bitácora del servidor.');
        }
        $this->redirigir('sync');
    }

    private function accionCuenta(): void
    {
        $actual = isset($_POST['actual']) && is_string($_POST['actual']) ? $_POST['actual'] : '';
        $nueva = isset($_POST['nueva']) && is_string($_POST['nueva']) ? $_POST['nueva'] : '';
        $confirmar = isset($_POST['confirmar']) && is_string($_POST['confirmar']) ? $_POST['confirmar'] : '';
        $errores = [];

        if (!password_verify($actual, (string) $this->usuario['password_hash'])) {
            $errores['actual'] = 'La contraseña actual no es correcta.';
        }
        $largo = mb_strlen($nueva, 'UTF-8');
        if ($largo < 12 || $largo > 200) {
            $errores['nueva'] = 'La nueva contraseña debe tener entre 12 y 200 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $errores['confirmar'] = 'La confirmación no coincide con la nueva contraseña.';
        } elseif ($actual !== '' && hash_equals($actual, $nueva)) {
            $errores['nueva'] = 'La nueva contraseña debe ser distinta de la actual.';
        }

        if ($errores !== []) {
            if (!headers_sent()) {
                http_response_code(422);
            }
            $this->render('cuenta', ['errores' => $errores], 'cuenta', 'Mi cuenta');
            return;
        }

        $this->repo->actualizarPassword((int) $this->usuario['id'], password_hash($nueva, PASSWORD_DEFAULT));
        Http::regenerarSesion();
        Logger::auditoria('Contraseña administrativa actualizada', [
            'usuario' => $this->usuario['usuario'],
            'admin' => $this->usuario['usuario'],
        ]);
        $this->auth->flash('ok', 'Tu contraseña se actualizó correctamente.');
        $this->redirigir('cuenta');
    }

    private function exigirSuperadmin(): void
    {
        if ((string) ($this->usuario['rol'] ?? 'admin') !== 'superadmin') {
            throw new HttpException(403, 'Solo la cuenta principal puede gestionar administradores.');
        }
    }

    private function renderAdmins(array $errores = [], array $valores = [], string $modo = 'crear', int $resetId = 0): void
    {
        $this->exigirSuperadmin();
        $this->render('admins', [
            'admins' => $this->repo->listarAdmins(),
            'errores' => $errores,
            'valores' => array_merge(['usuario' => '', 'nombre' => ''], $valores),
            'modo' => $modo,
            'resetId' => $resetId,
        ], 'admins', 'Administradores');
    }

    private function accionCrearAdmin(): void
    {
        $this->exigirSuperadmin();
        $usuario = mb_strtolower(Http::textoPost('usuario', 60), 'UTF-8');
        $nombre = Http::textoPost('nombre', 120);
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $confirmar = isset($_POST['confirmar']) && is_string($_POST['confirmar']) ? $_POST['confirmar'] : '';
        $errores = [];

        if (!preg_match('/^[a-z0-9._-]{3,60}$/', $usuario)) {
            $errores['usuario'] = 'Usa de 3 a 60 caracteres: minúsculas, números, punto, guion o guion bajo.';
        } elseif ($this->repo->usuarioPorNombre($usuario) !== null) {
            $errores['usuario'] = 'Ese usuario ya existe. Elige otro.';
        }
        if (mb_strlen($nombre, 'UTF-8') < 3 || mb_strlen($nombre, 'UTF-8') > 120) {
            $errores['nombre'] = 'El nombre visible debe tener entre 3 y 120 caracteres.';
        }
        $largo = mb_strlen($password, 'UTF-8');
        if ($largo < 12 || $largo > 200) {
            $errores['password'] = 'La contraseña debe tener entre 12 y 200 caracteres.';
        } elseif ($password !== $confirmar) {
            $errores['confirmar'] = 'La confirmación no coincide con la contraseña.';
        }

        if ($errores !== []) {
            if (!headers_sent()) {
                http_response_code(422);
            }
            $this->renderAdmins($errores, ['usuario' => $usuario, 'nombre' => $nombre]);
            return;
        }

        $id = $this->repo->crearAdmin($usuario, password_hash($password, PASSWORD_DEFAULT), $nombre);
        Logger::auditoria('Cuenta administrativa de apoyo creada', [
            'id' => $id,
            'usuario' => $usuario,
            'admin' => $this->usuario['usuario'],
        ]);
        $this->auth->flash('ok', 'La cuenta de ' . $nombre . ' quedó activa como administrador de apoyo.');
        $this->redirigir('admins');
    }

    private function accionAlternarAdmin(): void
    {
        $this->exigirSuperadmin();
        $id = (int) ($_POST['id'] ?? 0);
        $activo = (string) ($_POST['activo'] ?? '0') === '1';
        $admin = $this->repo->usuarioPorId($id);
        if ($admin === null) {
            $this->auth->flash('error', 'La cuenta administrativa no existe.');
            $this->redirigir('admins');
        }
        if ($id === (int) $this->usuario['id']) {
            $this->auth->flash('error', 'No puedes desactivar tu propia cuenta. Usa Mi cuenta para cambiar la contraseña.');
            $this->redirigir('admins');
        }
        if (!$activo && (string) $admin['rol'] === 'superadmin' && $this->repo->contarSuperadminsActivos() <= 1) {
            $this->auth->flash('error', 'Debe quedar al menos una cuenta principal activa.');
            $this->redirigir('admins');
        }

        $this->repo->alternarAdmin($id, $activo);
        Logger::auditoria('Estado de cuenta administrativa actualizado', [
            'id' => $id,
            'usuario' => $admin['usuario'],
            'activo' => $activo,
            'admin' => $this->usuario['usuario'],
        ]);
        $this->auth->flash('ok', $activo ? 'La cuenta quedó activa.' : 'La cuenta quedó desactivada.');
        $this->redirigir('admins');
    }

    private function accionResetPasswordAdmin(): void
    {
        $this->exigirSuperadmin();
        $id = (int) ($_POST['id'] ?? 0);
        $admin = $this->repo->usuarioPorId($id);
        if ($admin === null) {
            $this->auth->flash('error', 'La cuenta administrativa no existe.');
            $this->redirigir('admins');
        }
        $nueva = isset($_POST['nueva']) && is_string($_POST['nueva']) ? $_POST['nueva'] : '';
        $confirmar = isset($_POST['confirmar']) && is_string($_POST['confirmar']) ? $_POST['confirmar'] : '';
        $errores = [];
        $largo = mb_strlen($nueva, 'UTF-8');
        if ($largo < 12 || $largo > 200) {
            $errores['nueva'] = 'La contraseña debe tener entre 12 y 200 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $errores['confirmar'] = 'La confirmación no coincide con la contraseña.';
        }

        if ($errores !== []) {
            if (!headers_sent()) {
                http_response_code(422);
            }
            $this->renderAdmins($errores, [], 'reset', $id);
            return;
        }

        $this->repo->actualizarPassword($id, password_hash($nueva, PASSWORD_DEFAULT));
        Logger::auditoria('Contraseña de cuenta administrativa restablecida', [
            'id' => $id,
            'usuario' => $admin['usuario'],
            'admin' => $this->usuario['usuario'],
        ]);
        $this->auth->flash('ok', 'La contraseña de ' . $admin['usuario'] . ' se actualizó. Compártela por un medio seguro.');
        $this->redirigir('admins');
    }

    private function render(string $vista, array $datos, string $activo, string $titulo): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        $csrf = Http::csrfToken();
        $url = function (string $r = 'dashboard', array $p = []): string {
            return $this->url($r, $p);
        };
        $base = $this->base;
        $flash = $this->auth->tomarFlash();
        $conteos = $this->repo->contarNoRespondidas();
        $contenido = View::capturar('admin/' . $vista, array_merge($datos, [
            'csrf' => $csrf,
            'base' => $base,
            'url' => $url,
            'activo' => $activo,
            'titulo' => $titulo,
            'usuario' => $this->usuario,
        ]));

        View::render('admin/layout', [
            'contenido' => $contenido,
            'csrf' => $csrf,
            'base' => $base,
            'url' => $url,
            'flash' => $flash,
            'activo' => $activo,
            'titulo' => $titulo,
            'usuario' => $this->usuario,
            'pendientes' => (int) $conteos['pendientes'],
        ]);
    }

    private function mostrarError(int $status, string $mensaje): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
        }
        View::render('admin/error', [
            'base' => $this->base,
            'status' => $status,
            'mensaje' => $mensaje,
            'url' => function (string $r = 'dashboard'): string {
                return $this->url($r);
            },
        ]);
    }

    private function redirigir(string $ruta, array $params = []): void
    {
        Http::redirigir($this->url($ruta, $params));
    }

    private function url(string $ruta, array $params = []): string
    {
        return $this->base . '/index.php?' . http_build_query(
            array_merge(['r' => $ruta], $params),
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    private function detectarBase(): string
    {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($script));
        $dir = rtrim($dir, '/');
        if ($dir === '' || $dir === '.' || $dir === '/') {
            return '/asistente/admin';
        }
        return $dir;
    }
}
