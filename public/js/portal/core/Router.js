// js/portal/core/Router.js
// Fork de js/core/Router.js para el portal de cliente.
// SECTION_MAP propio (Servicios, Facturas, Pagos, Tickets, Notificaciones).
// Sin roles ni módulos RBAC: el cliente ve todo lo que el backend le
// permite (requireClientAuth). El must_change_password se gestiona a
// nivel de modal bloqueante en AuthView.
//
// A1 (2026-11-02): añadida `portal-notificaciones` a SECTION_MAP y
// DEFAULT_TITLES.
(function () {
    'use strict';

    var pages = new Map();

    var SECTION_MAP = {
        'Operación': ['portal-servicios', 'portal-facturas', 'portal-pagos', 'portal-tickets', 'portal-notificaciones']
    };

    var DEFAULT_TITLES = {
        'portal-servicios': { t: 'Mis servicios', e: 'Operación' },
        'portal-facturas': { t: 'Mis facturas', e: 'Finanzas' },
        'portal-pagos': { t: 'Mis pagos', e: 'Finanzas' },
        'portal-tickets': { t: 'Mis tickets', e: 'Soporte' },
        'portal-notificaciones': { t: 'Notificaciones', e: 'Operación' }
    };

    function escapeHtml(unsafe) {
        return String(unsafe || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closeAllModals() {
        document.querySelectorAll('.portal-modal-overlay').forEach(function (m) {
            if (m.id === 'portal-password-modal') return;
            if (m.style.display === 'flex') m.style.display = 'none';
        });
    }

    function resetScroll() {
        window.scrollTo(0, 0);
        var main = document.getElementById('portal-main');
        if (main) main.scrollTop = 0;
    }

    window.PortalRouter = {
        register: function (pageId, config) {
            pages.set(pageId, config || {});
        },
        listRegistered: function () {
            return Array.from(pages.keys());
        },

        renderMenu: function () {
            var container = document.getElementById('portal-sidebar-nav');
            if (!container) return;

            var registered = Array.from(pages.keys());
            if (registered.length === 0) {
                container.innerHTML = '<div class="portal-nav-item">Sin módulos</div>';
                return;
            }

            var groups = { 'Operación': [] };
            registered.forEach(function (pageId) {
                var section = 'Operación';
                for (var sec in SECTION_MAP) {
                    if (SECTION_MAP[sec].indexOf(pageId) !== -1) { section = sec; break; }
                }
                if (!groups[section]) groups[section] = [];
                groups[section].push(pageId);
            });

            var html = '';
            Object.keys(groups).forEach(function (groupName) {
                if (groups[groupName].length === 0) return;
                html += '<div class="portal-nav-group">';
                html += '<div class="portal-nav-group-title">' + escapeHtml(groupName) + '</div>';
                groups[groupName].forEach(function (pageId) {
                    var info = DEFAULT_TITLES[pageId] || { t: pageId };
                    html += '<div class="portal-nav-item" data-page="' + escapeHtml(pageId) + '">' + escapeHtml(info.t) + '</div>';
                });
                html += '</div>';
            });
            container.innerHTML = html;

            container.querySelectorAll('.portal-nav-item[data-page]').forEach(function (el) {
                el.addEventListener('click', function () {
                    window.PortalRouter.showPage(el.dataset.page);
                });
            });
        },

        showPage: function (pageId) {
            var session = window.PortalSession ? window.PortalSession.get() : null;
            if (session && session.must_change_password) {
                if (window.PortalAuthView) window.PortalAuthView.togglePasswordModal(true, true);
                return;
            }

            closeAllModals();

            document.querySelectorAll('.portal-page').forEach(function (p) { p.classList.remove('active'); });
            document.querySelectorAll('.portal-nav-item').forEach(function (n) { n.classList.remove('active'); });

            var pageEl = document.getElementById(pageId);
            if (pageEl) pageEl.classList.add('active');

            resetScroll();

            var navEl = document.querySelector('.portal-nav-item[data-page="' + pageId + '"]');
            if (navEl) navEl.classList.add('active');

            var info = DEFAULT_TITLES[pageId] || { t: 'Portal', e: 'Operación' };
            var titleEl = document.getElementById('portal-page-title');
            var eyebrowEl = document.getElementById('portal-page-eyebrow');
            if (titleEl) titleEl.textContent = info.t;
            if (eyebrowEl) eyebrowEl.textContent = info.e;

            var pageConfig = pages.get(pageId);
            if (pageConfig && typeof pageConfig.onShow === 'function') {
                try { pageConfig.onShow(); } catch (e) { console.error('[PortalRouter] Error in onShow for ' + pageId, e); }
            }
        },

        toggleSidebar: function () {
            var sidebar = document.getElementById('portal-sidebar');
            var main = document.getElementById('portal-main');
            if (sidebar) sidebar.classList.toggle('collapsed');
            if (main) main.classList.toggle('expanded');
        },

        toggleProfileMenu: function () {
            var menu = document.getElementById('portal-profile-menu');
            if (menu) menu.classList.toggle('show');
        },

        selectProfileMenuAction: function (action) {
            this.toggleProfileMenu();
            if (action === 'password') {
                if (window.PortalAuthView) window.PortalAuthView.togglePasswordModal(true, false);
            } else if (action === 'logout') {
                if (window.PortalAuthController) window.PortalAuthController.logout();
            }
        }
    };
})();