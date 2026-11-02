// js/portal/controllers/AuthController.js
// Orquesta login, logout, restore y cambio de contraseña del portal.
// Usa ApiClient (reuso de js/core/ApiClient.js) y PortalSession
// (fork con clave propia).
//
// 086d (2026-11-02): añadido hook `__onPortalLoginSuccess` para arrancar
// el polling de notificaciones tras un login fresco. `_clearSession()`
// ahora detiene el polling y limpia el modelo de notificaciones al
// logout o al fallo de restore.
//
// FIX 4BUGS (2026-11-02):
//   - P3: `_clearSession()` ya NO detiene el polling ni resetea el
//     modelo manualmente. Esa limpieza vive en `PortalSession.onClear`,
//     registrado en `js/portal/main.js`. Ventaja: cubre TODAS las vías
//     de pérdida de sesión (logout, restore fallido y 401 de `ApiClient`),
//     no solo las que pasan por este controller.
(function () {
    'use strict';

    function _setSession(raw) {
        var canonical = {
            authenticated: true,
            cliente: raw.cliente || null,
            token: raw.token || '',
            must_change_password: !!raw.must_change_password
        };
        if (window.PortalSession) window.PortalSession.set(canonical);
        window.permissionContext = canonical;
    }

    function _clearSession() {
        if (window.PortalSession) window.PortalSession.clear();
    }

    function _showLogin() {
        if (window.PortalAuthView) window.PortalAuthView.showLogin();
    }

    function _showApp() {
        if (window.PortalAuthView) window.PortalAuthView.showApp();
    }

    function _hideLogin() {
        if (window.PortalAuthView) window.PortalAuthView.hideLogin();
    }

    function _handleMustChangePassword(session) {
        if (session && session.must_change_password) {
            if (window.PortalAuthView) {
                window.PortalAuthView.togglePasswordModal(true, true);
            }
            return true;
        }
        return false;
    }

    async function login(username, password) {
        var btn = document.getElementById('portal-login-button');
        if (btn && btn.disabled) return;

        var feedback = document.getElementById('portal-login-feedback');
        if (feedback) feedback.textContent = '';

        if (!username || !password) {
            if (feedback) feedback.textContent = 'Ingresa usuario y contraseña.';
            return;
        }

        if (btn) { btn.disabled = true; btn.textContent = 'Ingresando…'; }

        try {
            var data = await window.ApiClient.post('api_portal_login.php', {
                username: username,
                password: password
            });

            if (data.status === 'success') {
                _setSession(data);
                _hideLogin();
                _showApp();

                if (window.PortalRouter) window.PortalRouter.renderMenu();

                var nameEl = document.getElementById('portal-profile-name');
                if (nameEl && data.cliente) {
                    nameEl.textContent = (data.cliente.nombre || '') + ' ' + (data.cliente.apellido || '') || data.cliente.username || 'Cliente';
                }

                if (_handleMustChangePassword(data)) {
                    return;
                }

                if (typeof window.__onPortalLoginSuccess === 'function') {
                    window.__onPortalLoginSuccess();
                }

                if (window.PortalRouter) {
                    var registered = window.PortalRouter.listRegistered();
                    if (registered.length > 0) {
                        window.PortalRouter.showPage(registered[0]);
                    }
                }
            } else {
                if (feedback) feedback.textContent = data.message || 'Usuario o contraseña incorrectos.';
            }
        } catch (e) {
            if (feedback) feedback.textContent = e.message || 'Error de conexión. Intenta de nuevo.';
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Ingresar'; }
        }
    }

    async function logout() {
        try { await window.ApiClient.post('api_portal_logout.php', {}); } catch (e) { /* no-op */ }
        _clearSession();
        if (window.PortalRouter) window.PortalRouter.renderMenu();
        _showLogin();
        var userEl = document.getElementById('portal-username');
        var passEl = document.getElementById('portal-password');
        if (userEl) userEl.value = '';
        if (passEl) passEl.value = '';
    }

    async function restoreSession() {
        var stored = window.PortalSession ? window.PortalSession.get() : null;
        if (!stored || !stored.token) {
            _clearSession();
            return false;
        }

        try {
            var data = await window.ApiClient.get('api_portal_auth_state.php');
            if (data.status === 'success' && data.authenticated) {
                _setSession(data);
                _hideLogin();
                _showApp();

                if (window.PortalRouter) window.PortalRouter.renderMenu();

                var nameEl = document.getElementById('portal-profile-name');
                if (nameEl && data.cliente) {
                    nameEl.textContent = (data.cliente.nombre || '') + ' ' + (data.cliente.apellido || '') || data.cliente.username || 'Cliente';
                }

                if (_handleMustChangePassword(data)) {
                    return true;
                }

                if (window.PortalRouter) {
                    var registered = window.PortalRouter.listRegistered();
                    if (registered.length > 0) {
                        window.PortalRouter.showPage(registered[0]);
                    }
                }
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

    async function changePassword(currentPassword, newPassword, confirmPassword) {
        var feedback = document.getElementById('portal-password-feedback');
        var btn = document.getElementById('portal-password-submit');
        var originalText = btn ? btn.textContent : 'Actualizar contraseña';

        if (feedback) feedback.textContent = '';

        if (!currentPassword) {
            if (feedback) feedback.textContent = 'La contraseña actual es obligatoria.';
            return;
        }
        if (!newPassword || newPassword.length < 8) {
            if (feedback) feedback.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
            return;
        }
        if (newPassword !== confirmPassword) {
            if (feedback) feedback.textContent = 'La confirmación no coincide con la nueva contraseña.';
            return;
        }
        if (newPassword === currentPassword) {
            if (feedback) feedback.textContent = 'La nueva contraseña debe ser distinta a la actual.';
            return;
        }

        if (btn) { btn.disabled = true; btn.textContent = 'Actualizando…'; }

        try {
            var res = await window.ApiClient.post('api_portal_cambiar_password.php', {
                password_actual: currentPassword,
                password_nueva: newPassword
            });

            if (res.status === 'success') {
                if (window.Toast) {
                    window.Toast.success('Contraseña actualizada. Inicia sesión con la nueva contraseña.');
                }
                _clearSession();
                if (window.PortalAuthView) window.PortalAuthView.togglePasswordModal(false, false);
                _showLogin();
                var currentEl = document.getElementById('portal-password-current');
                var newEl = document.getElementById('portal-password-new');
                var confirmEl = document.getElementById('portal-password-confirm');
                if (currentEl) currentEl.value = '';
                if (newEl) newEl.value = '';
                if (confirmEl) confirmEl.value = '';
            } else {
                if (feedback) feedback.textContent = res.message || 'Error al cambiar la contraseña.';
            }
        } catch (e) {
            if (feedback) feedback.textContent = e.message || 'Error al cambiar la contraseña.';
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = originalText; }
        }
    }

    window.PortalAuthController = {
        login: login,
        logout: logout,
        restoreSession: restoreSession,
        changePassword: changePassword
    };
})();