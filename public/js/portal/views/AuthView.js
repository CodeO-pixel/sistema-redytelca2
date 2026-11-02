// js/portal/views/AuthView.js
// Maneja el DOM del login overlay, la app, y el modal de contraseña.
// No hace fetch: solo renderiza y delega en PortalAuthController.
//
// B5 (2026-11-02): `togglePasswordModal(true, ...)` ahora limpia
// `#portal-password-feedback` al abrir. Aunque el flujo normal ya lo
// limpiaba en el `else` (close), la limpieza defensiva al abrir cubre
// casos borde: reopen tras `must_change_password=1` sin pasar por
// close, reopen tras 401 (onClear → showLogin → si el usuario abre
// el modal de password desde el menú sin haber cerrado el anterior),
// y reopen tras timeout de la pestaña. Estándar de la casa: "cada
// apertura de modal parte de un estado limpio".
(function () {
    'use strict';

    function _attachLoginListeners() {
        var form = document.getElementById('portal-login-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var u = document.getElementById('portal-username');
                var p = document.getElementById('portal-password');
                window.PortalAuthController.login(
                    u ? u.value.trim() : '',
                    p ? p.value.trim() : ''
                );
            });
        }

        // Enter en usuario → foco a contraseña.
        var userEl = document.getElementById('portal-username');
        if (userEl) {
            userEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var passEl = document.getElementById('portal-password');
                    if (passEl) passEl.focus();
                }
            });
        }
    }

    function _attachPasswordModalListeners() {
        var form = document.getElementById('portal-password-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var current = document.getElementById('portal-password-current');
                var next = document.getElementById('portal-password-new');
                var confirm = document.getElementById('portal-password-confirm');
                window.PortalAuthController.changePassword(
                    current ? current.value : '',
                    next ? next.value : '',
                    confirm ? confirm.value : ''
                );
            });
        }

        var closeBtn = document.getElementById('portal-password-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                window.PortalAuthView.togglePasswordModal(false, false);
            });
        }

        var cancelBtn = document.getElementById('portal-password-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                window.PortalAuthView.togglePasswordModal(false, false);
            });
        }

        // Escape cierra solo si NO es bloqueante.
        var modal = document.getElementById('portal-password-modal');
        if (modal) {
            modal.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    var forced = document.getElementById('portal-password-forced-msg');
                    if (forced && forced.style.display !== 'none') return; // bloqueante
                    window.PortalAuthView.togglePasswordModal(false, false);
                }
            });
        }
    }

    function _attachProfileListeners() {
        var profileBtn = document.getElementById('portal-profile-button');
        if (profileBtn) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                window.PortalRouter.toggleProfileMenu();
            });
        }

        // Click fuera del menú lo cierra.
        document.addEventListener('click', function (e) {
            var menu = document.getElementById('portal-profile-menu');
            var btn = document.getElementById('portal-profile-button');
            if (!menu || !btn) return;
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // Construir el menú de perfil.
        var menu = document.getElementById('portal-profile-menu');
        if (menu) {
            menu.innerHTML = ''
                + '<button type="button" data-portal-action="password">Cambiar contraseña</button>'
                + '<button type="button" data-portal-action="logout">Cerrar sesión</button>';
            menu.querySelectorAll('[data-portal-action]').forEach(function (b) {
                b.addEventListener('click', function () {
                    window.PortalRouter.selectProfileMenuAction(b.dataset.portalAction);
                });
            });
        }
    }

    window.PortalAuthView = {
        /**
         * Muestra el login overlay y oculta la app.
         */
        showLogin: function () {
            var overlay = document.getElementById('portal-login-overlay');
            var app = document.getElementById('portal-app');
            if (overlay) overlay.style.display = 'flex';
            if (app) app.style.display = 'none';
            document.documentElement.classList.add('portal-login-visible');
            document.documentElement.removeAttribute('data-portal-session');
        },

        /**
         * Oculta el login overlay y muestra la app.
         */
        hideLogin: function () {
            var overlay = document.getElementById('portal-login-overlay');
            var app = document.getElementById('portal-app');
            if (overlay) overlay.style.display = 'none';
            if (app) app.style.display = 'flex';
            document.documentElement.classList.remove('portal-login-visible');
            document.documentElement.setAttribute('data-portal-session', '1');
        },

        /**
         * Alias de hideLogin para legibilidad en main.js.
         */
        showApp: function () {
            this.hideLogin();
        },

        /**
         * Abre/cierra el modal de contraseña.
         *
         * @param {boolean} open
         * @param {boolean} forced - true si must_change_password=1.
         *   Bloqueante: no se puede cerrar con ×, Escape, ni Cancelar.
         *   El backend rechaza cualquier otro endpoint con 403.
         */
        togglePasswordModal: function (open, forced) {
            var modal = document.getElementById('portal-password-modal');
            if (!modal) return;

            var forcedMsg = document.getElementById('portal-password-forced-msg');
            var closeBtn = document.getElementById('portal-password-close');
            var cancelBtn = document.getElementById('portal-password-cancel');
            var userEl = document.getElementById('portal-password-user');
            var feedback = document.getElementById('portal-password-feedback');
            var session = window.PortalSession ? window.PortalSession.get() : null;

            if (open) {
                modal.style.display = 'flex';
                // B5: limpiar feedback de intentos anteriores. Defensa
                // en profundidad: el `else` (close) ya limpia, pero el
                // clear al abrir cubre reopen sin haber cerrado
                // (p. ej. must_change_password=1 → error → reopen).
                if (feedback) feedback.textContent = '';

                var isForced = forced === true || (session && session.must_change_password);
                if (forcedMsg) forcedMsg.style.display = isForced ? 'block' : 'none';
                if (closeBtn) closeBtn.style.display = isForced ? 'none' : 'block';
                if (cancelBtn) cancelBtn.style.display = isForced ? 'none' : 'inline-block';
                if (userEl && session && session.cliente) {
                    userEl.value = session.cliente.username || session.cliente.correo || '';
                }
                // Autofocus en contraseña actual.
                var currentEl = document.getElementById('portal-password-current');
                if (currentEl) setTimeout(function () { currentEl.focus(); }, 60);
            } else {
                // Solo se puede cerrar si no es bloqueante.
                var isForcedClose = forced === true || (session && session.must_change_password);
                if (isForcedClose) return;
                modal.style.display = 'none';
                if (feedback) feedback.textContent = '';
            }
        },

        _attachListeners: function () {
            _attachLoginListeners();
            _attachPasswordModalListeners();
            _attachProfileListeners();

            var toggle = document.getElementById('portal-sidebar-toggle');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    window.PortalRouter.toggleSidebar();
                });
            }
        }
    };
})();