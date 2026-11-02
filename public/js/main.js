// js/main.js
(function () {
    'use strict';

    window.__redytelca = { version: 'fase-1-mvc' };

    window.initApp = async function () {
        const safe = async (fn) => {
            if (typeof fn === 'function') {
                try { await fn(); } catch (e) { console.warn('[initApp]', e); }
            }
        };
        await Promise.all([
            safe(() => window.ServicioController ? window.ServicioController.load() : null),
            safe(() => window.ConfiguracionEmpresaController ? window.ConfiguracionEmpresaController.loadBranding() : null)
        ]);

        // Dashboard: se carga después del Promise.all de arriba para que
        // ServicioModel ya esté poblado y no se duplique el fetch.
        // Al login, _resetToDashboard() activa #dash directamente sin pasar
        // por Router.showPage, así que el onShow no se dispara. Por eso
        // DashboardController.load() se invoca aquí.
        await safe(() => window.DashboardController ? window.DashboardController.load() : null);

        // Notificaciones: arrancar el polling con fetch inicial (badge
        // visible en ≤2s, no en 30s). Solo se llama aquí, después del login
        // o del restore, no en DOMContentLoaded (sin sesión).
        await safe(() => window.NotificacionController ? window.NotificacionController.startPolling() : null);

        // Reportes NO se precarga aquí. Solo se carga cuando el usuario
        // navega a #reportes vía Router.showPage → onShow. Reportes es
        // una vista de consulta con filtro opcional (B3.15), no el
        // destino post-login. Pre-cargarlo sin filtro serían 4 fetches
        // (facturas, pagos, tickets, tareas) innecesarios en cada login.
    };

    function smokeTest() {
        const session = window.Session.get();
        console.info('[REDYTELCA] Session state:', {
            authenticated: window.Session.isAuthenticated(),
            user: session ? session.user : null,
            modules: session && session.modules ? `${session.modules.length} módulos` : 'sin módulos'
        });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        document.getElementById('login-button')?.addEventListener('click', () => window.AuthController.login());
        document.getElementById('user')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('pass')?.focus();
            }
        });
        document.getElementById('pass')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.AuthController.login();
            }
        });
        document.querySelector('.sidebar-toggle')?.addEventListener('click', () => window.Router.toggleSidebar());
        document.querySelector('.profile-button')?.addEventListener('click', () => window.Router.toggleProfileMenu());

        if (window.Router) {
            if (window.DashboardController) window.Router.register('dash', { onShow: () => window.DashboardController.load() });
            if (window.ClienteController) window.Router.register('clientes', { onShow: () => window.ClienteController.load() });
            if (window.PlanController) window.Router.register('planes', { onShow: () => window.PlanController.load() });
            if (window.NodoController) window.Router.register('nodos', { onShow: () => window.NodoController.load() });
            if (window.OltController) window.Router.register('olts', { onShow: () => window.OltController.load() });
            if (window.NapController) window.Router.register('naps', { onShow: () => window.NapController.load() });
            if (window.ServicioController) window.Router.register('servicios', { onShow: () => window.ServicioController.load() });
            if (window.EquipoController) window.Router.register('equipos', { onShow: () => window.EquipoController.load() });
            if (window.RolController) window.Router.register('roles', { onShow: () => window.RolController.load() });
            if (window.PermisoController) window.Router.register('modulos', { onShow: () => window.PermisoController.load() });
            if (window.UsuarioController) window.Router.register('usuarios', { onShow: () => window.UsuarioController.load() });
            if (window.FacturaController) window.Router.register('facturas', { onShow: () => window.FacturaController.load() });
            if (window.PagoController) window.Router.register('pagos', { onShow: () => window.PagoController.load() });
            if (window.ContratoController) window.Router.register('contratos', { onShow: () => window.ContratoController.load() });
            if (window.TicketController) window.Router.register('tickets', { onShow: () => window.TicketController.load() });
            if (window.CategoriaTicketController) window.Router.register('categorias-ticket', { onShow: () => window.CategoriaTicketController.load() });
            if (window.SlaDefinicionController) window.Router.register('sla-definiciones', { onShow: () => window.SlaDefinicionController.load() });
            if (window.AuditoriaLogController) window.Router.register('auditoria', { onShow: () => window.AuditoriaLogController.load() });
            if (window.TareaController) window.Router.register('tareas', { onShow: () => window.TareaController.load() });
            if (window.PedidoController) window.Router.register('pedidos', { onShow: () => window.PedidoController.load() });
            if (window.ConfiguracionEmpresaController) window.Router.register('empresa', { onShow: () => window.ConfiguracionEmpresaController.loadFull() });
            if (window.PerfilController) window.Router.register('perfil', { onShow: () => window.PerfilController.load() });
            if (window.MapaController) window.Router.register('mapa', { onShow: () => window.MapaController.load() });
            if (window.ReportesController) window.Router.register('reportes', { onShow: () => window.ReportesController.load() });
            if (window.NotificacionController) window.Router.register('notificaciones', { onShow: () => window.NotificacionController.load() });
        }

        if (window.Session && window.AuthController) {
            window.Session.onClear(() => {
                window.selectedIds = {};
                window.permissionContext = { authenticated: false, user: '', roles: [], roleName: '', token: '', modules: [], must_change_password: false };
                if (window.ClienteModel) window.ClienteModel.cache = [];
                if (window.ServicioModel) window.ServicioModel.cache = [];
                if (window.NodoModel) window.NodoModel.cache = [];
                if (window.OltModel) window.OltModel.cache = [];
                if (window.NapModel) window.NapModel.cache = [];
                if (window.EquipoModel) window.EquipoModel.cache = [];
                if (window.PlanModel) window.PlanModel.cache = [];
                if (window.RolModel) window.RolModel.cache = [];
                if (window.PermisoModel) window.PermisoModel.cache = { modulos: [], permisos_catalogo: [], lastIdRol: null };
                if (window.ConfiguracionEmpresaModel) window.ConfiguracionEmpresaModel.cache = { publico: null, completo: null };
                if (window.UsuarioModel) window.UsuarioModel.cache = [];
                if (window.FacturaModel) { window.FacturaModel.cache = []; window.FacturaModel.cacheLineas = {}; }
                if (window.PagoModel) window.PagoModel.cache = [];
                if (window.MetodoPagoModel) window.MetodoPagoModel.cache = [];
                if (window.ContratoModel) window.ContratoModel.cache = [];
                if (window.TicketModel) { window.TicketModel.cache = []; window.TicketModel.categoriasCache = []; window.TicketModel.slaCache = []; }
                if (window.CategoriaTicketModel) window.CategoriaTicketModel.cache = [];
                if (window.SlaDefinicionModel) window.SlaDefinicionModel.cache = [];
                if (window.AuditoriaLogModel) window.AuditoriaLogModel.cache = [];
                if (window.TareaModel) window.TareaModel.cache = [];
                if (window.PedidoModel) window.PedidoModel.cache = [];
                if (window.PerfilModel) window.PerfilModel.cache = { usuario: null, sesiones: [] };

                // Dashboard: destruir instancias de Chart.js para evitar
                // memory leaks y que el próximo login reutilice instancias
                // con datos del usuario anterior. Se resetean los totales
                // del dashboard. Origen: B3.14.
                if (window.DashboardView) window.DashboardView.destroyCharts();
                window.__dashboardTotals = null;

                // Reportes: cache es un OBJETO compuesto (rango + kpis +
                // 4 listados), no un array. Un `cache = []` genérico
                // dejaría el cache como array vacío y rompería
                // `ReporteModel.get().facturas` en el próximo login.
                // `reset()` restaura la forma correcta. Origen: B3.15.
                if (window.ReporteModel) window.ReporteModel.reset();

                // 086c — Notificaciones: limpiar cache, lastCount y detener
                // el polling. El polling se reanuda en el siguiente
                // DOMContentLoaded (F5) o queda inactivo hasta el próximo
                // login en la misma sesión de pestaña.
                if (window.NotificacionModel) {
                    window.NotificacionModel.cache = [];
                    window.NotificacionModel.lastCount = 0;
                }
                if (window.NotificacionController) window.NotificacionController.stopPolling();
                if (window.NotificacionView) window.NotificacionView.renderBadge(0);

                document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
                document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
                const dash = document.getElementById('dash');
                if (dash) dash.classList.add('active');
                const titleEl = document.getElementById('page-title');
                const eyebrowEl = document.getElementById('page-eyebrow');
                if (titleEl) titleEl.textContent = 'Dashboard';
                if (eyebrowEl) eyebrowEl.textContent = 'Análisis';
                document.querySelectorAll('tr.selected-row').forEach(r => r.classList.remove('selected-row'));
                const profileMenu = document.getElementById('profile-menu');
                if (profileMenu) profileMenu.classList.remove('show');
            });
        }

        const overlay = document.getElementById('login-overlay');
        const stored = window.Session ? window.Session.get() : null;
        if (!stored || !stored.token) {
            if (overlay) overlay.style.display = 'flex';
            document.documentElement.classList.add('login-visible');
        }

        if (window.ConfiguracionEmpresaController) {
            try { await window.ConfiguracionEmpresaController.loadBranding(); } catch (e) {}
        }

        const restored = await window.AuthController.restoreSession();
        if (!restored) {
            if (overlay) overlay.style.display = 'flex';
            document.documentElement.classList.add('login-visible');
        }
        if (restored) await window.initApp();

        document.querySelectorAll('.toolbar-btn[data-action="new"]').forEach(b => { b.disabled = false; });
        setTimeout(smokeTest, 500);
    });

    window.login = () => window.AuthController.login();
    window.logout = () => window.AuthController.logout();
    window.showPage = (pageId, el) => window.Router.showPage(pageId, el);
    window.toggleSidebar = () => window.Router.toggleSidebar();
    window.toggleProfileMenu = () => window.Router.toggleProfileMenu();
    window.toggleNavGroup = (el) => window.Router.toggleNavGroup(el);
    window.selectProfileMenuPage = (id) => window.Router.selectProfileMenuPage(id);
    window.renderMenu = () => window.Router.renderMenu();

    window.checkMustChangePassword = function () {
        if (window.AuthController && typeof window.AuthController.checkMustChangePassword === 'function') {
            window.AuthController.checkMustChangePassword();
        }
    };

    window.__redytelca.caches = () => ({
        nuevo: {
            clientes: window.ClienteModel ? window.ClienteModel.cache.length : -1,
            servicios: window.ServicioModel ? window.ServicioModel.cache.length : -1,
            nodos: window.NodoModel ? window.NodoModel.cache.length : -1,
            olts: window.OltModel ? window.OltModel.cache.length : -1,
            naps: window.NapModel ? window.NapModel.cache.length : -1,
            equipos: window.EquipoModel ? window.EquipoModel.cache.length : -1,
            planes: window.PlanModel ? window.PlanModel.cache.length : -1,
            roles: window.RolModel ? window.RolModel.cache.length : -1,
            permiso_modulos: window.PermisoModel ? window.PermisoModel.cache.modulos.length : -1,
            usuarios: window.UsuarioModel ? window.UsuarioModel.cache.length : -1,
            facturas: window.FacturaModel ? window.FacturaModel.cache.length : -1,
            pagos: window.PagoModel ? window.PagoModel.cache.length : -1,
            metodos_pago: window.MetodoPagoModel ? window.MetodoPagoModel.cache.length : -1,
            contratos: window.ContratoModel ? window.ContratoModel.cache.length : -1,
            tickets: window.TicketModel ? window.TicketModel.cache.length : -1,
            categorias_ticket: window.TicketModel ? (window.TicketModel.categoriasCache ? window.TicketModel.categoriasCache.length : -1) : -1,
            sla_definiciones: window.TicketModel ? (window.TicketModel.slaCache ? window.TicketModel.slaCache.length : -1) : -1,
            tareas: window.TareaModel ? window.TareaModel.cache.length : -1,
            pedidos: window.PedidoModel ? window.PedidoModel.cache.length : -1,
            auditoria_logs: window.AuditoriaLogModel ? window.AuditoriaLogModel.cache.length : -1,
            notificaciones: window.NotificacionModel ? window.NotificacionModel.cache.length : -1,
            notificaciones_no_leidas: window.NotificacionModel ? window.NotificacionModel.lastCount : -1,
            perfil_usuario: (window.PerfilModel && window.PerfilModel.cache.usuario) ? 'set' : 'null',
            perfil_sesiones: window.PerfilModel ? window.PerfilModel.cache.sesiones.length : -1,
            dashboard_totals_clientes: window.__dashboardTotals ? window.__dashboardTotals.clientes : -1,
            reportes_rango: (window.ReporteModel && window.ReporteModel.cache.rango)
                ? (window.ReporteModel.cache.rango.con_filtro
                    ? `${window.ReporteModel.cache.rango.desde || '?'} → ${window.ReporteModel.cache.rango.hasta || '?'}`
                    : 'sin filtro')
                : 'null',
            reportes_kpis: (window.ReporteModel && window.ReporteModel.cache.kpis) ? 'set' : 'null',
            reportes_facturas: window.ReporteModel ? window.ReporteModel.cache.facturas.length : -1,
            reportes_pagos: window.ReporteModel ? window.ReporteModel.cache.pagos.length : -1,
            reportes_tickets: window.ReporteModel ? window.ReporteModel.cache.tickets.length : -1,
            reportes_tareas: window.ReporteModel ? window.ReporteModel.cache.tareas.length : -1
        }
    });

    // -----------------------------------------------------------------
    // Drawer mobile — JS mínimo.
    //
    // Los estilos viven en styles.css (media queries). Este bloque solo
    // hace lo que CSS no puede:
    //   1. Crear el <div class="sidebar-scrim">. CSS no crea elementos.
    //   2. Click handler del scrim → cierra el drawer.
    //   3. En carga mobile, colapsar el sidebar por default.
    //   4. Detectar el cruce desktop↔mobile. CSS @media no distingue
    //      "carga inicial en mobile" de "resize desktop→mobile", y el
    //      sidebar necesita colapsarse en el segundo caso.
    // -----------------------------------------------------------------
    (function initMobileDrawer() {
        function collapseSidebar() {
            const s = document.querySelector('.sidebar');
            const m = document.querySelector('.main-content');
            if (s) s.classList.add('collapsed');
            if (m) m.classList.add('expanded');
        }

        function bootstrap() {
            if (!document.getElementById('sidebar-scrim')) {
                const scrim = document.createElement('div');
                scrim.id = 'sidebar-scrim';
                scrim.className = 'sidebar-scrim';
                scrim.addEventListener('click', collapseSidebar);
                document.body.appendChild(scrim);
            }
            const mql = window.matchMedia('(max-width: 768px)');
            if (mql.matches) collapseSidebar();
            mql.addEventListener('change', function (e) {
                if (e.matches) collapseSidebar();
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootstrap);
        } else {
            bootstrap();
        }
    })();
})();