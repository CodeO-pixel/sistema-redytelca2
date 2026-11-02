// js/models/ServicioModel.js
(function () {
    'use strict';

    window.ServicioModel = {
        cache: [],
        load: async function () {
            const res = await window.ApiClient.get('api_servicios.php');
            if (res.status === 'success') {
                this.cache = res.servicios || [];
                return this.cache;
            }
            return [];
        },
        getById: function (id) {
            return this.cache.find(s => String(s.id) === String(id) || String(s.id_servicio) === String(id)) || null;
        },
        create: async function (data) {
            const res = await window.ApiClient.post('api_servicios.php', data);
            return res;
        },
        update: async function (id, data) {
            const res = await window.ApiClient.put('api_servicios.php', { id_servicio: id, ...data });
            return res;
        },
        delete: async function (id) {
            const res = await window.ApiClient.delete('api_servicios.php', { id: id });
            return res;
        }
    };
})();