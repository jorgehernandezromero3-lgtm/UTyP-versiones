(function () {
    'use strict';

    var formularios = document.querySelectorAll('form[data-confirmar]');
    Array.prototype.forEach.call(formularios, function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            var mensaje = formulario.getAttribute('data-confirmar');
            if (mensaje && !window.confirm(mensaje)) {
                evento.preventDefault();
            }
        });
    });

    var enviando = false;
    var principal = document.querySelector('[data-formulario-principal]');
    if (principal) {
        principal.addEventListener('submit', function () {
            if (enviando) {
                return;
            }
            enviando = true;
            var boton = principal.querySelector('button[type="submit"]');
            if (boton) {
                boton.setAttribute('aria-busy', 'true');
            }
            window.setTimeout(function () {
                enviando = false;
                if (boton) {
                    boton.removeAttribute('aria-busy');
                }
            }, 4000);
        });
    }
}());
