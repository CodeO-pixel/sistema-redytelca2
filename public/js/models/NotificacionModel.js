// js/models/NotificacionModel.js
(function () {
    'use strict';

    window.NotificacionModel = {
        cache: [],
        lastCount: 0,

        load: async function (params = {}) {
            const res = await window.ApiClient.get('api_notificaciones.php', params);
            if (res.status === 'success') {
                this.cache = res.notificaciones || [];
                this.lastCount = res.no_leidas || 0;
            }
            return res;
        },

        countOnly: async function () {
            const res = await window.ApiClient.get('api_notificaciones.php', { count_only: 1 });
            if (res.status === 'success') {
                this.lastCount = res.no_leidas || 0;
            }
            return res;
        },

        markRead: async function (id) {
            return window.ApiClient.put('api_notificaciones.php', { id_notificacion: id });
        },

        markAllRead: async function () {
            return window.ApiClient.put('api_notificaciones.php', { mark_all: true });
        },

        getById: function (id) {
            return this.cache.find(n => String(n.id) === String(id)) || null;
        }
    };
})();