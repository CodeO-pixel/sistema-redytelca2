// js/core/Router.js
(function () {
    'use strict';

    const pages = new Map();

    const SECTION_MAP = {
        'Análisis': ['dash', 'reportes'],
        'Operación': ['clientes', 'servicios', 'mapa', 'tareas', 'tickets', 'pedidos', 'notificaciones'],
        'Infraestructura': ['nodos', 'olts', 'naps', 'equipos'],
        'Finanzas': ['facturas', 'pagos', 'planes', 'contratos'],
        'Configuración': ['roles', 'modulos', 'empresa', 'usuarios', 'perfil', 'password', 'auditoria']
    };

    const DEFAULT_TITLES = {
        dash: { t: 'Dashboard', e: 'Análisis' },
        reportes: { t: 'Reportes', e: 'Analítica' },
        clientes: { t: 'Clientes', e: 'Gestión' },
        servicios: { t: 'Servicios', e: 'Operación' },
        mapa: { t: 'Mapa técnico', e: 'Infraestructura' },
        tareas: { t: 'Control de tareas', e: 'Control' },
        tickets: { t: 'Tickets', e: 'Soporte' },
        pedidos: { t: 'Pedidos de campo', e: 'Operación' },
        notificaciones: { t: 'Notificaciones', e: 'Operación' },
        nodos: { t: 'Nodos', e: 'Infraestructura' },
        olts: { t: 'OLTs', e: 'Infraestructura' },
        naps: { t: 'NAPs', e: 'Infraestructura' },
        equipos: { t: 'Equipos', e: 'Infraestructura' },
        facturas: { t: 'Facturación', e: 'Finanzas' },
        pagos: { t: 'Pagos', e: 'Finanzas' },
        planes: { t: 'Planes', e: 'Finanzas' },
        contratos: { t: 'Contratos', e: 'Finanzas' },
        roles: { t: 'Roles', e: 'Configuración' },
        modulos: { t: 'Módulos y permisos', e: 'Configuración' },
        empresa: { t: 'Empresa', e: 'Configuración' },
        usuarios: { t: 'Usuarios', e: 'Configuración' },
        auditoria: { t: 'Auditoría', e: 'Configuración' },
        perfil: { t: 'Mi perfil', e: 'Configuración' }
    };

    function escapeHtml(unsafe) {
        return String(unsafe || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setArrow(group, expanded) {
        const arrow = group ? group.querySelector('.nav-group-arrow') : null;
        if (arrow) arrow.textContent = expanded ? '▾' : '▸';
    }

    function ensureVisible(el) {
        if (!el || typeof el.scrollIntoView !== 'function') return;
        try {
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } catch (e) {
            el.scrollIntoView(false);
        }
    }

    function closeAllModals() {
        // Bug B3.7: al navegar de un módulo a otro, los `.modal-overlay`
        // abiertos (position:fixed) quedaban visibles encima de la nueva
        // página. Cerrarlos siempre antes de activar la nueva página.
        // No tocamos `.core-modal-overlay` (Modal.confirm de core) porque
        // su Promise quedaría pendiente.
        document.querySelectorAll('.modal-overlay').forEach(function (m) {
            if (m.style.display === 'flex' || m.style.display === 'block') {
                m.style.display = 'none';
            }
        });
    }

    window.Router = {
        register: function (pageId, config) {
            pages.set(pageId, config);
        },
        listRegistered: function () {
            return Array.from(pages.keys());
        },
        renderMenu: function (autoNavigate = false) {
            const container = document.getElementById('sidebar-nav');
            if (!container) return;

            const session = window.Session ? window.Session.get() : null;
            const modules = session ? (session.modules || []) : [];

            const items = [];
            const seen = new Set();

            modules.forEach(m => {
                let pageId = String(m.url || m.vista || '').toLowerCase();
                if (pageId === 'permisos') pageId = 'modulos';

                if (pageId && !seen.has(pageId) && pageId !== 'password' && pageId !== 'notificaciones') {
                    let section = 'Operación';
                    for (const [sec, pgs] of Object.entries(SECTION_MAP)) {
                        if (pgs.includes(pageId)) {
                            section = sec;
                            break;
                        }
                    }
                    items.push({
                        page: pageId,
                        label: m.vista || m.url,
                        module: section
                    });
                    seen.add(pageId);
                }
            });

            if (items.length === 0) {
                container.innerHTML = '<div class="nav-item">Sin módulos</div>';
            } else {
                const groups = { 'Análisis': [], 'Operación': [], 'Infraestructura': [], 'Finanzas': [], 'Configuración': [] };
                items.forEach(item => {
                    if (groups[item.module]) groups[item.module].push(item);
                });

                const activePageBefore = document.querySelector('.page.active') ? document.querySelector('.page.active').id : null;

                container.innerHTML = Object.keys(groups).filter(k => groups[k].length > 0).map(groupName => `
                    <div class="nav-group collapsible expanded">
                        <div class="nav-group-title" data-nav-group>
                            ${escapeHtml(groupName)} <span class="nav-group-arrow">▾</span>
                        </div>
                        ${groups[groupName].map(item => `
                            <div class="nav-item" data-page="${escapeHtml(item.page)}">
                                ${escapeHtml(item.label)}
                            </div>
                        `).join('')}
                    </div>
                `).join('');

                container.querySelectorAll('[data-nav-group]').forEach(el => {
                    el.addEventListener('click', () => {
                        const group = el.closest('.nav-group');
                        if (!group) return;
                        const exp = group.classList.toggle('expanded');
                        setArrow(group, exp);
                        requestAnimationFrame(() => ensureVisible(el));
                    });
                });

                container.querySelectorAll('.nav-item[data-page]').forEach(el => {
                    el.addEventListener('click', () => window.Router.showPage(el.dataset.page, el));
                });

                if (autoNavigate) {
                    const first = container.querySelector('.nav-item');
                    if (first) this.showPage(first.dataset.page, first);
                } else if (activePageBefore) {
                    const activeNav = container.querySelector(`.nav-item[data-page="${activePageBefore}"]`);
                    if (activeNav) activeNav.classList.add('active');
                }
            }

            const profileMenu = document.getElementById('profile-menu');
            if (profileMenu) {
                profileMenu.innerHTML = `
                    <button type="button" data-profile-action="perfil">Mi perfil</button>
                    <button type="button" data-profile-action="password">Cambiar contraseña</button>
                    <button type="button" data-profile-action="logout">Cerrar sesión</button>
                `;
                profileMenu.querySelectorAll('[data-profile-action]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const action = btn.dataset.profileAction;
                        if (action === 'logout') {
                            window.logout();
                        } else {
                            window.Router.selectProfileMenuPage(action);
                        }
                    });
                });
            }
        },
        showPage: function (pageId, element = null) {
            const session = window.Session ? window.Session.get() : null;
            if (session && session.must_change_password) {
                if (window.AuthController && typeof window.AuthController.checkMustChangePassword === 'function') {
                    window.AuthController.checkMustChangePassword();
                } else if (typeof window.checkMustChangePassword === 'function') {
                    window.checkMustChangePassword();
                }
                return;
            }

            closeAllModals();

            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

            const pageEl = document.getElementById(pageId);
            if (pageEl) pageEl.classList.add('active');

            // Reset scroll. Sin esto, el scroll del documento (o de
            // .main-content) se conserva entre páginas y la nueva aparece
            // "ya scrolleada" (bug Dashboard <-> Reportes, 2026-10-31).
            window.scrollTo(0, 0);
            const mainContent = document.querySelector('.main-content');
            if (mainContent) mainContent.scrollTop = 0;

            const navEl = element || document.querySelector(`.nav-item[data-page="${pageId}"]`);
            if (navEl) {
                navEl.classList.add('active');
                const group = navEl.closest('.nav-group');
                if (group) {
                    group.classList.add('expanded');
                    setArrow(group, true);
                }
                ensureVisible(navEl);
            }

            const info = DEFAULT_TITLES[pageId] || { t: 'Panel', e: 'Operación' };
            const titleEl = document.getElementById('page-title');
            const eyebrowEl = document.getElementById('page-eyebrow');
            if (titleEl) titleEl.textContent = info.t;
            if (eyebrowEl) eyebrowEl.textContent = info.e;

            if (window.selectedIds) {
                window.selectedIds[pageId] = null;
            }

            const pageConfig = pages.get(pageId);
            if (pageConfig) {
                if (typeof pageConfig.clearSelectedIds === 'function') {
                    pageConfig.clearSelectedIds();
                }
                if (typeof pageConfig.onShow === 'function') {
                    try {
                        pageConfig.onShow();
                    } catch (e) {
                        console.error(`[Router] Error in onShow for page ${pageId}`, e);
                    }
                }
            }

            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar')?.classList.add('collapsed');
                document.querySelector('.main-content')?.classList.add('expanded');
            }
        },
        toggleSidebar: function () {
            document.querySelector('.sidebar')?.classList.toggle('collapsed');
            document.querySelector('.main-content')?.classList.toggle('expanded');
            if (document.getElementById('mapa')?.classList.contains('active')) {
                setTimeout(() => {
                    if (window.leafletMapInstance) window.leafletMapInstance.invalidateSize();
                }, 250);
            }
        },
        toggleNavGroup: function (el) {
            const group = el.closest('.nav-group');
            if (!group) return;
            const exp = group.classList.toggle('expanded');
            setArrow(group, exp);
        },
        toggleProfileMenu: function () {
            document.getElementById('profile-menu')?.classList.toggle('show');
        },
        selectProfileMenuPage: function (pageId) {
            if (pageId === 'password') {
                const modal = document.getElementById('password-modal');
                const closeBtn = document.getElementById('password-modal-close');
                if (modal) modal.style.display = 'flex';
                if (closeBtn) closeBtn.style.display = 'block';
                const userInput = document.getElementById('password-user');
                const session = window.Session ? window.Session.get() : null;
                if (userInput) userInput.value = session ? session.user : '';
                this.toggleProfileMenu();
                return;
            }
            this.showPage(pageId);
            this.toggleProfileMenu();
        }
    };
})();