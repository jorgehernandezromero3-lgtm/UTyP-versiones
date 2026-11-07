(function () {
    'use strict';

    var raiz = document.getElementById('utyp-asistente');
    if (!raiz || raiz.getAttribute('data-iniciado') === '1') {
        return;
    }
    raiz.setAttribute('data-iniciado', '1');

    var refs = {
        abrir: raiz.querySelector('[data-abrir]'),
        panel: raiz.querySelector('[data-panel]'),
        cerrar: raiz.querySelector('[data-cerrar]'),
        reiniciar: raiz.querySelector('[data-reiniciar]'),
        mensajes: raiz.querySelector('[data-mensajes]'),
        bienvenida: raiz.querySelector('[data-bienvenida]'),
        inicio: raiz.querySelector('[data-inicio]'),
        form: raiz.querySelector('[data-form]'),
        texto: raiz.querySelector('[data-texto]'),
        enviar: raiz.querySelector('[data-enviar]'),
        contador: raiz.querySelector('[data-contador]'),
        estado: raiz.querySelector('[data-estado-sesion]'),
        estadoTexto: raiz.querySelector('[data-estado-texto]'),
        reintentar: raiz.querySelector('[data-reintentar]'),
        conexion: raiz.querySelector('[data-conexion]')
    };

    if (!refs.abrir || !refs.panel || !refs.form || !refs.texto || !refs.enviar || !refs.mensajes) {
        return;
    }

    var estado = {
        abierto: false,
        iniciado: false,
        listo: false,
        enviando: false,
        cargando: false,
        promesa: null,
        csrf: '',
        minimo: 2,
        maximo: 300,
        inicio: [],
        focoPrevio: null
    };

    var INICIO_RESPALDO = [
        '¿Cómo es el proceso de admisión?',
        '¿Qué carreras ofrece la UTyPCB?',
        '¿Cuánto cuesta la ficha?',
        '¿Cuándo inicia el cuatrimestre?'
    ];

    function texto(valor) {
        return String(valor === undefined || valor === null ? '' : valor);
    }

    function marcarEstado(tipo, mensaje) {
        if (refs.estado) {
            refs.estado.setAttribute('data-estado-sesion', tipo);
        }
        if (refs.estadoTexto) {
            refs.estadoTexto.textContent = mensaje;
        }
    }

    function actualizarBoton() {
        var largo = refs.texto.value.trim().length;
        refs.enviar.disabled = !estado.listo || estado.enviando || largo === 0;
        refs.contador.textContent = refs.texto.value.length + '/' + estado.maximo;
        refs.contador.setAttribute('data-cerca', refs.texto.value.length >= estado.maximo - 30 ? '1' : '0');
    }

    function ajustarAltura() {
        refs.texto.style.height = 'auto';
        refs.texto.style.height = Math.min(refs.texto.scrollHeight, 116) + 'px';
    }

    function desplazar(suave) {
        var reducir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var comportamiento = suave && !reducir ? 'smooth' : 'auto';
        try {
            refs.mensajes.scrollTo({ top: refs.mensajes.scrollHeight, behavior: comportamiento });
        } catch (e) {
            refs.mensajes.scrollTop = refs.mensajes.scrollHeight;
        }
    }

    function mostrarConexion(mensaje, conBoton) {
        if (refs.conexion) {
            refs.conexion.textContent = mensaje;
            refs.conexion.hidden = false;
        }
        if (refs.reintentar) {
            refs.reintentar.hidden = !conBoton;
        }
    }

    function ocultarConexion() {
        if (refs.conexion) {
            refs.conexion.hidden = true;
        }
        if (refs.reintentar) {
            refs.reintentar.hidden = true;
        }
    }

    function cargarEstado() {
        if (estado.promesa) {
            return estado.promesa;
        }
        if (!raiz.getAttribute('data-estado')) {
            return Promise.reject(new Error('sin-ruta'));
        }

        estado.cargando = true;
        marcarEstado('cargando', 'Conectando…');

        estado.promesa = fetch(raiz.getAttribute('data-estado'), {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (res) {
                return res.json().catch(function () { return null; }).then(function (datos) {
                    if (!res.ok || !datos || datos.ok !== true || !datos.csrf) {
                        throw new Error('conexion');
                    }
                    return datos;
                });
            })
            .then(function (datos) {
                estado.csrf = texto(datos.csrf);
                estado.listo = true;
                estado.cargando = false;
                estado.promesa = null;
                estado.minimo = Math.max(1, Number(datos.limites && datos.limites.min_mensaje) || 2);
                estado.maximo = Math.max(10, Number(datos.limites && datos.limites.max_mensaje) || 300);
                refs.texto.setAttribute('maxlength', String(estado.maximo));
                estado.inicio = Array.isArray(datos.sugerencias) ? datos.sugerencias : [];
                pintarInicio();
                marcarEstado('listo', 'En línea · Información oficial');
                ocultarConexion();
                actualizarBoton();
                return datos;
            })
            .catch(function (error) {
                estado.listo = false;
                estado.cargando = false;
                estado.promesa = null;
                marcarEstado('error', 'Sin conexión');
                mostrarConexion('No pude conectar con el asistente. Revisa tu conexión y vuelve a intentarlo.', true);
                actualizarBoton();
                throw error;
            });

        return estado.promesa;
    }

    function crearBotonPregunta(pregunta, clase) {
        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = clase || '';
        boton.textContent = texto(pregunta);
        boton.setAttribute('aria-label', 'Preguntar: ' + texto(pregunta));
        boton.addEventListener('click', function () {
            enviar(texto(pregunta));
        });
        return boton;
    }

    function pintarInicio() {
        if (!refs.inicio) {
            return;
        }
        refs.inicio.textContent = '';
        var lista = estado.inicio.length ? estado.inicio : INICIO_RESPALDO.map(function (pregunta) {
            return { pregunta: pregunta };
        });

        lista.slice(0, 4).forEach(function (item) {
            var pregunta = item && item.pregunta ? item.pregunta : '';
            if (pregunta) {
                refs.inicio.appendChild(crearBotonPregunta(pregunta));
            }
        });
    }

    function crearMensaje(tipo, contenido, esError) {
        var articulo = document.createElement('article');
        articulo.className = 'utyp-chat__mensaje es-' + (tipo === 'usuario' ? 'usuario' : 'bot') + (esError ? ' es-error' : '');

        var autor = document.createElement('span');
        autor.className = 'utyp-chat__autor';
        autor.textContent = tipo === 'usuario' ? 'Tú' : 'Asistente';

        var burbuja = document.createElement('div');
        burbuja.className = 'utyp-chat__burbuja';

        var parrafo = document.createElement('p');
        parrafo.className = 'utyp-chat__texto';
        parrafo.textContent = texto(contenido);

        burbuja.appendChild(parrafo);
        articulo.appendChild(autor);
        articulo.appendChild(burbuja);
        refs.mensajes.appendChild(articulo);
        desplazar(true);
        return articulo;
    }

    function urlSegura(valor) {
        try {
            var url = new URL(texto(valor), window.location.href);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') {
                return null;
            }
            return url.href;
        } catch (e) {
            return null;
        }
    }

    function agregarEnlace(contenedor, enlace) {
        var url = urlSegura(enlace && enlace.url);
        if (!url) {
            return;
        }
        var vinculo = document.createElement('a');
        vinculo.href = url;
        vinculo.target = '_blank';
        vinculo.rel = 'noopener noreferrer';
        vinculo.textContent = texto(enlace && enlace.titulo ? enlace.titulo : 'Más información');
        contenedor.appendChild(vinculo);
    }

    function agregarFuentes(articulo, datos) {
        var urls = {};
        var bloque = document.createElement('div');
        bloque.className = 'utyp-chat__fuente';

        if (datos.fuente && datos.fuente.url) {
            var etiqueta = document.createElement('span');
            etiqueta.className = 'utyp-chat__etiqueta';
            etiqueta.textContent = 'Fuente oficial';
            bloque.appendChild(etiqueta);
            agregarEnlace(bloque, datos.fuente);
            var fuenteUrl = urlSegura(datos.fuente.url);
            if (fuenteUrl) {
                urls[fuenteUrl] = true;
            }
        }

        if (Array.isArray(datos.enlaces) && datos.enlaces.length) {
            var extra = document.createElement('div');
            extra.className = 'utyp-chat__enlaces';
            var agregados = 0;
            datos.enlaces.forEach(function (enlace) {
                var url = urlSegura(enlace && enlace.url);
                if (!url || urls[url]) {
                    return;
                }
                urls[url] = true;
                agregarEnlace(extra, enlace);
                agregados++;
            });
            if (agregados > 0) {
                bloque.appendChild(extra);
            }
        }

        if (bloque.childNodes.length > 0) {
            var burbuja = articulo.querySelector('.utyp-chat__burbuja');
            if (burbuja) {
                burbuja.appendChild(bloque);
            }
        }
    }

    function agregarSugerencias(datos) {
        if (!Array.isArray(datos.sugerencias) || datos.sugerencias.length === 0) {
            return;
        }

        var grupo = document.createElement('div');
        grupo.className = 'utyp-chat__sugerencias';
        grupo.setAttribute('role', 'group');
        grupo.setAttribute('aria-label', 'Preguntas relacionadas');

        datos.sugerencias.slice(0, 4).forEach(function (sugerencia) {
            var pregunta = sugerencia && sugerencia.pregunta ? sugerencia.pregunta : '';
            if (pregunta) {
                grupo.appendChild(crearBotonPregunta(pregunta));
            }
        });

        if (grupo.childNodes.length > 0) {
            refs.mensajes.appendChild(grupo);
            desplazar(true);
        }
    }

    function quitarSugerencias() {
        Array.prototype.forEach.call(refs.mensajes.querySelectorAll('.utyp-chat__sugerencias'), function (nodo) {
            nodo.parentNode.removeChild(nodo);
        });
    }

    function mostrarEscribiendo() {
        var articulo = document.createElement('div');
        articulo.className = 'utyp-chat__mensaje es-bot utyp-chat__escribiendo';
        articulo.setAttribute('role', 'status');
        articulo.setAttribute('aria-label', 'El asistente está escribiendo');

        for (var i = 0; i < 3; i++) {
            articulo.appendChild(document.createElement('i'));
        }
        refs.mensajes.appendChild(articulo);
        desplazar(true);
        return articulo;
    }

    function quitarNodo(nodo) {
        if (nodo && nodo.parentNode) {
            nodo.parentNode.removeChild(nodo);
        }
    }

    function ocultarBienvenida() {
        if (refs.bienvenida) {
            refs.bienvenida.hidden = true;
        }
    }

    function mostrarBienvenida() {
        if (refs.bienvenida) {
            refs.bienvenida.hidden = false;
        }
        pintarInicio();
    }

    function limpiarConversacion() {
        Array.prototype.forEach.call(refs.mensajes.querySelectorAll('.utyp-chat__mensaje, .utyp-chat__sugerencias'), function (nodo) {
            nodo.parentNode.removeChild(nodo);
        });
        mostrarBienvenida();
    }

    function mensajeDeError(error) {
        if (error && error.servidor && error.message) {
            return error.message;
        }
        if (error && error.message === 'conexion') {
            return 'No pude conectar con el asistente. Revisa tu conexión e inténtalo de nuevo.';
        }
        return 'No pude completar la respuesta. Inténtalo de nuevo en un momento.';
    }

    function solicitarChat(cuerpo) {
        return fetch(raiz.getAttribute('data-chat'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-Token': estado.csrf
            },
            credentials: 'same-origin',
            cache: 'no-store',
            body: JSON.stringify(cuerpo)
        }).then(function (res) {
            return res.json().catch(function () { return null; }).then(function (datos) {
                if (res.status === 403) {
                    var expirada = new Error('sesion');
                    expirada.reintentar = true;
                    throw expirada;
                }
                if (!res.ok || !datos || datos.ok !== true) {
                    var fallo = new Error(datos && datos.error ? datos.error : 'No se pudo procesar la pregunta.');
                    fallo.servidor = true;
                    throw fallo;
                }
                return datos;
            });
        });
    }

    function enviar(valor, reintento) {
        if (estado.enviando) {
            return;
        }

        var pregunta = texto(valor).trim();
        if (!pregunta) {
            refs.texto.focus();
            return;
        }
        if (pregunta.length < estado.minimo) {
            refs.texto.focus();
            return;
        }
        if (pregunta.length > estado.maximo) {
            pregunta = pregunta.slice(0, estado.maximo);
        }

        if (!estado.listo) {
            if (estado.cargando) {
                return;
            }
            cargarEstado().then(function () {
                enviar(pregunta, reintento);
            }).catch(function () {
                mostrarConexion('No pude conectar con el asistente. Revisa tu conexión y vuelve a intentarlo.', true);
            });
            return;
        }

        ocultarConexion();
        quitarSugerencias();
        ocultarBienvenida();
        crearMensaje('usuario', pregunta);
        refs.texto.value = '';
        ajustarAltura();
        estado.enviando = true;
        actualizarBoton();
        var escribiendo = mostrarEscribiendo();

        solicitarChat({ mensaje: pregunta })
            .then(function (datos) {
                quitarNodo(escribiendo);
                estado.enviando = false;
                actualizarBoton();
                var respuesta = crearMensaje('bot', datos.mensaje || 'No recibí una respuesta. Inténtalo de nuevo.');
                agregarFuentes(respuesta, datos);
                agregarSugerencias(datos);
                if (window.innerWidth > 560) {
                    refs.texto.focus();
                }
            })
            .catch(function (error) {
                quitarNodo(escribiendo);
                estado.enviando = false;
                actualizarBoton();

                if (error && error.reintentar && !reintento) {
                    estado.listo = false;
                    estado.csrf = '';
                    cargarEstado().then(function () {
                        enviar(pregunta, true);
                    }).catch(function () {
                        var fallo = crearMensaje('bot', 'Tu sesión expiró y no pude restablecerla. Recarga la página e inténtalo de nuevo.', true);
                        agregarFuentes(fallo, {});
                    });
                    return;
                }

                crearMensaje('bot', mensajeDeError(error), true);
                if (error && error.reintentar) {
                    mostrarConexion('La sesión expiró. Recarga la página para continuar.', false);
                }
            });
    }

    function abrir() {
        if (estado.abierto) {
            return;
        }
        estado.abierto = true;
        estado.focoPrevio = document.activeElement;
        refs.panel.hidden = false;
        refs.abrir.setAttribute('aria-expanded', 'true');
        refs.panel.setAttribute('tabindex', '-1');
        refs.panel.focus({ preventScroll: true });

        if (!estado.iniciado) {
            estado.iniciado = true;
            cargarEstado().catch(function () {});
        }
    }

    function cerrar() {
        if (!estado.abierto) {
            return;
        }
        estado.abierto = false;
        refs.panel.hidden = true;
        refs.abrir.setAttribute('aria-expanded', 'false');
        var foco = estado.focoPrevio && typeof estado.focoPrevio.focus === 'function'
            ? estado.focoPrevio
            : refs.abrir;
        foco.focus({ preventScroll: true });
    }

    function reiniciar() {
        if (!estado.abierto || estado.enviando) {
            return;
        }
        if (!window.confirm('¿Empezar una conversación nueva? Se borrará el historial de este chat.')) {
            return;
        }

        if (!estado.listo) {
            limpiarConversacion();
            refs.texto.focus();
            return;
        }

        solicitarChat({ accion: 'reiniciar' })
            .then(function () {
                limpiarConversacion();
                refs.texto.value = '';
                ajustarAltura();
                actualizarBoton();
                refs.texto.focus();
            })
            .catch(function (error) {
                if (error && error.reintentar) {
                    estado.listo = false;
                    estado.csrf = '';
                    cargarEstado().then(function () {
                        reiniciar();
                    }).catch(function () {
                        crearMensaje('bot', 'No pude reiniciar la conversación. Recarga la página e inténtalo de nuevo.', true);
                    });
                    return;
                }
                crearMensaje('bot', mensajeDeError(error), true);
            });
    }

    function elementosEnfocables() {
        var selector = 'a[href], button:not([disabled]), textarea:not([disabled]), ' +
            'input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
        return Array.prototype.filter.call(refs.panel.querySelectorAll(selector), function (el) {
            return el.getClientRects().length > 0;
        });
    }

    refs.panel.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Tab' || !estado.abierto) {
            return;
        }
        var lista = elementosEnfocables();
        if (!lista.length) {
            evento.preventDefault();
            refs.panel.focus();
            return;
        }
        var primero = lista[0];
        var ultimo = lista[lista.length - 1];
        var activo = document.activeElement;
        if (evento.shiftKey && (activo === primero || activo === refs.panel)) {
            evento.preventDefault();
            ultimo.focus();
        } else if (!evento.shiftKey && activo === ultimo) {
            evento.preventDefault();
            primero.focus();
        }
    });

    refs.abrir.addEventListener('click', function () {
        if (estado.abierto) {
            cerrar();
        } else {
            abrir();
        }
    });

    if (refs.cerrar) {
        refs.cerrar.addEventListener('click', cerrar);
    }
    if (refs.reiniciar) {
        refs.reiniciar.addEventListener('click', reiniciar);
    }
    if (refs.reintentar) {
        refs.reintentar.addEventListener('click', function () {
            estado.listo = false;
            estado.csrf = '';
            ocultarConexion();
            cargarEstado().catch(function () {});
        });
    }

    refs.form.addEventListener('submit', function (evento) {
        evento.preventDefault();
        enviar(refs.texto.value);
    });

    refs.texto.addEventListener('input', function () {
        ajustarAltura();
        actualizarBoton();
    });

    refs.texto.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter' && !evento.shiftKey && !evento.isComposing) {
            evento.preventDefault();
            enviar(refs.texto.value);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && estado.abierto) {
            cerrar();
        }
    });

    ajustarAltura();
    actualizarBoton();
})();
