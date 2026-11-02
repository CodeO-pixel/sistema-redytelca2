// js/models/PerfilModel.js
(function () {
    'use strict';

    window.PerfilModel = {
        cache: {
            usuario: null,
            sesiones: []
        },

        load: async function () {
            const res = await window.ApiClient.get('api_perfil.php');
            if (res.status === 'success') {
                this.cache.usuario = res.usuario || null;
                this.cache.sesiones = Array.isArray(res.sesiones) ? res.sesiones : [];
            }
            return res;
        },

        cambiarPassword: async function (payload) {
            return window.ApiClient.post('cambiar_password.php', {
                username: payload.username,
                password_actual: payload.password_actual,
                password_nueva: payload.password_nueva
            });
        }
    };
})();