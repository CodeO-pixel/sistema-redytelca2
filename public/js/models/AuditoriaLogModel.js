// js/models/AuditoriaLogModel.js
(function () {
    'use strict';

    window.AuditoriaLogModel = {
        cache: [],

        /**
         * Carga logs de auditoría paginados con filtros.
         * El endpoint es GET solo lectura. No hay create/update/delete.
         *
         * @param {Object} opts
         * @param {number} opts.page
         * @param {number} opts.per_page
         * @param {string} opts.tabla
         * @param {string} opts.accion
         * @param {string} opts.desde
         * @param {string} opts.hasta
         * @returns {Promise<Object>} JSON crudo del endpoint
         */
        load: async function (opts = {}) {
            const params = {};
            if (opts.page) params.page = opts.page;
            if (opts.per_page) params.per_page = opts.per_page;
            if (opts.tabla) params.tabla = opts.tabla;
            if (opts.accion) params.accion = opts.accion;
            if (opts.desde) params.desde = opts.desde;
            if (opts.hasta) params.hasta = opts.hasta;

            const res = await window.ApiClient.get('api_auditoria_logs.php', params);
            if (res.status === 'success') {
                this.cache = res.logs || [];
            }
            return res;
        },

        getById: function (id) {
            return this.cache.find(l => String(l.id) === String(id)) || null;
        }
    };
})();