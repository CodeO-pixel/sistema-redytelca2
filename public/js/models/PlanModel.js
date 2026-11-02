// js/models/PlanModel.js
(function () {
    'use strict';

    window.PlanModel = {
        cache: [],
        load: async function () {
            const res = await window.ApiClient.get('api_planes.php');
            if (res.status === 'success') {
                this.cache = res.planes || [];
                return this.cache;
            }
            return [];
        },
        getById: function (id) {
            return this.cache.find(p => String(p.id) === String(id) || String(p.id_plan) === String(id)) || null;
        },
        create: async function (data) {
            return window.ApiClient.post('api_planes.php', data);
        },
        update: async function (id, data) {
            return window.ApiClient.put('api_planes.php', { id_plan: id, ...data });
        },
        delete: async function (id) {
            return window.ApiClient.delete('api_planes.php', { id: id });
        }
    };
})();