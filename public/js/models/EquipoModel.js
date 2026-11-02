// js/models/EquipoModel.js
(function () {
    'use strict';
    window.EquipoModel = {
        cache: [],
        load: async function (filters = {}) {
            const res = await window.ApiClient.get('api_equipos.php', filters);
            if (res.status === 'success') this.cache = res.equipos || [];
            return this.cache;
        },
        getById: function (id) {
            return this.cache.find(e => String(e.id) === String(id)) || null;
        },
        create: async function (data) { return window.ApiClient.post('api_equipos.php', data); },
        update: async function (id, data) { return window.ApiClient.put('api_equipos.php', { id_equipo: id, ...data }); },
        delete: async function (id) { return window.ApiClient.delete('api_equipos.php', { id: id }); }
    };
})();