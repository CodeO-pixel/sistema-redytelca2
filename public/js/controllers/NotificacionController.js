// js/controllers/NotificacionController.js
(function () {
    'use strict';

    window.NotificacionController = {
        _searchTimer: null,
        _pollTimer: null,
        _pollMs: 30000,
        _loading: false,

        init: function () {
            if (window.NotificacionView) window.NotificacionView._attachListeners();
            this._restore();
            // NO startPolling aquí. Se arranca vía startPolling() desde initApp()
            // cuando ya hay sesión. Si arrancamos en DOMContentLoaded sin token,
            // _pollOnce retorna temprano y el badge queda vacío hasta 30s.
        },

        startPolling: function () {
            this.stopPolling();
            this._pollOnce();  // fetch inicial — el badge aparece ya, no en 30s
            this._pollTimer = setInterval(() => {
                this._pollOnce();
            }, this._pollMs);
        },

        stopPolling: function () {
            if (this._pollTimer) {
                clearInterval(this._pollTimer);
                this._pollTimer = null;
            }
        },

        _pollOnce: async function () {
            if (!window.Session || !window.Session.getToken()) return;
            try {
                const res = await window.NotificacionModel.countOnly();
                if (res.status === 'success') {
                    window.NotificacionView.renderBadge(res.no_leidas || 0);
                }
            } catch (e) {
                // Silencioso.
            }
        },

        toggleDropdown: async function () {
            const dd = document.getElementById('notif-dropdown');
            if (!dd) return;
            const isOpen = dd.style.display === 'block';
            if (isOpen) {
                window.NotificacionView.toggleDropdown(false);
                return;
            }
            try {
                const res = await window.NotificacionModel.load({ page: 1, per_page: 20 });
                if (res.status === 'success') {
                    window.NotificacionView.renderDropdown(res.notificaciones || []);
                    window.NotificacionView.renderBadge(res.no_leidas || 0);
                    window.NotificacionView.toggleDropdown(true);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar notificaciones');
            }
        },

        load: async function (page) {
            if (this._loading) return;
            this._loading = true;
            const target = page || window.__notifCurrentPage || 1;
            try {
                const res = await window.NotificacionModel.load({ page: target, per_page: 50 });
                if (res.status !== 'success') {
                    window.Toast.error(res.message || 'Error al cargar notificaciones');
                    return;
                }
                window.NotificacionView.renderBadge(res.no_leidas || 0);
                this.renderPage(target);
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar notificaciones');
            } finally {
                this._loading = false;
            }
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const estado = this._readEstado();
            const normalized = window.normalizeForSearch(search || '');

            let items = window.NotificacionModel.cache.slice();
            if (estado === 'no leido') {
                items = items.filter(n => n.leida_en == null);
            } else if (estado === 'leido') {
                items = items.filter(n => n.leida_en != null);
            }
            if (normalized) {
                items = items.filter(n => window.normalizeForSearch(
                    (n.titulo || '') + ' ' + (n.mensaje || '') + ' ' + (n.tipo_evento || '')
                ).includes(normalized));
            }

            const size = 50;
            const totalPages = Math.max(1, Math.ceil(items.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__notifCurrentPage = safePage;
            this._persist(search, estado, safePage);
            window.NotificacionView.renderTable(items.slice(start, start + size));
            window.NotificacionView.renderPagination(safePage, totalPages);
            window.NotificacionView.updateCounter(items.length, safePage, size);
        },

        markRead: async function (id, url) {
            if (!id) return;
            try {
                await window.NotificacionModel.markRead(id);
            } catch (e) {
                window.Toast.error(e.message || 'Error al marcar como leída');
                return;
            }

            window.NotificacionView.toggleDropdown(false);
            if (window.NotificacionModel.lastCount > 0) {
                window.NotificacionModel.lastCount = Math.max(0, window.NotificacionModel.lastCount - 1);
            }
            window.NotificacionView.renderBadge(window.NotificacionModel.lastCount);
            this._navigate(url);
        },

        markAllRead: async function () {
            try {
                const res = await window.NotificacionModel.markAllRead();
                if (res.status !== 'success') {
                    window.Toast.error(res.message || 'Error al marcar todas como leídas');
                    return;
                }
                window.Toast.success(res.message || 'Notificaciones marcadas como leídas');
                window.NotificacionModel.lastCount = 0;
                window.NotificacionView.renderBadge(0);
                if (document.getElementById('notificaciones')?.classList.contains('active')) {
                    await this.load(window.__notifCurrentPage || 1);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al marcar todas como leídas');
            }
        },

        _navigate: function (url) {
            if (!url || !window.Router) {
                window.Router.showPage('notificaciones');
                return;
            }
            const m = String(url).match(/^\/([a-z_-]+)(?:\?id=(\d+))?$/);
            if (!m) {
                window.Router.showPage('notificaciones');
                return;
            }
            const page = m[1];
            const id = m[2] ? parseInt(m[2], 10) : null;
            const controllers = {
                tickets: window.TicketController,
                pagos: window.PagoController,
                facturas: window.FacturaController
            };
            window.Router.showPage(page);
            if (id && controllers[page] && typeof controllers[page].edit === 'function') {
                setTimeout(function () { controllers[page].edit(id); }, 300);
            }
        },

        _readSearch: function () {
            const input = document.getElementById('notificaciones-search');
            return input ? input.value : '';
        },

        _readEstado: function () {
            const sel = document.getElementById('notificaciones-estado');
            return sel ? sel.value : '';
        },

        _persist: function (search, estado, page) {
            try {
                sessionStorage.setItem('redytelca.notificaciones.search', search || '');
                sessionStorage.setItem('redytelca.notificaciones.estado', estado || '');
                sessionStorage.setItem('redytelca.notificaciones.page', String(page || 1));
            } catch (e) { /* no-op */ }
        },

        _restore: function () {
            try {
                const s = sessionStorage.getItem('redytelca.notificaciones.search');
                const e = sessionStorage.getItem('redytelca.notificaciones.estado');
                const p = sessionStorage.getItem('redytelca.notificaciones.page');
                const input = document.getElementById('notificaciones-search');
                const sel = document.getElementById('notificaciones-estado');
                if (input && s) input.value = s;
                if (sel && e) sel.value = e;
                if (p) window.__notifCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window.NotificacionController) window.NotificacionController.init();
    });
})();