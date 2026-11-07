<?php
declare(strict_types=1);

/**
 * Conjunto de evaluación con 100 preguntas no incluidas en la semilla.
 * Cada entrada declara la intención esperada. Las entradas con «antes»
 * prueban el manejo de contexto conversacional.
 */
return [
    // Admisión (15)
    ['categoria' => 'admision', 'q' => 'me interesa entrar a la universidad, cuales son los pasos', 'slug' => 'admision-proceso'],
    ['categoria' => 'admision', 'q' => 'donde esta la plataforma para registrarme', 'slug' => 'admision-preregistro-donde'],
    ['categoria' => 'admision', 'q' => 'que datos me solicitan al registrarme', 'slug' => 'admision-preregistro-datos'],
    ['categoria' => 'admision', 'q' => 'tambien me piden la curp ahi', 'slug' => 'admision-curp', 'antes' => ['donde esta la plataforma para registrarme']],
    ['categoria' => 'admision', 'q' => 'la prueba que se hace en seguimiento que es', 'slug' => 'admision-psicometrico'],
    ['categoria' => 'admision', 'q' => 'cuanto tiempo dura la prueba sicometrica', 'slug' => 'admision-psicometrico-duracion'],
    ['categoria' => 'admision', 'q' => 'si no tengo internet, me pueden ayudar a hacer el examen', 'slug' => 'admision-sin-computadora'],
    ['categoria' => 'admision', 'q' => 'en que fecha salen los resultados de la convocatoria', 'slug' => 'admision-resultados'],
    ['categoria' => 'admision', 'q' => 'donde checo mi puntaje', 'slug' => 'admision-puntaje'],
    ['categoria' => 'admision', 'q' => 'si me fue mal, puedo volver a intentar', 'slug' => 'admision-no-aceptado'],
    ['categoria' => 'admision', 'q' => 'que papeles debo llevar si ya quede', 'slug' => 'admision-documentos-aceptado'],
    ['categoria' => 'admision', 'q' => 'en que fechas es la entrega de documentos en servicios escolares', 'slug' => 'admision-entrega-documentos'],
    ['categoria' => 'admision', 'q' => 'cuando son las aplicaciones del examen', 'slug' => 'admision-fechas-examen'],
    ['categoria' => 'admision', 'q' => 'puedo cambiar mi examen para otro sabado', 'slug' => 'admision-elegir-fecha'],
    ['categoria' => 'admision', 'q' => 'hay material de estudio para prepararme', 'slug' => 'admision-guia-estudios'],

    // Carreras (16)
    ['categoria' => 'carreras', 'q' => 'que licenciaturas hay en la utypcb', 'slug' => 'carreras-oferta'],
    ['categoria' => 'carreras', 'q' => 'cuales son las carreras cortas', 'slug' => 'carreras-tsu-disponibles'],
    ['categoria' => 'carreras', 'q' => 'que es el tsu y en que se diferencia de una licenciatura', 'slug' => 'carreras-tsu-vs-licenciatura'],
    ['categoria' => 'carreras', 'q' => 'si no me gusta mi carrera, hay forma de pasarme a otra', 'slug' => 'carreras-cambio'],
    ['categoria' => 'carreras', 'q' => 'me explicas la carrera de ingenieria civil', 'slug' => 'civil-que-es'],
    ['categoria' => 'carreras', 'q' => 'y cuanto tiempo dura', 'slug' => 'civil-duracion', 'antes' => ['me explicas la carrera de ingenieria civil']],
    ['categoria' => 'carreras', 'q' => 'que materias se ven en construccion', 'slug' => 'civil-materias'],
    ['categoria' => 'carreras', 'q' => 'en que consiste la licenciatura de aduanas', 'slug' => 'comercio-que-es'],
    ['categoria' => 'carreras', 'q' => 'me interesa aduanas, cuanto tiempo es', 'slug' => 'comercio-duracion'],
    ['categoria' => 'carreras', 'q' => 'donde trabaja alguien de comercio internacional', 'slug' => 'comercio-campo-laboral'],
    ['categoria' => 'carreras', 'q' => 'que tan larga es la ingenieria en energia', 'slug' => 'energia-duracion'],
    ['categoria' => 'carreras', 'q' => 'que perfil tiene el egresado de energia solar', 'slug' => 'energia-perfil'],
    ['categoria' => 'carreras', 'q' => 'que asignaturas lleva innovacion digital', 'slug' => 'iti-materias'],
    ['categoria' => 'carreras', 'q' => 'de que puede trabajar alguien de tecnologias de la informacion', 'slug' => 'iti-campo-laboral'],
    ['categoria' => 'carreras', 'q' => 'me interesa recursos naturales, de que trata', 'slug' => 'naturales-que-es'],
    ['categoria' => 'carreras', 'q' => 'donde puede trabajar un egresado de fauna silvestre', 'slug' => 'naturales-campo-laboral'],

    // Costos (10)
    ['categoria' => 'costos', 'q' => 'cuanto hay que pagar por la ficha', 'slug' => 'costos-ficha'],
    ['categoria' => 'costos', 'q' => 'el precio de la ficha ya cubre el examen psicometrico', 'slug' => 'costos-ficha-incluye'],
    ['categoria' => 'costos', 'q' => 'y despues de aceptado cuanto se paga', 'slug' => 'costos-inscripcion', 'antes' => ['cuanto hay que pagar por la ficha']],
    ['categoria' => 'costos', 'q' => 'en total cuanto sale el nuevo ingreso', 'slug' => 'costos-total-nuevo-ingreso'],
    ['categoria' => 'costos', 'q' => 'tienen mensualidades', 'slug' => 'costos-colegiatura'],
    ['categoria' => 'costos', 'q' => 'cual es la clabe para pagar', 'slug' => 'costos-datos-transferencia'],
    ['categoria' => 'costos', 'q' => 'como cambio mi comprobante de deposito', 'slug' => 'costos-recibo-oficial'],
    ['categoria' => 'costos', 'q' => 'cuando vence el pago del cuatrimestre', 'slug' => 'costos-fechas-pago'],
    ['categoria' => 'costos', 'q' => 'se puede ir pagando poco a poco', 'slug' => 'costos-pago-parcialidades'],
    ['categoria' => 'costos', 'q' => 'si no entro, me regresan mi dinero', 'slug' => 'costos-reembolso'],

    // Calendario (10)
    ['categoria' => 'calendario', 'q' => 'cuando arrancan las clases en enero', 'slug' => 'calendario-inicio-cuatrimestre'],
    ['categoria' => 'calendario', 'q' => 'y cuando terminan', 'slug' => 'calendario-fin-cuatrimestre', 'antes' => ['cuando arrancan las clases en enero']],
    ['categoria' => 'calendario', 'q' => 'cuando hay descanso en abril', 'slug' => 'calendario-vacaciones'],
    ['categoria' => 'calendario', 'q' => 'cuando presentan los finales', 'slug' => 'calendario-evaluaciones-finales'],
    ['categoria' => 'calendario', 'q' => 'cuando puedo recuperar una materia', 'slug' => 'calendario-extraordinarios'],
    ['categoria' => 'calendario', 'q' => 'cuando hay que reinscribirse en agosto', 'slug' => 'calendario-reinscripcion'],
    ['categoria' => 'calendario', 'q' => 'que fecha entregan boletas', 'slug' => 'calendario-calificaciones'],
    ['categoria' => 'calendario', 'q' => 'donde esta el calendario oficial', 'slug' => 'calendario-donde-ver'],
    ['categoria' => 'calendario', 'q' => 'cuando reciben a los de primer ingreso', 'slug' => 'calendario-induccion'],
    ['categoria' => 'calendario', 'q' => 'cuando exponen el proyecto final de cuatrimestre', 'slug' => 'calendario-proyecto-integrador'],

    // Becas (6)
    ['categoria' => 'becas', 'q' => 'dan apoyo economico a los alumnos', 'slug' => 'becas-disponibles'],
    ['categoria' => 'becas', 'q' => 'como le hago para pedirla', 'slug' => 'becas-como-solicitar', 'antes' => ['dan apoyo economico a los alumnos']],
    ['categoria' => 'becas', 'q' => 'que piden para darte una beca', 'slug' => 'becas-requisitos'],
    ['categoria' => 'becas', 'q' => 'cuando se abre la convocatoria de becas', 'slug' => 'becas-fechas'],
    ['categoria' => 'becas', 'q' => 'todavia existe el descuento de la mitad', 'slug' => 'becas-50-por-ciento'],
    ['categoria' => 'becas', 'q' => 'hay apoyo para el pasaje', 'slug' => 'becas-otros-apoyos'],

    // Trámites (9)
    ['categoria' => 'tramites', 'q' => 'necesito una constancia de estudios, donde la pido', 'slug' => 'tramites-constancia'],
    ['categoria' => 'tramites', 'q' => 'quiero mi historial academico', 'slug' => 'tramites-kardex'],
    ['categoria' => 'tramites', 'q' => 'perdi mi credencial, como la repongo', 'slug' => 'tramites-credencial'],
    ['categoria' => 'tramites', 'q' => 'como le hago para titularme', 'slug' => 'tramites-titulacion'],
    ['categoria' => 'tramites', 'q' => 'quiero darme de baja un tiempo', 'slug' => 'tramites-baja-temporal'],
    ['categoria' => 'tramites', 'q' => 'como corrijo mi nombre en el sistema', 'slug' => 'tramites-correccion-datos'],
    ['categoria' => 'tramites', 'q' => 'necesito el certificado de estudios', 'slug' => 'tramites-certificado'],
    ['categoria' => 'tramites', 'q' => 'a donde voy para mis tramites escolares', 'slug' => 'tramites-servicios-escolares'],
    ['categoria' => 'tramites', 'q' => 'me sirven materias de otra universidad', 'slug' => 'tramites-revalidacion'],

    // Servicios (6)
    ['categoria' => 'servicios', 'q' => 'hay biblioteca', 'slug' => 'servicios-biblioteca'],
    ['categoria' => 'servicios', 'q' => 'venden comida dentro del campus', 'slug' => 'servicios-comedor'],
    ['categoria' => 'servicios', 'q' => 'hay enfermeria', 'slug' => 'servicios-medico'],
    ['categoria' => 'servicios', 'q' => 'hay camion para los estudiantes', 'slug' => 'servicios-transporte'],
    ['categoria' => 'servicios', 'q' => 'dan asesoria si voy reprobando', 'slug' => 'servicios-tutorias'],
    ['categoria' => 'servicios', 'q' => 'aparte de clases, que servicios dan', 'slug' => 'servicios-que-ofrece'],

    // Deportes (7)
    ['categoria' => 'deportes', 'q' => 'que actividades deportivas puedo hacer', 'slug' => 'deportes-disponibles'],
    ['categoria' => 'deportes', 'q' => 'hay equipo femenil de futbol', 'slug' => 'deportes-futbol'],
    ['categoria' => 'deportes', 'q' => 'hay basquetbol varonil', 'slug' => 'deportes-basquetbol'],
    ['categoria' => 'deportes', 'q' => 'como entro a una seleccion', 'slug' => 'deportes-unirse'],
    ['categoria' => 'deportes', 'q' => 'cuando entrenan los equipos', 'slug' => 'deportes-entrenamientos'],
    ['categoria' => 'deportes', 'q' => 'que es la encarrera', 'slug' => 'deportes-carrera-tecnm'],
    ['categoria' => 'deportes', 'q' => 'donde veo las fotos de los partidos', 'slug' => 'deportes-postales'],

    // Noticias (5)
    ['categoria' => 'noticias', 'q' => 'cuales son las ultimas noticias', 'slug' => 'noticias-recientes'],
    ['categoria' => 'noticias', 'q' => 'donde estan las noticias', 'slug' => 'noticias-donde-ver'],
    ['categoria' => 'noticias', 'q' => 'hay notas de medio ambiente', 'slug' => 'noticias-medio-ambiente'],
    ['categoria' => 'noticias', 'q' => 'hay una nota de felicitacion a los aceptados', 'slug' => 'noticias-aceptados'],
    ['categoria' => 'noticias', 'q' => 'donde salen los avisos oficiales', 'slug' => 'noticias-comunicados'],

    // Transparencia (6)
    ['categoria' => 'transparencia', 'q' => 'donde esta el micrositio de transparencia', 'slug' => 'transparencia-donde-consultar'],
    ['categoria' => 'transparencia', 'q' => 'cada cuanto suben la informacion financiera', 'slug' => 'transparencia-actualizacion'],
    ['categoria' => 'transparencia', 'q' => 'cuanto presupuesto tiene la universidad', 'slug' => 'transparencia-presupuesto'],
    ['categoria' => 'transparencia', 'q' => 'hay informacion de la deuda', 'slug' => 'transparencia-deuda'],
    ['categoria' => 'transparencia', 'q' => 'quiero revisar el estado de situacion financiera', 'slug' => 'transparencia-estados-financieros'],
    ['categoria' => 'transparencia', 'q' => 'ya publicaron la cuenta anual de este año', 'slug' => 'transparencia-cuenta-anual'],

    // Contacto (5)
    ['categoria' => 'contacto', 'q' => 'a que numero marco', 'slug' => 'contacto-telefono'],
    ['categoria' => 'contacto', 'q' => 'a que correo puedo escribir', 'slug' => 'contacto-correo'],
    ['categoria' => 'contacto', 'q' => 'me pasan su domicilio', 'slug' => 'contacto-direccion'],
    ['categoria' => 'contacto', 'q' => 'hasta que hora atienden en la universidad', 'slug' => 'contacto-horario'],
    ['categoria' => 'contacto', 'q' => 'me pueden dar el contacto de admisiones', 'slug' => 'contacto-admision'],

    // Institucional (5)
    ['categoria' => 'institucional', 'q' => 'que quiere decir el nombre utypcb', 'slug' => 'institucional-significado'],
    ['categoria' => 'institucional', 'q' => 'de que se trata esta universidad', 'slug' => 'institucional-que-es'],
    ['categoria' => 'institucional', 'q' => 'hay clases de ingles', 'slug' => 'institucional-ingles'],
    ['categoria' => 'institucional', 'q' => 'estudio primero el tecnico y despues puedo continuar', 'slug' => 'institucional-modelo-tsu-licenciatura'],
    ['categoria' => 'institucional', 'q' => 'que filosofia tiene la universidad', 'slug' => 'institucional-valores'],
];
