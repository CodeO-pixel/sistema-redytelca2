// js/models/TareaModel.js
(function () {
    'use strict';

    window.TareaModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_tareas.php');
            if (res.status === 'success') {
                this.cache = res.tareas || [];
            }
            return this.cache;
        },

        getById: function (id) {
            return this.cache.find(t => String(t.id) === String(id)) || null;
        },

        create: async function (data) {
            return window.ApiClient.post('api_tareas.php', data);
        },

        update: async function (id, data) {
            return window.ApiClient.put('api_tareas.php', { id_tarea: id, ...data });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_tareas.php', { id: id });
        }
    };
})();