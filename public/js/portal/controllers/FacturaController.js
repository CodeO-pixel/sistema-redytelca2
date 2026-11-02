// js/portal/controllers/FacturaController.js
// Controller del portal para "Mis facturas" (096b, read-only).
// - load(page): lee filtros actuales, llama al modelo con paginación
//   server-side y renderiza.
// - renderPage(page): delega en la vista.
// - applyFilters(): recarga desde la página 1 con los filtros actuales.
// - clearFilters(): limpia los filtros y recarga.
// - showDetail(id): pide el detalle al backend y abre el modal.
// - Persiste filtros y página en sessionStorage (clave propia del portal).
//
// A3 (2026-11-02): `load(page = null)` con default `null` en vez de
// `undefined`. El caller (`main.js`) ahora llama `load()` sin argumento,
// y este controller usa `window.__portalFacturaCurrentPage` restaurado
// de sessionStorage. Antes `main.js` llamaba `load(1)` y forzaba
// página 1 en cada navegación. Consistente con el patrón del admin
// (`ContratoController.load = function (page = null)`).
(function () {
    'use strict';

    window.PortalFacturaController = {
        init: function () {
            if (window.PortalFacturaView) {
                window.PortalFacturaView._attachListeners();
            }
            this._restore();
        },

        load: async function (page = null) {
            const target = page != null ? page : (window.__portalFacturaCurrentPage || 1);
            const filtros = this._readFilters();

            const btn = document.querySelector('[data-toolbar="portal-facturas"] [data-action="apply-filters"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Cargando…'; }

            try {
                const res = await window.PortalFacturaModel.load(target, 20, filtros);
                if (res.status !== 'success') {
                    if (window.Toast) window.Toast.error(res.message || 'Error al cargar tus facturas');
                    return;
                }

                const facturas = res.facturas || [];
                const safePage = res.page || 1;
                const totalPages = res.pages || 1;
                const total = res.total || 0;

                window.__portalFacturaCurrentPage = safePage;
                this._persist(filtros, safePage);

                window.PortalFacturaView.renderTable(facturas, filtros.periodo);
                window.PortalFacturaView.renderPagination(safePage, totalPages);
                window.PortalFacturaView.updateCounter(total, safePage, 20);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar tus facturas');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        applyFilters: function () {
            this.load(1);
        },

        clearFilters: function () {
            const estado = document.getElementById('portal-facturas-estado');
            const periodo = document.getElementById('portal-facturas-periodo');
            if (estado) estado.value = '';
            if (periodo) periodo.value = '';
            this.load(1);
        },

        showDetail: async function (id) {
            if (!id) return;

            // Si la factura ya está en cache, mostrar de inmediato
            // con esos datos. Enriquecer con el detalle del backend
            // si hace falta (no bloquea la apertura del modal).
            const cached = window.PortalFacturaModel.getById(id);
            if (cached) {
                window.PortalFacturaView.renderDetailModal(cached, []);
            }

            try {
                const detalle = await window.PortalFacturaModel.loadDetail(id);
                if (detalle) {
                    window.PortalFacturaView.renderDetailModal(detalle.factura, detalle.lineas);
                } else if (!cached) {
                    if (window.Toast) window.Toast.error('Factura no encontrada');
                }
            } catch (e) {
                if (!cached && window.Toast) {
                    window.Toast.error(e.message || 'Error al cargar el detalle');
                }
            }
        },

        _readFilters: function () {
            const estado = document.getElementById('portal-facturas-estado');
            const periodo = document.getElementById('portal-facturas-periodo');
            return {
                estado: estado ? estado.value : '',
                periodo: periodo ? String(periodo.value || '').trim() : ''
            };
        },

        _persist: function (filtros, page) {
            try {
                sessionStorage.setItem('redytelca.portal.facturas.estado', filtros.estado || '');
                sessionStorage.setItem('redytelca.portal.facturas.periodo', filtros.periodo || '');
                sessionStorage.setItem('redytelca.portal.facturas.page', String(page || 1));
            } catch (e) { /* no-op */ }
        },

        _restore: function () {
            try {
                const estado = sessionStorage.getItem('redytelca.portal.facturas.estado');
                const periodo = sessionStorage.getItem('redytelca.portal.facturas.periodo');
                const page = sessionStorage.getItem('redytelca.portal.facturas.page');

                const elEstado = document.getElementById('portal-facturas-estado');
                const elPeriodo = document.getElementById('portal-facturas-periodo');
                if (elEstado && estado) elEstado.value = estado;
                if (elPeriodo && periodo) elPeriodo.value = periodo;

                if (page) {
                    window.__portalFacturaCurrentPage = parseInt(page, 10) || 1;
                }
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PortalFacturaController.init());
})();