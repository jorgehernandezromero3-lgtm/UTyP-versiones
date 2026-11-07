<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Knowledge;

use Utypcb\Asistente\Support\Config;

final class Responder
{
    private Repository $repo;

    public function __construct(?Repository $repo = null)
    {
        $this->repo = $repo instanceof Repository ? $repo : new Repository();
    }

    public function construir(array $resultado): array
    {
        $tipo = (string) ($resultado['tipo'] ?? 'sin_respuesta');
        $contexto = $this->contextoSalida($resultado);
        $sugerencias = $this->sugerenciasSalida($resultado['sugerencias'] ?? []);

        if ($tipo === 'intencion' && isset($resultado['intent']) && is_array($resultado['intent'])) {
            $intent = $resultado['intent'];
            $fuente = $this->fuente($intent);
            return [
                'ok' => true,
                'tipo' => 'respuesta',
                'mensaje' => $intent['respuesta'],
                'fuente' => $fuente,
                'enlaces' => $this->enlaces($intent['enlaces'] ?? [], $fuente['url'] ?? null),
                'contacto' => null,
                'sugerencias' => $sugerencias,
                'confianza' => (float) ($resultado['confianza'] ?? 0.0),
                'intent' => $intent['slug'],
                'categoria' => Catalog::categorias()[$intent['categoria']] ?? $intent['categoria'],
                'contexto' => $contexto,
            ];
        }

        if ($tipo === 'ambigua') {
            return [
                'ok' => true,
                'tipo' => 'ambigua',
                'mensaje' => 'Encontré varias respuestas posibles. Para darte el dato correcto, ¿cuál de estas preguntas se parece más a lo que buscas?',
                'fuente' => null,
                'enlaces' => [],
                'contacto' => null,
                'sugerencias' => $sugerencias,
                'confianza' => (float) ($resultado['confianza'] ?? 0.0),
                'intent' => null,
                'categoria' => null,
                'contexto' => $contexto,
            ];
        }

        if ($tipo === 'saludo') {
            return $this->conversacional(
                '¡Hola! Soy el Asistente Coyote de la UTyPCB. Puedo ayudarte con admisión, carreras, costos, calendario, trámites, becas y más. ¿Qué te gustaría saber?',
                $sugerencias,
                $contexto
            );
        }

        if ($tipo === 'gracias') {
            return $this->conversacional(
                '¡Con gusto! Si tienes otra duda sobre la UTyPCB, aquí estoy.',
                $sugerencias,
                $contexto
            );
        }

        if ($tipo === 'despedida') {
            return $this->conversacional(
                '¡Hasta pronto! Éxitos en tu camino. La UTyPCB te espera.',
                $sugerencias,
                $contexto
            );
        }

        if ($tipo === 'identidad') {
            $respuesta = $this->conversacional(
                'Soy el Asistente Coyote, un apoyo virtual de la Universidad Tecnológica y Politécnica de Coyuca de Benítez. Respondo con información publicada en el sitio oficial; cuando no tengo un dato verificado, te canalizo con el área correspondiente.',
                $sugerencias,
                $contexto
            );
            $respuesta['fuente'] = $this->fuenteSitio();
            return $respuesta;
        }

        if ($tipo === 'capacidades') {
            return $this->conversacional(
                "Puedo responderte con información oficial sobre:\n" .
                "• Oferta educativa y planes de estudio\n" .
                "• Admisión, pre-registro y examen\n" .
                "• Costos de ficha e inscripción\n" .
                "• Calendario escolar\n" .
                "• Becas, trámites y servicios\n" .
                "• Deportes, noticias y transparencia\n\n" .
                'Escribe tu pregunta o toca una sugerencia.',
                $sugerencias,
                $contexto
            );
        }

        if ($tipo === 'aclaracion') {
            return $this->conversacional(
                'No estoy seguro de haber entendido. ¿Puedes escribirme tu pregunta con otras palabras? Por ejemplo: «¿Cuánto cuesta la ficha?» o «¿Cuándo es el examen de admisión?».',
                $sugerencias,
                $contexto
            );
        }

        return $this->sinRespuesta($resultado, $contexto, $sugerencias);
    }

    private function sinRespuesta(array $resultado, array $contexto, array $sugerencias): array
    {
        $tema = $resultado['tema'] ?? ($resultado['contexto']['tema'] ?? null);
        $area = $this->repo->areaParaTema(is_string($tema) ? $tema : null);
        $contacto = $this->contacto($area);

        $mensaje = 'No tengo una respuesta verificada para esa pregunta y prefiero no inventar información.';
        if ($contacto !== null && $contacto['area'] !== '') {
            $mensaje .= "\n\nPuedes consultarla directamente con " . $contacto['area'] . ':';
            if ($contacto['correo'] !== null) {
                $mensaje .= "\n• Correo: " . $contacto['correo'];
            }
            if ($contacto['telefono'] !== null && $contacto['telefono'] !== '') {
                $mensaje .= "\n• Teléfono: " . $contacto['telefono'];
            }
            if ($contacto['url'] !== null) {
                $mensaje .= "\n• Más información: " . $contacto['url'];
            }
        } else {
            $mensaje .= "\n\nPuedes acudir a Servicios Escolares de la UTyPCB o revisar el sitio oficial.";
        }

        return [
            'ok' => true,
            'tipo' => 'sin_respuesta',
            'mensaje' => $mensaje,
            'fuente' => $this->fuenteSitio(),
            'enlaces' => $contacto !== null && $contacto['url'] !== null
                ? [['titulo' => 'Contacto · ' . $contacto['area'], 'url' => $contacto['url']]]
                : [],
            'contacto' => $contacto,
            'sugerencias' => $sugerencias,
            'confianza' => 0.0,
            'intent' => null,
            'categoria' => null,
            'contexto' => $contexto,
        ];
    }

    private function conversacional(string $mensaje, array $sugerencias, array $contexto): array
    {
        return [
            'ok' => true,
            'tipo' => 'respuesta',
            'mensaje' => $mensaje,
            'fuente' => null,
            'enlaces' => [],
            'contacto' => null,
            'sugerencias' => $sugerencias,
            'confianza' => 0.0,
            'intent' => null,
            'categoria' => null,
            'contexto' => $contexto,
        ];
    }

    private function fuente(array $intent): ?array
    {
        $url = Catalog::urlSegura($intent['fuente_url'] ?? null);
        if ($url === null) {
            return null;
        }
        return [
            'titulo' => trim((string) ($intent['fuente_titulo'] ?? '')) !== ''
                ? (string) $intent['fuente_titulo']
                : 'Sitio oficial de la UTyPCB',
            'url' => $url,
        ];
    }

    private function fuenteSitio(): ?array
    {
        $base = rtrim((string) Config::get('sitio.url', ''), '/');
        $url = Catalog::urlSegura($base !== '' ? $base . '/home.php' : null);
        if ($url === null) {
            return null;
        }
        return ['titulo' => 'Sitio oficial de la UTyPCB', 'url' => $url];
    }

    private function enlaces($enlaces, ?string $urlFuente): array
    {
        if (!is_array($enlaces)) {
            return [];
        }

        $salida = [];
        $vistos = [];
        if ($urlFuente !== null) {
            $vistos[$urlFuente] = true;
        }

        foreach ($enlaces as $enlace) {
            if (count($salida) >= 5) {
                break;
            }
            if (!is_array($enlace)) {
                continue;
            }
            $url = Catalog::urlSegura(isset($enlace['url']) ? (string) $enlace['url'] : null);
            if ($url === null || isset($vistos[$url])) {
                continue;
            }
            $titulo = trim((string) ($enlace['titulo'] ?? ''));
            if ($titulo === '') {
                $titulo = 'Más información';
            }
            $vistos[$url] = true;
            $salida[] = ['titulo' => $titulo, 'url' => $url];
        }

        return $salida;
    }

    private function contacto(?array $area): ?array
    {
        if ($area === null) {
            return null;
        }
        return [
            'area' => (string) ($area['nombre'] ?? ''),
            'correo' => Catalog::correoSeguro($area['correo'] ?? null),
            'telefono' => $area['telefono'] !== null && $area['telefono'] !== '' ? (string) $area['telefono'] : null,
            'url' => Catalog::urlSegura($area['url_contacto'] ?? null),
        ];
    }

    private function contextoSalida(array $resultado): array
    {
        $programa = $resultado['contexto']['programa'] ?? ($resultado['programa'] ?? null);
        $tema = $resultado['contexto']['tema'] ?? ($resultado['tema'] ?? null);

        return [
            'programa' => is_string($programa) ? Catalog::nombrePrograma($programa) : null,
            'tema' => is_string($tema) ? Catalog::nombreTema($tema) : null,
        ];
    }

    private function sugerenciasSalida(array $sugerencias): array
    {
        $salida = [];
        foreach ($sugerencias as $sugerencia) {
            if (!is_array($sugerencia) || !isset($sugerencia['pregunta'])) {
                continue;
            }
            $salida[] = [
                'slug' => (string) ($sugerencia['slug'] ?? ''),
                'pregunta' => (string) $sugerencia['pregunta'],
            ];
        }
        return $salida;
    }
}
