// js/models/OltModel.js
(function () {
    'use strict';
    window.OltModel = {
        cache: [],
        load: async function () {
            const res = await window.ApiClient.get('api_olts.php');
            if (res.status === 'success') this.cache = res.olts || [];
            return this.cache;
        },
        getById: function (id) {
            return this.cache.find(o => String(o.id) === String(id)) || null;
        },
        create: async function (data) { return window.ApiClient.post('api_olts.php', data); },
        update: async function (id, data) { return window.ApiClient.put('api_olts.php', { id_olt: id, ...data }); },
        delete: async function (id) { return window.ApiClient.delete('api_olts.php', { id: id }); }
    };
})();