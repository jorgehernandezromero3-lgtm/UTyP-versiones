<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

final class Text
{
    private static ?array $stopwords = null;
    private static ?array $sinonimos = null;
    private static ?array $excepcionesRaiz = null;

    /** @var array<string,string> */
    private const ACENTOS = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n', 'Ç' => 'c',
        '“' => ' ', '”' => ' ', '‘' => ' ', '’' => ' ', '–' => ' ', '—' => ' ',
    ];

    public static function normalizar(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $texto = strtr($texto, self::ACENTOS);
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace('/(\d),(?=\d{3}(\D|$))/', '$1', $texto) ?? $texto;
        $texto = preg_replace('/\bpre\s*-?\s*registro\b/u', 'preregistro', $texto) ?? $texto;
        $texto = preg_replace('/\bpre\s*-?\s*inscripcion\b/u', 'preinscripcion', $texto) ?? $texto;
        $texto = preg_replace('/[^a-z0-9]+/u', ' ', $texto) ?? '';
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

        return trim($texto);
    }

    /**
     * Devuelve las palabras significativas ya normalizadas, con sinónimos y raíz ligera.
     *
     * @return string[]
     */
    public static function tokens(string $texto): array
    {
        $normalizado = self::normalizar($texto);
        if ($normalizado === '') {
            return [];
        }

        $stopwords = self::stopwords();
        $salida = [];
        foreach (explode(' ', $normalizado) as $palabra) {
            if ($palabra === '') {
                continue;
            }
            if (is_numeric($palabra)) {
                $salida[$palabra] = true;
                continue;
            }
            if (isset($stopwords[$palabra]) || mb_strlen($palabra, 'UTF-8') < 2) {
                continue;
            }
            $palabra = self::sinonimo($palabra);
            if (isset($stopwords[$palabra])) {
                continue;
            }
            $salida[self::raiz($palabra)] = true;
        }

        return array_map('strval', array_keys($salida));
    }

    public static function sinonimo(string $palabra): string
    {
        $mapa = self::sinonimos();
        return $mapa[$palabra] ?? $palabra;
    }

    public static function raiz(string $palabra): string
    {
        $len = mb_strlen($palabra, 'UTF-8');
        if ($len <= 3 || isset(self::excepcionesRaiz()[$palabra])) {
            return $palabra;
        }

        if ($len > 5 && mb_substr($palabra, -3, 3, 'UTF-8') === 'ces') {
            return mb_substr($palabra, 0, -3, 'UTF-8') . 'z';
        }
        if ($len > 4 && mb_substr($palabra, -2, 2, 'UTF-8') === 'es') {
            return mb_substr($palabra, 0, -2, 'UTF-8');
        }
        if (mb_substr($palabra, -1, 1, 'UTF-8') === 's') {
            return mb_substr($palabra, 0, -1, 'UTF-8');
        }

        return $palabra;
    }

    public static function parecido(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }
        $la = strlen($a);
        $lb = strlen($b);
        if ($la === 0 || $lb === 0) {
            return 0.0;
        }
        $distancia = levenshtein($a, $b);
        $maximo = max($la, $lb);
        return max(0.0, 1.0 - ($distancia / $maximo));
    }

    /**
     * Tolerancia a errores de dedo: 1 edición en palabras cortas y 2 en largas,
     * exigiendo la misma letra inicial para evitar falsos positivos.
     */
    public static function coincidenciaDifusa(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }
        $la = strlen($a);
        $lb = strlen($b);
        if ($la < 4 || $lb < 4 || abs($la - $lb) > 2) {
            return false;
        }

        $permitidas = min($la, $lb) <= 5 ? 1 : 2;
        $distancia = levenshtein($a, $b);
        if ($distancia > $permitidas) {
            return false;
        }
        if ($distancia === 2 && ($la < 6 || $lb < 6)) {
            return false;
        }
        if ($distancia > 0) {
            if ($a[0] !== $b[0] || self::parecido($a, $b) < 0.8) {
                return false;
            }
        }

        return true;
    }

    public static function trigramas(string $texto): array
    {
        $texto = self::normalizar($texto);
        if ($texto === '') {
            return [];
        }
        $texto = '  ' . $texto . ' ';
        $len = strlen($texto);
        $salida = [];
        for ($i = 0; $i < $len - 2; $i++) {
            $salida[substr($texto, $i, 3)] = true;
        }
        return array_keys($salida);
    }

    public static function similitudTrigramas(string $a, string $b): float
    {
        $ta = self::trigramas($a);
        $tb = self::trigramas($b);
        $total = count($ta) + count($tb);
        if ($total === 0) {
            return 0.0;
        }
        $comunes = count(array_intersect($ta, $tb));
        return (2 * $comunes) / $total;
    }

    public static function fraseContiene(string $pajar, string $aguja): bool
    {
        $pajar = ' ' . self::normalizar($pajar) . ' ';
        $aguja = ' ' . self::normalizar($aguja) . ' ';
        return $aguja !== '  ' && strpos($pajar, $aguja) !== false;
    }

    public static function formatearFecha(string $fecha): string
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return '';
        }

        $meses = self::meses();
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $fecha, $m)) {
            $mes = (int) $m[2];
            $dia = (int) $m[3];
            if (isset($meses[$mes])) {
                return $dia . ' de ' . $meses[$mes] . ' de ' . $m[1];
            }
        }

        if (preg_match('/^(\d{1,2})\s+([A-Za-zÁÉÍÓÚáéíóúñ]{3,})\.?\s+(\d{4})/u', $fecha, $m)) {
            $clave = mb_strtolower(mb_substr($m[2], 0, 3, 'UTF-8'), 'UTF-8');
            $clave = strtr($clave, self::ACENTOS);
            foreach ($meses as $numero => $nombre) {
                if (mb_substr(strtr($nombre, self::ACENTOS), 0, 3, 'UTF-8') === $clave || $numero === self::mesAbrev($clave)) {
                    return (int) $m[1] . ' de ' . $nombre . ' de ' . $m[3];
                }
            }
        }

        return $fecha;
    }

    public static function recortar(string $texto, int $maximo = 160): string
    {
        $texto = trim($texto);
        if (mb_strlen($texto, 'UTF-8') <= $maximo) {
            return $texto;
        }
        return rtrim(mb_substr($texto, 0, $maximo - 1, 'UTF-8')) . '…';
    }

    public static function slug(string $texto, string $defecto = 'elemento'): string
    {
        $texto = self::normalizar($texto);
        $texto = preg_replace('/\s+/', '-', $texto) ?? '';
        $texto = trim($texto, '-');
        $texto = substr($texto, 0, 80);
        return $texto !== '' ? $texto : $defecto;
    }

    private static function mesAbrev(string $clave): int
    {
        $abreviaturas = [
            'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'ago' => 8, 'sep' => 9, 'set' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12,
        ];
        return $abreviaturas[$clave] ?? 0;
    }

    private static function meses(): array
    {
        return [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
            7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
    }

    private static function stopwords(): array
    {
        if (self::$stopwords !== null) {
            return self::$stopwords;
        }

        $lista = [
            'a', 'al', 'algo', 'algun', 'alguna', 'algunas', 'alguno', 'algunos', 'ante', 'antes',
            'aqui', 'asi', 'aun', 'bien', 'cada', 'con', 'contra', 'de', 'del', 'desde',
            'el', 'ella', 'ellas', 'ello', 'ellos', 'en', 'entre', 'era', 'es', 'esa', 'esas',
            'ese', 'eso', 'esos', 'esta', 'estan', 'estar', 'estas', 'este', 'esto', 'estos',
            'fue', 'ha', 'hace', 'hasta', 'hay', 'la', 'las', 'le', 'les', 'lo', 'los',
            'mas', 'me', 'mi', 'mis', 'mucho', 'muy', 'nada', 'ni', 'no', 'nos', 'nosotros',
            'o', 'os', 'otra', 'otras', 'otro', 'otros', 'para', 'pero', 'poco', 'por', 'porque',
            'se', 'sea', 'segun', 'ser', 'si', 'sin', 'sobre', 'son', 'su', 'sus', 'tambien',
            'tan', 'te', 'toda', 'todas', 'todo', 'todos', 'tu', 'tus', 'un', 'una', 'unas',
            'uno', 'unos', 'usted', 'ustedes', 'y', 'ya', 'yo',
            'que', 'como', 'cual', 'cuales', 'quien', 'quienes',
            'puedo', 'puede', 'pueden', 'podemos', 'debo', 'debe', 'deben', 'deberia',
            'dan', 'da', 'voy', 'vas', 'van', 'ir', 'ire', 'iria', 'tengo', 'tienes', 'tenemos',
            'dentro', 'ahi', 'aca', 'alli', 'alla', 'hacer', 'hago', 'haces', 'hacen',
            'gusta', 'gustan', 'gustaria', 'forma', 'despues',
            'interesa', 'interesan', 'interesado', 'interesada', 'interesados', 'interesadas',
            'hola', 'buenas', 'buen', 'buenos', 'dias', 'tardes', 'noches', 'saludos', 'favor',
            'porfa', 'porfavor', 'gracias', 'disculpa', 'disculpe', 'oye', 'oiga', 'necesito',
            'quiero', 'quisiera', 'queria', 'saber', 'decir', 'dime', 'puedes', 'podrias',
            'ayudame', 'ayuda', 'ayudar', 'informacion', 'informes', 'informe', 'info',
            'respecto', 'acerca', 'relacionado', 'relacionada', 'tema', 'duda', 'dudas',
            'proporciona', 'proporcionan', 'brinda', 'brindan',
            'ofrece', 'ofrecen', 'tiene', 'tienen', 'existe', 'existen', 'seria', 'podria',
            'favor', 'amablemente', 'breve', 'explicame', 'explica', 'menciona', 'mencioname',
            'sabes', 'conoces', 'dame', 'mostrar', 'muestra', 'ponme',
        ];

        self::$stopwords = array_fill_keys($lista, true);
        return self::$stopwords;
    }

    private static function sinonimos(): array
    {
        if (self::$sinonimos !== null) {
            return self::$sinonimos;
        }

        $mapa = [
            // Dinero y pagos
            'cuesta' => 'costo', 'cuestan' => 'costo', 'costar' => 'costo', 'costaria' => 'costo',
            'coste' => 'costo', 'costes' => 'costo', 'precio' => 'costo', 'precios' => 'costo',
            'arancel' => 'costo', 'aranceles' => 'costo', 'cuota' => 'costo', 'cuotas' => 'costo',
            'colegiatura' => 'costo', 'colegiaturas' => 'costo', 'mensualidad' => 'costo',
            'mensualidades' => 'costo', 'inversion' => 'costo', 'vale' => 'costo', 'valen' => 'costo',
            'sale' => 'costo', 'salen' => 'costo', 'cobra' => 'costo', 'cobran' => 'costo',
            'cobro' => 'costo', 'cobros' => 'costo', 'tarifa' => 'costo', 'tarifas' => 'costo',
            'pagar' => 'pago', 'paga' => 'pago', 'pagan' => 'pago', 'pagare' => 'pago', 'pagando' => 'pago',
            'dinero' => 'pago', 'lana' => 'pago', 'efectivo' => 'pago',
            'descuento' => 'beca', 'descuentos' => 'beca',
            'cubre' => 'incluir', 'cubren' => 'incluir', 'incluye' => 'incluir', 'incluyen' => 'incluir',
            'incluido' => 'incluir', 'incluida' => 'incluir',

            // Carreras y estudios
            'carreras' => 'carrera', 'licenciatura' => 'carrera', 'licenciaturas' => 'carrera',
            'profesion' => 'carrera', 'profesiones' => 'carrera', 'oferta' => 'carrera',
            'cortas' => 'tsu', 'corta' => 'tsu', 'tecnico' => 'tsu', 'tecnica' => 'tsu',
            'semestre' => 'cuatrimestre', 'semestres' => 'cuatrimestre',
            'cuatrimestres' => 'cuatrimestre', 'materia' => 'materia', 'materias' => 'materia',
            'asignatura' => 'materia', 'asignaturas' => 'materia',
            'reticula' => 'plan', 'malla' => 'plan', 'curricular' => 'plan', 'curriculo' => 'plan',
            'dura' => 'duracion', 'duran' => 'duracion', 'duracion' => 'duracion',
            'larga' => 'duracion', 'largas' => 'duracion', 'tarda' => 'duracion', 'tardan' => 'duracion',
            'demora' => 'duracion', 'demoran' => 'duracion', 'anos' => 'duracion',
            'estudiar' => 'estudio', 'estudia' => 'estudio', 'estudian' => 'estudio', 'estudias' => 'estudio',
            'presenta' => 'presentacion', 'presentan' => 'presentacion', 'presentar' => 'presentacion',
            'presentacion' => 'presentacion', 'presentaciones' => 'presentacion',
            'expongo' => 'presentacion', 'exponen' => 'presentacion', 'exponer' => 'presentacion',
            'exposicion' => 'presentacion',

            // Admisión
            'inscribirme' => 'inscripcion', 'inscribir' => 'inscripcion', 'inscribe' => 'inscripcion',
            'inscriben' => 'inscripcion', 'inscripciones' => 'inscripcion',
            'registrarme' => 'preregistro', 'registrar' => 'preregistro', 'registro' => 'preregistro',
            'registros' => 'preregistro', 'preregistro' => 'preregistro', 'preinscripcion' => 'preregistro',
            'preinscribirme' => 'preregistro', 'preinscribir' => 'preregistro',
            'examenes' => 'examen', 'evaluaciones' => 'evaluacion', 'prueba' => 'evaluacion', 'pruebas' => 'evaluacion',
            'psicometrica' => 'psicometrico', 'psicometricas' => 'psicometrico',
            'psicometricos' => 'psicometrico', 'exani' => 'examen',
            'sicometrico' => 'psicometrico', 'sicometrica' => 'psicometrico',
            'sicometricos' => 'psicometrico', 'sicometricas' => 'psicometrico',
            'seguimiento' => 'psicometrico',
            'solicitar' => 'solicitud', 'solicito' => 'solicitud', 'solicita' => 'solicitud',
            'solicitan' => 'solicitud', 'solicitudes' => 'solicitud', 'solicitud' => 'solicitud',
            'pedir' => 'solicitud', 'pido' => 'solicitud', 'pide' => 'solicitud', 'piden' => 'solicitud',
            'pedirla' => 'solicitud', 'pedirle' => 'solicitud', 'pidiendo' => 'solicitud',
            'entregar' => 'entrega', 'entregan' => 'entrega', 'entregas' => 'entrega',
            'llevar' => 'entrega', 'llevo' => 'entrega', 'llevan' => 'entrega',
            'llevarla' => 'entrega', 'llevarlos' => 'entrega', 'llevarlas' => 'entrega',
            'ingresar' => 'ingreso', 'ingreso' => 'ingreso', 'ingresos' => 'ingreso',
            'suben' => 'actualizacion', 'sube' => 'actualizacion', 'subir' => 'actualizacion',
            'subieron' => 'actualizacion', 'actualiza' => 'actualizacion',
            'actualizan' => 'actualizacion', 'actualizar' => 'actualizacion',
            'actualizado' => 'actualizacion', 'actualizada' => 'actualizacion',
            'cambiar' => 'cambio', 'cambia' => 'cambio', 'cambiarme' => 'cambio', 'cambiamos' => 'cambio',
            'pasarme' => 'cambio', 'pasarse' => 'cambio',
            'reinscribirse' => 'reinscripcion', 'reinscribirme' => 'reinscripcion',
            'reinscribir' => 'reinscripcion', 'reinscribe' => 'reinscripcion',
            'reinscriben' => 'reinscripcion', 'reinscripciones' => 'reinscripcion',
            'recuperar' => 'extraordinario', 'recupero' => 'extraordinario',
            'recupera' => 'extraordinario', 'recuperacion' => 'extraordinario',
            'revalidar' => 'revalidacion', 'revalido' => 'revalidacion',
            'equivalencia' => 'revalidacion', 'equivalencias' => 'revalidacion',

            // Trámites y documentos
            'documentos' => 'documento', 'papeles' => 'documento', 'papeleria' => 'documento',
            'requisitos' => 'requisito', 'tramites' => 'tramite', 'constancias' => 'constancia',
            'titulacion' => 'titulacion', 'titulo' => 'titulacion', 'titularme' => 'titulacion',
            'titular' => 'titulacion',

            // Contacto y ubicación
            'telefonos' => 'telefono', 'tel' => 'telefono', 'celular' => 'telefono',
            'whatsapp' => 'telefono', 'whats' => 'telefono', 'marcar' => 'telefono',
            'correos' => 'correo', 'email' => 'correo', 'emails' => 'correo', 'mail' => 'correo',
            'direccion' => 'ubicacion', 'domicilio' => 'ubicacion', 'ubicada' => 'ubicacion',
            'ubicado' => 'ubicacion', 'ubica' => 'ubicacion', 'llegar' => 'ubicacion',
            'queda' => 'ubicacion', 'quedan' => 'ubicacion', 'mapa' => 'ubicacion', 'maps' => 'ubicacion',
            'comunicarme' => 'contacto', 'contactar' => 'contacto', 'contactos' => 'contacto',
            'campus' => 'utypcb',

            // Fechas, trámites y vida universitaria
            'cronograma' => 'calendario', 'fechas' => 'fecha', 'horarios' => 'horario',
            'noticias' => 'noticia', 'nota' => 'noticia', 'notas' => 'calificacion',
            'boleta' => 'calificacion', 'boletas' => 'calificacion',
            'comunicado' => 'noticia', 'comunicados' => 'noticia', 'aviso' => 'noticia', 'avisos' => 'noticia',
            'deportes' => 'deporte', 'deportiva' => 'deporte', 'deportivas' => 'deporte',
            'deportivo' => 'deporte', 'deportivos' => 'deporte', 'torneos' => 'torneo',
            'selecciones' => 'seleccion', 'equipos' => 'seleccion',
            'estadias' => 'estadia', 'estancia' => 'estadia', 'estancias' => 'estadia',
            'practicas' => 'practica', 'practica' => 'practica',
            'trabajar' => 'trabajo', 'trabajos' => 'trabajo', 'laboral' => 'trabajo',
            'laborales' => 'trabajo', 'empleo' => 'trabajo', 'empleos' => 'trabajo',
            'egresados' => 'egresado', 'egresada' => 'egresado', 'egresadas' => 'egresado',
            'becas' => 'beca', 'convocatorias' => 'convocatoria', 'convocan' => 'convocatoria',
            'paginas' => 'sitio', 'pagina' => 'sitio', 'web' => 'sitio', 'sitios' => 'sitio',
            'universidad' => 'utypcb', 'universidades' => 'utypcb', 'uni' => 'utypcb',
            'utyp' => 'utypcb', 'utypcb' => 'utypcb',
            'servicios' => 'servicio', 'cursos' => 'curso',
            'camion' => 'transporte', 'camiones' => 'transporte', 'autobus' => 'transporte',
            'autobuses' => 'transporte', 'ruta' => 'transporte', 'rutas' => 'transporte',
            'venden' => 'comprar', 'vende' => 'comprar', 'vender' => 'comprar',
            'vendes' => 'comprar', 'vendo' => 'comprar',
            'material' => 'guia', 'materiales' => 'guia',
            'prepararme' => 'preparar', 'preparo' => 'preparar', 'prepara' => 'preparar',
            'preparar' => 'preparar', 'preparacion' => 'preparar',
            'veo' => 'consultar', 'ver' => 'consultar', 'ves' => 'consultar',
            'descanso' => 'vacaciones', 'descansan' => 'vacaciones',
            'descansamos' => 'vacaciones', 'descansar' => 'vacaciones',
            'ingles' => 'ingles', 'interes' => 'interes', 'artes' => 'artes',
        ];

        self::$sinonimos = $mapa;
        return self::$sinonimos;
    }

    private static function excepcionesRaiz(): array
    {
        if (self::$excepcionesRaiz !== null) {
            return self::$excepcionesRaiz;
        }

        $lista = [
            'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo',
            'ingles', 'interes', 'artes', 'pues', 'despues', 'adios', 'gratis', 'mes',
            'res', 'ces', 'sos', 'tos', 'gas', 'paz', 'luz', 'voz', 'vez',
        ];

        self::$excepcionesRaiz = array_fill_keys($lista, true);
        return self::$excepcionesRaiz;
    }
}
