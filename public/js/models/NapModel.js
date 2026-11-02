// js/models/NapModel.js
(function () {
    'use strict';
    window.NapModel = {
        cache: [],
        load: async function () {
            const res = await window.ApiClient.get('api_naps.php');
            if (res.status === 'success') this.cache = res.naps || [];
            return this.cache;
        },
        getById: function (id) {
            return this.cache.find(n => String(n.id) === String(id)) || null;
        },
        create: async function (data) { return window.ApiClient.post('api_naps.php', data); },
        update: async function (id, data) { return window.ApiClient.put('api_naps.php', { id_nap: id, ...data }); },
        delete: async function (id) { return window.ApiClient.delete('api_naps.php', { id: id }); }
    };
})();