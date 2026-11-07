(function () {
    'use strict';

    function abrirAsistente() {
        var lanzador = document.querySelector('#utyp-asistente [data-abrir]');
        if (!lanzador) {
            return;
        }
        lanzador.click();
    }

    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-demo-abrir]');
        if (!boton) {
            return;
        }
        evento.preventDefault();
        abrirAsistente();
    });

    var menu = document.querySelector('[data-menu]');
    var nav = document.getElementById('up-nav');

    if (menu && nav) {
        menu.addEventListener('click', function () {
            var abierto = nav.getAttribute('data-abierto') === '1';
            nav.setAttribute('data-abierto', abierto ? '0' : '1');
            menu.setAttribute('aria-expanded', abierto ? 'false' : 'true');
            menu.setAttribute('aria-label', abierto ? 'Abrir menú' : 'Cerrar menú');
        });

        nav.addEventListener('click', function (evento) {
            if (evento.target.closest('a')) {
                nav.setAttribute('data-abierto', '0');
                menu.setAttribute('aria-expanded', 'false');
                menu.setAttribute('aria-label', 'Abrir menú');
            }
        });
    }

    if (window.location.hash === '#asistente') {
        window.setTimeout(abrirAsistente, 500);
    }
})();
