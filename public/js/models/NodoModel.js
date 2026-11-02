// js/models/NodoModel.js
(function () {
    'use strict';
    window.NodoModel = {
        cache: [],
        load: async function () {
            const res = await window.ApiClient.get('api_nodos.php');
            if (res.status === 'success') this.cache = res.nodos || [];
            return this.cache;
        },
        getById: function (id) {
            return this.cache.find(n => String(n.id) === String(id)) || null;
        },
        create: async function (data) { return window.ApiClient.post('api_nodos.php', data); },
        update: async function (id, data) { return window.ApiClient.put('api_nodos.php', { id_nodo: id, ...data }); },
        delete: async function (id) { return window.ApiClient.delete('api_nodos.php', { id: id }); }
    };
})();