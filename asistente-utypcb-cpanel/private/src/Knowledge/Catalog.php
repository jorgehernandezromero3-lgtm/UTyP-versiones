<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Knowledge;

use Utypcb\Asistente\Support\Text;

final class Catalog
{
    public static function programas(): array
    {
        return [
            'ingenieria-civil' => [
                'nombre' => 'Ingeniería Civil',
                'tsu' => 'Técnico Superior Universitario en Construcción',
                'alias' => [
                    'ingenieria civil', 'construccion e ingenieria civil', 'tsu en construccion',
                    'construccion', 'civil',
                ],
            ],
            'comercio-internacional-aduanas' => [
                'nombre' => 'Licenciatura en Comercio Internacional y Aduanas',
                'tsu' => 'Técnico Superior Universitario en Procesos Logísticos',
                'alias' => [
                    'comercio internacional y aduanas', 'comercio internacional', 'clasificacion arancelaria',
                    'procesos logisticos', 'comercio exterior', 'aduanas', 'comercio', 'logistica',
                ],
            ],
            'energia-desarrollo-sostenible' => [
                'nombre' => 'Ingeniería en Energía y Desarrollo Sostenible',
                'tsu' => 'Técnico Superior Universitario en Energía Solar',
                'alias' => [
                    'energia y desarrollo sostenible', 'energia solar', 'energias renovables',
                    'desarrollo sostenible', 'energia',
                ],
            ],
            'iti-innovacion-digital' => [
                'nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
                'tsu' => 'Técnico Superior Universitario en Entornos Virtuales y Negocios Digitales',
                'alias' => [
                    'tecnologias de la informacion e innovacion digital', 'innovacion digital',
                    'entornos virtuales y negocios digitales', 'tecnologias de la informacion',
                    'entornos virtuales', 'negocios digitales', 'informatica', 'sistemas', 'iti', 'tic',
                ],
            ],
            'manejo-recursos-naturales' => [
                'nombre' => 'Licenciatura en Ingeniería en Manejo de Recursos Naturales',
                'tsu' => 'Técnico Superior Universitario en Manejo de Fauna Silvestre',
                'alias' => [
                    'manejo de recursos naturales', 'recursos naturales', 'manejo de fauna silvestre',
                    'fauna silvestre', 'medio ambiente',
                ],
            ],
        ];
    }

    public static function detectarPrograma(string $textoNormalizado): ?string
    {
        $mejor = null;
        $mejorLargo = 0;

        foreach (self::programas() as $slug => $programa) {
            foreach ($programa['alias'] as $alias) {
                $aliasNorm = Text::normalizar($alias);
                if ($aliasNorm === '' || !Text::fraseContiene($textoNormalizado, $aliasNorm)) {
                    continue;
                }
                $largo = substr_count($aliasNorm, ' ') + 1;
                if ($largo > $mejorLargo) {
                    $mejor = $slug;
                    $mejorLargo = $largo;
                }
            }
        }

        return $mejor;
    }

    public static function nombrePrograma(?string $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }
        $programas = self::programas();
        return $programas[$slug]['nombre'] ?? null;
    }

    public static function temas(): array
    {
        return [
            'oferta' => [
                'nombre' => 'Oferta educativa',
                'palabras' => ['carrera', 'oferta', 'ingenieria', 'tsu'],
            ],
            'admision' => [
                'nombre' => 'Admisión',
                'palabras' => ['admision', 'examen', 'psicometrico', 'preregistro', 'puntaje', 'resultado', 'aceptado', 'ficha'],
            ],
            'costos' => [
                'nombre' => 'Costos',
                'palabras' => ['costo', 'pago', 'arancel', 'colegiatura', 'inscripcion'],
            ],
            'calendario' => [
                'nombre' => 'Calendario escolar',
                'palabras' => ['calendario', 'fecha', 'clases', 'periodo', 'vacaciones', 'inicio', 'reinscripcion'],
            ],
            'becas' => [
                'nombre' => 'Becas',
                'palabras' => ['beca', 'descuento'],
            ],
            'plan-estudios' => [
                'nombre' => 'Plan de estudios',
                'palabras' => ['plan', 'materia', 'cuatrimestre', 'mapa', 'curricular', 'duracion', 'nivel', 'estudiar'],
            ],
            'campo-laboral' => [
                'nombre' => 'Campo laboral',
                'palabras' => ['trabajo', 'laboral', 'egresado', 'empresa', 'campo'],
            ],
            'estadias' => [
                'nombre' => 'Estadías y prácticas',
                'palabras' => ['estadia', 'practica'],
            ],
            'tramites' => [
                'nombre' => 'Trámites escolares',
                'palabras' => ['tramite', 'constancia', 'kardex', 'credencial', 'documento', 'certificado', 'baja'],
            ],
            'titulacion' => [
                'nombre' => 'Titulación',
                'palabras' => ['titulacion'],
            ],
            'servicios' => [
                'nombre' => 'Servicios universitarios',
                'palabras' => ['biblioteca', 'cafeteria', 'transporte', 'tutoria', 'asesoria', 'laboratorio', 'seguro', 'enfermeria', 'psicologia', 'comedor', 'servicio'],
            ],
            'deportes' => [
                'nombre' => 'Deportes',
                'palabras' => ['deporte', 'futbol', 'basquetbol', 'torneo', 'seleccion', 'entrenamiento', 'atletismo', 'voleibol'],
            ],
            'noticias' => [
                'nombre' => 'Noticias',
                'palabras' => ['noticia', 'comunicado', 'evento', 'foro', 'visita', 'concurso'],
            ],
            'transparencia' => [
                'nombre' => 'Transparencia',
                'palabras' => ['transparencia', 'presupuesto', 'auditoria', 'organigrama', 'directorio', 'licitacion', 'cuenta'],
            ],
            'contacto' => [
                'nombre' => 'Contacto',
                'palabras' => ['telefono', 'correo', 'ubicacion', 'contacto', 'facebook', 'instagram', 'whatsapp', 'redes', 'horario'],
            ],
            'institucional' => [
                'nombre' => 'La universidad',
                'palabras' => ['historia', 'mision', 'vision', 'valores', 'coyote', 'rector', 'fundacion', 'aniversario'],
            ],
            'ingles' => [
                'nombre' => 'Inglés',
                'palabras' => ['ingles', 'idioma', 'mcer'],
            ],
        ];
    }

    public static function detectarTema(string $textoNormalizado, array $tokens): ?string
    {
        $mejor = null;
        $mejorPuntaje = 0;

        foreach (self::temas() as $slug => $tema) {
            $puntaje = 0;
            foreach ($tema['palabras'] as $palabra) {
                if (in_array($palabra, $tokens, true)) {
                    $puntaje += 2;
                } elseif (Text::fraseContiene($textoNormalizado, $palabra)) {
                    $puntaje += 1;
                }
            }
            if ($puntaje > $mejorPuntaje) {
                $mejor = $slug;
                $mejorPuntaje = $puntaje;
            }
        }

        return $mejor;
    }

    public static function nombreTema(?string $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }
        $temas = self::temas();
        return $temas[$slug]['nombre'] ?? null;
    }

    public static function categorias(): array
    {
        return [
            'institucional' => 'La universidad',
            'carreras' => 'Carreras y planes de estudio',
            'admision' => 'Admisión y nuevo ingreso',
            'costos' => 'Costos y pagos',
            'calendario' => 'Calendario escolar',
            'tramites' => 'Trámites y servicios escolares',
            'becas' => 'Becas y apoyos',
            'servicios' => 'Servicios universitarios',
            'deportes' => 'Deportes',
            'noticias' => 'Noticias y eventos',
            'transparencia' => 'Transparencia',
            'contacto' => 'Contacto y ubicación',
        ];
    }

    public static function urlSegura(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $url = trim($url);
        if ($url === '' || strlen($url) > 500 || preg_match('/[\s<>"\']/', $url)) {
            return null;
        }
        $partes = parse_url($url);
        if (!is_array($partes) || !isset($partes['scheme'], $partes['host'])) {
            return null;
        }
        if (!in_array(strtolower($partes['scheme']), ['http', 'https'], true)) {
            return null;
        }
        if (isset($partes['user']) || isset($partes['pass'])) {
            return null;
        }
        return $url;
    }

    public static function correoSeguro(?string $correo): ?string
    {
        if ($correo === null) {
            return null;
        }
        $correo = trim($correo);
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false ? $correo : null;
    }
}
