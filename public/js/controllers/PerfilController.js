// js/controllers/PerfilController.js
(function () {
    'use strict';

    window.PerfilController = {
        init: function () {
            if (window.PerfilView) window.PerfilView._attachListeners();
        },

        load: async function () {
            try {
                const res = await window.PerfilModel.load();
                if (res.status !== 'success') {
                    if (window.Toast) window.Toast.error(res.message || 'Error al cargar el perfil');
                    return;
                }
                window.PerfilView.renderPerfil(res.usuario || {}, res.sesiones || []);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar el perfil');
            }
        },

        changePassword: async function () {
            const btn = document.querySelector('#password-modal [data-action="change-password"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Actualizando…'; }

            const usernameEl = document.getElementById('password-user');
            const currentEl = document.getElementById('password-current');
            const newEl = document.getElementById('password-new');
            const confirmEl = document.getElementById('password-confirm');

            const username = usernameEl ? usernameEl.value.trim() : '';
            const passwordActual = currentEl ? currentEl.value : '';
            const passwordNueva = newEl ? newEl.value : '';
            const passwordConfirm = confirmEl ? confirmEl.value : '';

            const resetBtn = () => {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            };

            if (!passwordActual) {
                if (window.Toast) window.Toast.warning('La contraseña actual es obligatoria.');
                resetBtn(); return;
            }
            if (!passwordNueva || passwordNueva.length < 8) {
                if (window.Toast) window.Toast.warning('La nueva contraseña debe tener al menos 8 caracteres.');
                resetBtn(); return;
            }
            if (passwordNueva !== passwordConfirm) {
                if (window.Toast) window.Toast.warning('La confirmación no coincide con la nueva contraseña.');
                resetBtn(); return;
            }

            try {
                const res = await window.PerfilModel.cambiarPassword({
                    username: username,
                    password_actual: passwordActual,
                    password_nueva: passwordNueva
                });

                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success('Contraseña actualizada. Inicia sesión con la nueva contraseña.');
                    const modal = document.getElementById('password-modal');
                    if (modal) modal.style.display = 'none';
                    if (currentEl) currentEl.value = '';
                    if (newEl) newEl.value = '';
                    if (confirmEl) confirmEl.value = '';
                    await window.AuthController.logout();
                } else {
                    if (window.Toast) window.Toast.error(res.message || 'Error al cambiar la contraseña');
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cambiar la contraseña');
            } finally {
                resetBtn();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PerfilController.init());
})();