// js/views/ConfiguracionEmpresaView.js
(function () {
    'use strict';

    window.ConfiguracionEmpresaView = {
        _attachListeners: function () {
            // Botón guardar (en #empresa)
            document.querySelectorAll('#empresa [data-action="save-empresa"]').forEach(function (btn) {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', function () {
                    if (window.ConfiguracionEmpresaController) {
                        window.ConfiguracionEmpresaController.save();
                    }
                });
            });

            // Preview en vivo del nombre (botón de muestra del preview)
            var nombre = document.getElementById('empresa-nombre');
            if (nombre) {
                nombre.addEventListener('input', function () {
                    window.ConfiguracionEmpresaView.updatePreview();
                });
            }

            // Preview del logo login al seleccionar archivo
            var loginInput = document.getElementById('empresa-logo-login');
            if (loginInput) {
                loginInput.addEventListener('change', function () {
                    var file = loginInput.files && loginInput.files[0];
                    if (file) {
                        window.ConfiguracionEmpresaView.updateLogoPreview('login', file);
                    }
                });
            }

            // Preview del logo sidebar al seleccionar archivo
            var sidebarInput = document.getElementById('empresa-logo-sidebar');
            if (sidebarInput) {
                sidebarInput.addEventListener('change', function () {
                    var file = sidebarInput.files && sidebarInput.files[0];
                    if (file) {
                        window.ConfiguracionEmpresaView.updateLogoPreview('sidebar', file);
                    }
                });
            }

            // Color: no se aplica en vivo. Se aplica al guardar (vía _apply
            // del Controller, que llama loadFull() después del POST).
        },

        fillForm: function (config) {
            if (!config) return;

            var nombre = document.getElementById('empresa-nombre');
            if (nombre) nombre.value = config.nombre_empresa || '';

            var color = document.getElementById('empresa-color');
            if (color) color.value = config.color_primario || '#104aa8';

            var impuesto = document.getElementById('empresa-impuesto');
            if (impuesto) impuesto.value = (config.impuesto_porcentaje != null) ? config.impuesto_porcentaje : 0;

            var diaFact = document.getElementById('empresa-dia-facturacion');
            if (diaFact) diaFact.value = (config.dia_facturacion != null) ? config.dia_facturacion : 1;

            var diasGracia = document.getElementById('empresa-dias-gracia');
            if (diasGracia) diasGracia.value = (config.dias_gracia_mora != null) ? config.dias_gracia_mora : 5;

            var recargo = document.getElementById('empresa-recargo-mora');
            if (recargo) recargo.value = (config.monto_recargo_mora != null) ? config.monto_recargo_mora : 0;

            var diasCorte = document.getElementById('empresa-dias-corte');
            if (diasCorte) diasCorte.value = (config.dias_corte_automatico != null) ? config.dias_corte_automatico : 10;

            // Los file inputs no se pueden setear por JS. Se limpian.
            var logoLogin = document.getElementById('empresa-logo-login');
            if (logoLogin) logoLogin.value = '';
            var logoSidebar = document.getElementById('empresa-logo-sidebar');
            if (logoSidebar) logoSidebar.value = '';

            this.updatePreview();
        },

        /**
         * Actualiza el botón de muestra del preview con el nombre actual.
         */
        updatePreview: function () {
            var nombre = document.getElementById('empresa-nombre');
            var btnPreview = document.querySelector('#empresa .preview-card button');
            if (btnPreview) {
                var label = (nombre && nombre.value.trim()) || 'REDYTELCA';
                btnPreview.textContent = label;
            }
        },

        /**
         * Actualiza el preview de un logo.
         *
         * @param {'login'|'sidebar'} target
         * @param {File} file
         */
        updateLogoPreview: function (target, file) {
            var allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
            if (allowed.indexOf(file.type) === -1) {
                if (window.Toast) window.Toast.warning('Formato de imagen no soportado. Usa JPG, PNG, WEBP, GIF o SVG.');
                return;
            }
            var imgId = (target === 'sidebar')
                ? 'empresa-logo-preview-sidebar'
                : 'empresa-logo-preview-login';

            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.getElementById(imgId);
                if (img) img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window.ConfiguracionEmpresaController) {
            window.ConfiguracionEmpresaController.init();
        }
    });
})();