<?php
declare(strict_types=1);

/*
 * Copia este archivo como private/config.php y cambia todos los valores.
 * En producción, private/ debe quedar fuera de public_html.
 */
return [
    'app' => [
        'nombre' => 'Asistente Coyote',
        'version' => '1.0.0',
        'zona_horaria' => 'America/Mexico_City',
        'entorno' => 'produccion',
        'debug' => false,
    ],

    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=utypcb_asistente;charset=utf8mb4',
        'usuario' => 'utypcb_asistente',
        'password' => 'CAMBIA_ESTA_CONTRASENA',
    ],

    'seguridad' => [
        // Cadena aleatoria de al menos 48 caracteres. No la compartas ni la subas al repositorio.
        'ip_salt' => 'CAMBIA_ESTE_VALOR_POR_UNA_CADENA_ALEATORIA_DE_48_O_MAS_CARACTERES',
        'chat_session_name' => 'UTYPCHAT',
        'admin_session_name' => 'UTYPADM',
        'chat_session_path' => '/',
        'admin_session_path' => '/asistente/admin',
        'admin_idle_minutos' => 30,
        'limites' => [
            'chat_sesion' => ['limite' => 20, 'ventana' => 300],
            'chat_ip' => ['limite' => 120, 'ventana' => 600],
            'admin_login' => ['limite' => 5, 'ventana' => 900],
        ],
    ],

    'privacidad' => [
        'registrar_no_respondidas' => true,
        'retencion_no_respondidas_dias' => 90,
        'retencion_eventos_dias' => 365,
        'aviso_url' => '/asistente/privacidad.php',
        'responsable' => 'Departamento de Servicios Escolares de la UTyPCB',
    ],

    'sitio' => [
        'url' => 'https://utypcb.edu.mx',
        // Ruta absoluta a la carpeta donde viven calendario_data.php, noticias_data.php, etc.
        'directorio_datos' => '/home/USUARIO/public_html',
        'archivos' => [
            'calendario' => 'calendario_data.php',
            'noticias' => 'noticias_data.php',
            'carreras' => 'carreras_data.php',
            'deportes' => 'deportes_data.php',
            'tripticos' => 'tripticos_data.php',
        ],
        'variables' => [
            'calendario' => 'CAL',
            'noticias' => 'NOTICIAS',
            'carreras' => 'CARRERAS',
            'deportes' => 'DEPORTES',
            'tripticos' => 'TRIPTICOS',
        ],
    ],

    'motor' => [
        'min_mensaje' => 2,
        'max_mensaje' => 300,
        'umbral' => 0.40,
        'margen' => 0.02,
        'max_sugerencias' => 4,
        'max_contexto' => 6,
        'contexto_ttl_minutos' => 30,
    ],
];
