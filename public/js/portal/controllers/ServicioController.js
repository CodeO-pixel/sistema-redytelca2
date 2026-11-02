// js/portal/controllers/ServicioController.js
// Controller del portal para "Mis servicios" (096e, read-only).
// - load(): carga el cache del Model.
// - renderPage(page): filtra por búsqueda, pagina client-side (PAGE_SIZE=10).
// - showDetail(id): pide el detalle al backend y abre el modal.
// - Persiste búsqueda y página en sessionStorage (clave propia del portal).
(function () {
    'use strict';

    window.PortalServicioController = {
        _searchTimer: null,

        init: function () {
            if (window.PortalServicioView) {
                window.PortalServicioView._attachListeners();
            }
            this._restore();
        },

        load: async function () {
            try {
                await window.PortalServicioModel.load(100);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar tus servicios');
                return;
            }
            this.renderPage(window.__portalServicioCurrentPage || 1);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.PortalServicioModel.cache, search);
            const size = window.PortalServicioView.PAGE_SIZE;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;

            window.__portalServicioCurrentPage = safePage;
            this._persist(search, safePage);

            window.PortalServicioView.renderTable(
                filtered.slice(start, start + size),
                search
            );
            window.PortalServicioView.renderPagination(safePage, totalPages);
            window.PortalServicioView.updateCounter(filtered.length, safePage, size);
        },

        showDetail: async function (id) {
            if (!id) return;

            // Si el servicio ya está en cache, mostrar de inmediato
            // con esos datos. Enriquecer con el detalle del backend
            // si hace falta (no bloquea la apertura del modal).
            const cached = window.PortalServicioModel.getById(id);
            if (cached) {
                window.PortalServicioView.renderDetailModal(cached);
            }

            try {
                const detalle = await window.PortalServicioModel.loadDetail(id);
                if (detalle) {
                    window.PortalServicioView.renderDetailModal(detalle);
                } else if (!cached) {
                    if (window.Toast) window.Toast.error('Servicio no encontrado');
                }
            } catch (e) {
                if (!cached && window.Toast) {
                    window.Toast.error(e.message || 'Error al cargar el detalle');
                }
            }
        },

        _readSearch: function () {
            const input = document.getElementById('portal-servicios-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.portal.servicios.search', search || '');
                sessionStorage.setItem('redytelca.portal.servicios.page', String(page || 1));
            } catch (e) { /* no-op */ }
        },

        _restore: function () {
            try {
                const s = sessionStorage.getItem('redytelca.portal.servicios.search');
                const p = sessionStorage.getItem('redytelca.portal.servicios.page');
                const input = document.getElementById('portal-servicios-search');
                if (input && s) input.value = s;
                if (p) window.__portalServicioCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = String(search || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
            if (!s) return items;
            return items.filter(item => {
                const haystack = [
                    item.alias || '',
                    item.plan_nombre || '',
                    item.nap_codigo || '',
                    item.olt_codigo || '',
                    item.nodo_nombre || '',
                    item.direccion_texto || '',
                    item.estado_comercial || ''
                ].join(' ');
                return String(haystack)
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .includes(s);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PortalServicioController.init());
})();