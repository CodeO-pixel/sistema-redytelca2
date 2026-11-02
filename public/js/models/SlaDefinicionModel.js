// js/models/SlaDefinicionModel.js
(function () {
    'use strict';

    window.SlaDefinicionModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_sla_definiciones.php');
            if (res.status === 'success') {
                this.cache = res.slas || [];
            }
            return this.cache;
        },

        getById: function (id) {
            return this.cache.find(s => String(s.id) === String(id)) || null;
        },

        create: async function (data) {
            return window.ApiClient.post('api_sla_definiciones.php', {
                categoria_id: data.categoria_id,
                prioridad: data.prioridad,
                horas_respuesta: data.horas_respuesta,
                horas_resolucion: data.horas_resolucion,
                descripcion: data.descripcion
            });
        },

        update: async function (id, data) {
            return window.ApiClient.put('api_sla_definiciones.php', {
                id_sla: id,
                categoria_id: data.categoria_id,
                prioridad: data.prioridad,
                horas_respuesta: data.horas_respuesta,
                horas_resolucion: data.horas_resolucion,
                descripcion: data.descripcion
            });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_sla_definiciones.php', { id: id });
        }
    };
})();