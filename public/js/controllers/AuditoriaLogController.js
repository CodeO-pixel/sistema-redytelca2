// js/controllers/AuditoriaLogController.js
(function () {
    'use strict';

    window.AuditoriaLogController = {
        _perPage: 50,
        _loading: false,

        init: function () {
            if (window.AuditoriaLogView) window.AuditoriaLogView._attachListeners();
            try {
                const tabla = sessionStorage.getItem('redytelca.auditoria.tabla');
                const accion = sessionStorage.getItem('redytelca.auditoria.accion');
                const desde = sessionStorage.getItem('redytelca.auditoria.desde');
                const hasta = sessionStorage.getItem('redytelca.auditoria.hasta');
                const page = sessionStorage.getItem('redytelca.auditoria.page');
                const t = document.getElementById('auditoria-tabla');
                const a = document.getElementById('auditoria-accion');
                const d = document.getElementById('auditoria-desde');
                const h = document.getElementById('auditoria-hasta');
                if (t && tabla) t.value = tabla;
                if (a && accion) a.value = accion;
                if (d && desde) d.value = desde;
                if (h && hasta) h.value = hasta;
                if (page) window.__auditoriaCurrentPage = parseInt(page, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = 1) {
            if (this._loading) return;
            this._loading = true;

            const btn = document.querySelector('[data-toolbar="auditoria"] [data-action="apply-filters"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Buscando…'; }

            const filters = this._readFilters();
            const hasFilters = !!(filters.tabla || filters.accion || filters.desde || filters.hasta);

            try {
                const res = await window.AuditoriaLogModel.load({
                    page: page,
                    per_page: this._perPage,
                    tabla: filters.tabla,
                    accion: filters.accion,
                    desde: filters.desde,
                    hasta: filters.hasta
                });

                if (res.status !== 'success') {
                    window.Toast.error(res.message || 'Error al cargar auditoría');
                    return;
                }

                const logs = res.logs || [];
                const safePage = res.page || 1;
                const totalPages = res.pages || 1;
                const total = res.total || 0;

                window.__auditoriaCurrentPage = safePage;
                this._persist(filters, safePage);

                window.AuditoriaLogView.renderTable(logs, hasFilters);
                window.AuditoriaLogView.renderPagination(safePage, totalPages);
                window.AuditoriaLogView.updateCounter(total, safePage, this._perPage);
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar auditoría');
            } finally {
                this._loading = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        applyFilters: function () {
            this.load(1);
        },

        clearFilters: function () {
            const ids = ['auditoria-tabla', 'auditoria-accion', 'auditoria-desde', 'auditoria-hasta'];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            this.load(1);
        },

        showDetail: function (id) {
            const log = window.AuditoriaLogModel.getById(id);
            if (!log) {
                window.Toast.error('Registro no encontrado');
                return;
            }
            window.AuditoriaLogView.renderDetailModal(log);
        },

        _readFilters: function () {
            const getVal = (id) => {
                const el = document.getElementById(id);
                return el ? String(el.value || '').trim() : '';
            };
            return {
                tabla: getVal('auditoria-tabla'),
                accion: getVal('auditoria-accion'),
                desde: getVal('auditoria-desde'),
                hasta: getVal('auditoria-hasta')
            };
        },

        _persist: function (filters, page) {
            try {
                sessionStorage.setItem('redytelca.auditoria.tabla', filters.tabla || '');
                sessionStorage.setItem('redytelca.auditoria.accion', filters.accion || '');
                sessionStorage.setItem('redytelca.auditoria.desde', filters.desde || '');
                sessionStorage.setItem('redytelca.auditoria.hasta', filters.hasta || '');
                sessionStorage.setItem('redytelca.auditoria.page', String(page || 1));
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.AuditoriaLogController.init());
})();