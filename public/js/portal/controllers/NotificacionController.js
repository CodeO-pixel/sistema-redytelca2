// js/portal/controllers/NotificacionController.js
// Controller del portal para notificaciones del cliente (086d + A1 fix).
//
// Orquesta:
//   - PortalNotificacionModel (datos)
//   - PortalNotificacionView (render)
//   - PortalRouter (navegación)
//
// Polling cada 30s con countOnly(). Fetch inicial inmediato.
// Mismo patrón que NotificacionController del staff (086c).
//
// Patrón Sonar: campana con badge, dropdown con lista, click en item
// marca como leída + navega a url_destino. Digitel/Inter (Venezuela):
// buzón de notificaciones en el portal de autogestión.
//
// A1 (2026-11-02): añadidos `load(page)`, `renderPage(page)`,
// `applyFilters()`, `clearFilters()`, `_readSearch()`, `_readEstado()`,
// `_persist()`, `_restore()`. El `View` ya tenía
// `renderTable`/`renderPagination`/`updateCounter` y el CSS ya tenía
// `.portal-notif-table`. Solo faltaba la lógica del Controller.
//
// FIX 2026-11-02 (post-auditoría, reportado en browser):
//   - `renderPage` usaba `window.normalizeForSearch`, que vive en
//     `public/js/core/helpers.js`. El portal NO carga `helpers.js`
//     (solo `ApiClient`, `Toast`, `Modal`, `Pagination` y los módulos
//     del portal). Por eso `normalizeForSearch is not a function` al
//     abrir la página `portal-notificaciones` o al hacer click en
//     "Ver todas". Fix: helper `normalize` inline en el IIFE. Self-
//     contained, sin dependencias externas. Mismo patrón que
//     `ServicioController._filter` (normalize inline).
//
// FIX 4BUGS (2026-11-02):
//   - P2: el buzón del portal es el dropdown de la campana Y la página
//     `portal-notificaciones`. El botón "Ver todas las notificaciones"
//     del dropdown navega a la página.
//   - P3: la limpieza al perder sesión vive en `PortalSession.onClear`
//     (registrado en `js/portal/main.js`), no aquí.
(function () {
    'use strict';

    /**
     * Normaliza un string para búsqueda: minúsculas, sin acentos.
     * Inline (no depende de `window.normalizeForSearch` de
     * `core/helpers.js`, que el portal no carga).
     * Mismo criterio que `ServicioController._filter`.
     */
    function normalize(str) {
        return String(str == null ? '' : str)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    window.PortalNotificacionController = {
        _pollTimer: null,
        _pollMs: 30000,
        _loading: false,
        _searchTimer: null,

        init: function () {
            if (window.PortalNotificacionView) {
                window.PortalNotificacionView._attachListeners();
            }
            this._restore();
            // NO startPolling aquí. Se arranca vía startPolling() desde
            // PortalAuthController/__onPortalLoginSuccess después del
            // login/restore, cuando ya hay sesión.
        },

        /**
         * Arranca el polling. Hace un fetch inicial inmediato para que el
         * badge aparezca en ≤2s, no en 30s.
         */
        startPolling: function () {
            this.stopPolling();
            this._pollOnce();
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

        /**
         * Un tick de polling. Solo actualiza el badge.
         * Silencioso si falla (no interrumpe al usuario).
         */
        _pollOnce: async function () {
            if (!window.PortalSession || !window.PortalSession.getToken()) return;
            try {
                const res = await window.PortalNotificacionModel.countOnly();
                if (res.status === 'success') {
                    window.PortalNotificacionView.renderBadge(res.no_leidas || 0);
                }
            } catch (e) {
                // Silencioso para el usuario. El 401 ya dispara
                // PortalSession.clear() → onClear → showLogin().
            }
        },

        /**
         * Abre/cierra el dropdown. Si abre, carga las últimas 20.
         */
        toggleDropdown: async function () {
            const dd = document.getElementById('portal-notif-dropdown');
            if (!dd) return;
            const isOpen = dd.style.display === 'block';
            if (isOpen) {
                window.PortalNotificacionView.toggleDropdown(false);
                return;
            }
            try {
                const res = await window.PortalNotificacionModel.load({ page: 1, per_page: 20 });
                if (res.status === 'success') {
                    window.PortalNotificacionView.renderDropdown(
                        window.PortalNotificacionModel.cache
                    );
                    window.PortalNotificacionView.renderBadge(res.no_leidas || 0);
                    window.PortalNotificacionView.toggleDropdown(true);
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar notificaciones');
            }
        },

        /**
         * A1: carga la página completa `portal-notificaciones`.
         * Pagina server-side (50/page). Filtros opcionales por estado.
         *
         * @param {number|null} page
         */
        load: async function (page = null) {
            if (this._loading) return;
            this._loading = true;

            const target = page != null ? page : (window.__portalNotifCurrentPage || 1);
            const estado = this._readEstado();

            try {
                const res = await window.PortalNotificacionModel.load({
                    page: target,
                    per_page: 50,
                    estado: estado || undefined
                });
                if (res.status !== 'success') {
                    if (window.Toast) window.Toast.error(res.message || 'Error al cargar notificaciones');
                    return;
                }
                window.PortalNotificacionView.renderBadge(res.no_leidas || 0);
                this.renderPage(target);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar notificaciones');
            } finally {
                this._loading = false;
            }
        },

        /**
         * A1: renderiza la página actual con filtros client-side
         * (búsqueda por texto sobre la página cargada). La paginación
         * es server-side, el filtro de búsqueda es sobre la página.
         */
        renderPage: function (page) {
            const search = this._readSearch();
            const estado = this._readEstado();
            const normalized = normalize(search);

            let items = window.PortalNotificacionModel.cache.slice();
            if (estado === 'no leido') {
                items = items.filter(n => n.leida_en == null);
            } else if (estado === 'leido') {
                items = items.filter(n => n.leida_en != null);
            }
            if (normalized) {
                items = items.filter(n => normalize(
                    (n.titulo || '') + ' ' + (n.mensaje || '') + ' ' + (n.tipo_evento || '')
                ).includes(normalized));
            }

            const total = window.PortalNotificacionModel.total || items.length;
            const pages = window.PortalNotificacionModel.pages || 1;
            const safePage = Math.min(Math.max(1, page || 1), Math.max(1, pages));
            window.__portalNotifCurrentPage = safePage;
            this._persist(search, estado, safePage);
            window.PortalNotificacionView.renderTable(items);
            window.PortalNotificacionView.renderPagination(safePage, pages);
            window.PortalNotificacionView.updateCounter(total, safePage, 50);
        },

        applyFilters: function () {
            this.load(1);
        },

        clearFilters: function () {
            const search = document.getElementById('portal-notif-search');
            const estado = document.getElementById('portal-notif-estado');
            if (search) search.value = '';
            if (estado) estado.value = '';
            this.load(1);
        },

        /**
         * Marca una notificación como leída y navega a url_destino.
         * Si no hay url_destino, abre el modal de detalle.
         */
        markRead: async function (id, url) {
            if (!id) return;

            try {
                await window.PortalNotificacionModel.markRead(id);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al marcar como leída');
                return;
            }

            const notif = window.PortalNotificacionModel.getById(id);
            if (notif) {
                notif.leida_en = notif.leida_en || new Date().toISOString().slice(0, 19).replace('T', ' ');
            }

            if (window.PortalNotificacionModel.lastCount > 0) {
                window.PortalNotificacionModel.lastCount =
                    Math.max(0, window.PortalNotificacionModel.lastCount - 1);
            }
            window.PortalNotificacionView.renderBadge(window.PortalNotificacionModel.lastCount);
            window.PortalNotificacionView.toggleDropdown(false);

            const pageEl = document.getElementById('portal-notificaciones');
            if (pageEl && pageEl.classList.contains('active')) {
                this.load(window.__portalNotifCurrentPage || 1);
            }

            if (url) {
                this.navigateTo(url);
            } else if (notif) {
                window.PortalNotificacionView.renderDetailModal(notif);
            }
        },

        markAllRead: async function () {
            try {
                const res = await window.PortalNotificacionModel.markAllRead();
                if (res.status !== 'success') {
                    if (window.Toast) window.Toast.error(res.message || 'Error al marcar todas como leídas');
                    return;
                }
                if (window.Toast) window.Toast.success(res.message || 'Notificaciones marcadas como leídas');
                window.PortalNotificacionModel.lastCount = 0;
                window.PortalNotificacionView.renderBadge(0);

                const dd = document.getElementById('portal-notif-dropdown');
                if (dd && dd.style.display === 'block') {
                    const r = await window.PortalNotificacionModel.load({ page: 1, per_page: 20 });
                    if (r.status === 'success') {
                        window.PortalNotificacionView.renderDropdown(
                            window.PortalNotificacionModel.cache
                        );
                    }
                }

                const pageEl = document.getElementById('portal-notificaciones');
                if (pageEl && pageEl.classList.contains('active')) {
                    this.load(window.__portalNotifCurrentPage || 1);
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al marcar todas como leídas');
            }
        },

        /**
         * Navega a la url_destino devuelta por el backend.
         * El backend del portal devuelve rutas como:
         *   - /portal/tickets?id=N
         *   - /portal/pagos?id=N
         *   - /portal/facturas?id=N
         */
        navigateTo: function (url) {
            if (!url || !window.PortalRouter) {
                if (window.PortalRouter) window.PortalRouter.showPage('portal-tickets');
                return;
            }

            const m = String(url).match(/^\/portal\/([a-z_-]+)(?:\?id=(\d+))?$/);
            if (!m) {
                window.PortalRouter.showPage('portal-tickets');
                return;
            }

            const page = 'portal-' + m[1];
            const id = m[2] ? parseInt(m[2], 10) : null;

            const registered = window.PortalRouter.listRegistered();
            if (registered.indexOf(page) === -1) {
                window.PortalRouter.showPage('portal-tickets');
                return;
            }

            window.PortalRouter.showPage(page);

            if (!id) return;

            setTimeout(function () {
                if (page === 'portal-tickets' && window.PortalTicketController) {
                    window.PortalTicketController.showDetail(id);
                } else if (page === 'portal-pagos' && window.PortalPagoController) {
                    window.PortalPagoController.showDetail(id);
                } else if (page === 'portal-facturas' && window.PortalFacturaController) {
                    window.PortalFacturaController.showDetail(id);
                }
            }, 350);
        },

        _readSearch: function () {
            const input = document.getElementById('portal-notif-search');
            return input ? input.value : '';
        },

        _readEstado: function () {
            const sel = document.getElementById('portal-notif-estado');
            return sel ? sel.value : '';
        },

        _persist: function (search, estado, page) {
            try {
                sessionStorage.setItem('redytelca.portal.notificaciones.search', search || '');
                sessionStorage.setItem('redytelca.portal.notificaciones.estado', estado || '');
                sessionStorage.setItem('redytelca.portal.notificaciones.page', String(page || 1));
            } catch (e) { /* no-op */ }
        },

        _restore: function () {
            try {
                const s = sessionStorage.getItem('redytelca.portal.notificaciones.search');
                const e = sessionStorage.getItem('redytelca.portal.notificaciones.estado');
                const p = sessionStorage.getItem('redytelca.portal.notificaciones.page');
                const input = document.getElementById('portal-notif-search');
                const sel = document.getElementById('portal-notif-estado');
                if (input && s) input.value = s;
                if (sel && e) sel.value = e;
                if (p) window.__portalNotifCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window.PortalNotificacionController) {
            window.PortalNotificacionController.init();
        }
    });
})();