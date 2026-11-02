// js/controllers/ConfiguracionEmpresaController.js
(function () {
    'use strict';

    window.ConfiguracionEmpresaController = {
        init: function () {
            if (window.ConfiguracionEmpresaView) {
                window.ConfiguracionEmpresaView._attachListeners();
            }
        },

        /**
         * Carga el branding público (nombre, logos, color) desde el backend.
         *
         * - Se llama en main.js ANTES de restoreSession (resuelve bug 3: incógnito
         *   limpio muestra el branding real desde el primer frame).
         * - Se llama en AuthController después del login (idempotente).
         * - Se llama en initApp (idempotente).
         *
         * Cache: si Model.cache.publico ya está poblado, no re-fetchea.
         * Reset: Session.onClear limpia Model.cache.publico al logout.
         */
        loadBranding: async function (force = false) {
            if (!force && window.ConfiguracionEmpresaModel && window.ConfiguracionEmpresaModel.cache.publico) {
                return { status: 'success', config: window.ConfiguracionEmpresaModel.cache.publico };
            }
            const res = await window.ConfiguracionEmpresaModel.loadPublica();
            if (res.status === 'success' && res.config) {
                this._apply(res.config);
            }
            return res;
        },

        loadFull: async function () {
            const res = await window.ConfiguracionEmpresaModel.loadCompleta();
            if (res.status === 'success' && res.config) {
                this._apply(res.config);
                if (window.ConfiguracionEmpresaView) {
                    window.ConfiguracionEmpresaView.fillForm(res.config);
                }
            }
            return res;
        },

        update: async function (data, files) {
            return window.ConfiguracionEmpresaModel.update(data, files);
        },

        save: async function () {
            const nombre = (document.getElementById('empresa-nombre')?.value || '').trim();
            const color = document.getElementById('empresa-color')?.value || '';
            const impuesto = document.getElementById('empresa-impuesto')?.value || '0';
            const diaFact = document.getElementById('empresa-dia-facturacion')?.value || '1';
            const diasGracia = document.getElementById('empresa-dias-gracia')?.value || '0';
            const recargoMora = document.getElementById('empresa-recargo-mora')?.value || '0';
            const diasCorte = document.getElementById('empresa-dias-corte')?.value || '0';
            const loginFile = document.getElementById('empresa-logo-login')?.files?.[0] || null;
            const sidebarFile = document.getElementById('empresa-logo-sidebar')?.files?.[0] || null;

            // Validación frontend (refleja EmpresaService::validarConfiguracion)
            if (!nombre) { window.Toast.warning('El nombre de la empresa es obligatorio.'); return; }
            if (!/^#[0-9a-fA-F]{6}$/.test(color)) { window.Toast.warning('El color debe tener formato #RRGGBB.'); return; }
            const imp = Number(impuesto);
            if (isNaN(imp) || imp < 0 || imp > 100) { window.Toast.warning('El impuesto debe estar entre 0 y 100.'); return; }
            const diaFactN = Number(diaFact);
            if (isNaN(diaFactN) || diaFactN < 1 || diaFactN > 31) { window.Toast.warning('El día de facturación debe estar entre 1 y 31.'); return; }
            if (Number(diasGracia) < 0) { window.Toast.warning('Los días de gracia no pueden ser negativos.'); return; }
            if (Number(recargoMora) < 0) { window.Toast.warning('El recargo por mora no puede ser negativo.'); return; }
            if (Number(diasCorte) < 0) { window.Toast.warning('Los días de corte no pueden ser negativos.'); return; }

            const btn = document.querySelector('#empresa [data-action="save-empresa"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = await this.update({
                    nombre_empresa: nombre,
                    color_primario: color,
                    impuesto_porcentaje: impuesto,
                    dia_facturacion: diaFact,
                    dias_gracia_mora: diasGracia,
                    monto_recargo_mora: recargoMora,
                    dias_corte_automatico: diasCorte
                }, { login: loginFile, sidebar: sidebarFile });

                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Configuración actualizada');
                    await this.loadFull();
                } else {
                    window.Toast.error(res.message || 'Error al guardar la configuración');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al guardar la configuración');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        /**
         * Aplica la config al DOM.
         *
         * - --accent y --accent-rgb: variables CSS globales.
         * - brand-name / login-brand-name: si existen (compatibilidad con HTML
         *   viejo), se actualizan. Si el HTML los eliminó (B3.11 fix), no falla.
         * - sidebar-brand-logo: usa logo_path_sidebar (fondo oscuro) con fallback
         *   al SVG blanco.
         * - login-brand-logo: usa logo_path (fondo claro) con fallback al PNG.
         * - empresa-logo-preview-login / empresa-logo-preview-sidebar: previews
         *   del form de Empresa.
         *
         * Los previews se actualizan SOLO si ya existen en el DOM (la página
         * #empresa puede no estar montada todavía).
         */
        _apply: function (config) {
            const name = config.nombre_empresa || 'REDYTELCA';
            const color = config.color_primario || '#104aa8';
            const logoLogin = config.logo_path || 'Logos/logo_transparent.png';
            const logoSidebar = config.logo_path_sidebar || 'Logos/redytelca-identiadad-tipo-c-blanco.svg';

            document.documentElement.style.setProperty('--accent', color);
            const hex = String(color).replace('#', '');
            if (hex.length === 6) {
                const r = parseInt(hex.substring(0, 2), 16);
                const g = parseInt(hex.substring(2, 4), 16);
                const b = parseInt(hex.substring(4, 6), 16);
                document.documentElement.style.setProperty('--accent-rgb', r + ', ' + g + ', ' + b);
            }

            // Spans del nombre (compatibilidad, si el HTML los tiene)
            ['brand-name', 'login-brand-name'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.textContent = name;
            });

            // Logos en sidebar y login
            ['sidebar-brand-logo'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.src = logoSidebar; el.alt = name; }
            });
            ['login-brand-logo'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.src = logoLogin; el.alt = name; }
            });

            // Previews del form de Empresa (si la página está montada)
            ['empresa-logo-preview-login'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.src = logoLogin; el.alt = name + ' (login)'; }
            });
            ['empresa-logo-preview-sidebar'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.src = logoSidebar; el.alt = name + ' (sidebar)'; }
            });

            try {
                localStorage.setItem('redytelca_empresa_config', JSON.stringify(config));
            } catch (e) { /* quota excedida, ignorar */ }
        }
    };
})();