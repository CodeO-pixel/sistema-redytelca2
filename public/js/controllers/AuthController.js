// js/controllers/AuthController.js
(function () {
    'use strict';

    function _setSession(raw) {
        const canonical = {
            authenticated: true,
            user: raw.usuario || raw.user || '',
            roles: raw.roles || [],
            roleName: raw.rol_nombre || raw.roleName || '',
            token: raw.token || '',
            modules: raw.modulos_permitidos || raw.modules || [],
            must_change_password: !!raw.must_change_password
        };
        if (window.Session) window.Session.set(canonical);
        window.permissionContext = canonical;
    }

    function _clearSession() {
        if (window.Session) window.Session.clear();
    }

    // Limpia todos los caches de los 22 Models. Se dispara en login() cuando
    // los módulos cambian entre dos sesiones del mismo navegador. Equivale al
    // Session.onClear de main.js. Los window.*Cache de B2 fueron eliminados
    // en B5.2.a (2026-10-30) porque nadie los lee.
    function _clearCaches() {
        window.selectedIds = {};
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
        if (window.DashboardView) window.DashboardView.destroyCharts();
        window.__dashboardTotals = null;
        if (window.ReporteModel) window.ReporteModel.reset();
    }

    function _hideOverlay() {
        document.documentElement.removeAttribute('data-has-session');
        document.documentElement.classList.remove('login-visible');
        const el = document.getElementById('login-overlay');
        if (el) el.style.display = 'none';
    }

    function _showOverlay() {
        document.documentElement.removeAttribute('data-has-session');
        document.documentElement.classList.add('login-visible');
        const el = document.getElementById('login-overlay');
        if (el) el.style.display = 'flex';
    }

    function _resetToDashboard() {
        // Tras login/restore, siempre ir a dashboard. Sin esto, la página
        // activa del login anterior sigue visible (bug de re-login).
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        const dash = document.getElementById('dash');
        if (dash) dash.classList.add('active');
        const titleEl = document.getElementById('page-title');
        const eyebrowEl = document.getElementById('page-eyebrow');
        if (titleEl) titleEl.textContent = 'Dashboard';
        if (eyebrowEl) eyebrowEl.textContent = 'Análisis';
        document.querySelectorAll('tr.selected-row').forEach(r => r.classList.remove('selected-row'));
        // Cerrar el menú de perfil si quedó abierto del login anterior.
        const profileMenu = document.getElementById('profile-menu');
        if (profileMenu) profileMenu.classList.remove('show');

        // Reset scroll. Mismo motivo que Router.showPage (bug Dashboard <->
        // Reportes, 2026-10-31). Cubre el caso logout -> login sin F5,
        // donde el documento queda scrolleado de la sesión anterior.
        window.scrollTo(0, 0);
        const mainContent = document.querySelector('.main-content');
        if (mainContent) mainContent.scrollTop = 0;
    }

    function _modulesChanged(oldModules, newModules) {
        return JSON.stringify(oldModules) !== JSON.stringify(newModules);
    }

    function _mensaje(msg, tipo) {
        if (typeof window.Toast !== 'undefined') {
            window.Toast.show(msg, tipo);
        } else {
            alert(msg);
        }
    }

    async function login() {
        const btn = document.getElementById('login-button');
        if (btn && btn.disabled) return;

        const userEl = document.getElementById('user');
        const passEl = document.getElementById('pass');
        const username = userEl ? userEl.value.trim() : '';
        const password = passEl ? passEl.value.trim() : '';

        if (!username || !password) {
            _mensaje('Por favor ingresa usuario y contraseña.', 'warning');
            return;
        }

        if (btn) btn.disabled = true;

        try {
            const data = await window.ApiClient.post('login.php', { username: username, password: password });
            if (data.status === 'success') {
                _setSession(data);
                _resetToDashboard();
                _hideOverlay();
                if (userEl) userEl.value = '';
                if (passEl) passEl.value = '';

                const label = document.getElementById('profile-name-label');
                if (label) label.textContent = data.usuario || data.user || 'Usuario';

                if (window.Router) window.Router.renderMenu();

                if (typeof window.initApp === 'function') {
                    await window.initApp();
                }

                checkMustChangePassword();
            } else {
                _mensaje(data.message || 'Error al iniciar sesión', 'error');
            }
        } catch (e) {
            _mensaje(e.message || 'Error de conexión', 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function logout() {
        try { await window.ApiClient.post('logout.php', {}); } catch (e) {}
        _clearSession();
        if (window.Router) window.Router.renderMenu();
        _showOverlay();
    }

    async function restoreSession(autoNavigate = true) {
        const stored = window.Session ? window.Session.get() : null;
        if (!stored || !stored.token) {
            _clearSession();
            return false;
        }

        try {
            const data = await window.ApiClient.get('auth_state.php');
            if (data.status === 'success' && data.authenticated) {
                const newMods = data.modules || [];
                const oldMods = stored.modules || [];
                if (_modulesChanged(oldMods, newMods)) _clearCaches();

                _setSession(data);
                _resetToDashboard();
                _hideOverlay();

                const label = document.getElementById('profile-name-label');
                if (label) label.textContent = data.user || 'Usuario';

                if (window.Router) window.Router.renderMenu(autoNavigate);

                if (window.ConfiguracionEmpresaController) {
                    try { await window.ConfiguracionEmpresaController.loadBranding(); } catch (e) {}
                }

                checkMustChangePassword();
                return true;
            } else {
                _clearSession();
                return false;
            }
        } catch (e) {
            _clearSession();
            return false;
        }
    }

    function checkMustChangePassword() {
        const s = window.Session ? window.Session.get() : null;
        if (!s || !s.must_change_password) return;

        const modal = document.getElementById('password-modal');
        const closeBtn = document.getElementById('password-modal-close');
        if (modal) modal.style.display = 'flex';
        if (closeBtn) closeBtn.style.display = 'none';

        const userInput = document.getElementById('password-user');
        if (userInput) userInput.value = s.user || '';

        const feedback = document.getElementById('password-feedback');
        if (feedback) {
            feedback.style.color = 'orange';
            feedback.textContent = 'Debes cambiar tu contraseña temporal antes de continuar.';
        }
    }

    window.AuthController = {
        login: login,
        logout: logout,
        restoreSession: restoreSession,
        checkMustChangePassword: checkMustChangePassword
    };
})();