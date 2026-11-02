// js/models/BancoModel.js
(function () {
    'use strict';

    window.BancoModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_bancos.php');
            if (res.status === 'success') this.cache = Array.isArray(res.bancos) ? res.bancos : [];
            return res;
        },

        create: async function (nombre) {
            return window.ApiClient.post('api_bancos.php', { nombre: nombre });
        },

        update: async function (id, activo) {
            return window.ApiClient.put('api_bancos.php', { id: id, activo: activo ? 1 : 0 });
        }
    };
})();
