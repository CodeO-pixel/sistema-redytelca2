// js/portal/models/ServicioModel.js
// Model del portal para "Mis servicios" (096e, read-only).
// Cachea la lista completa (per_page=100) para paginar client-side.
// El detalle se pide al backend por id cuando se abre el modal.
//
// Contrato backend (api_portal_servicios.php):
//   GET ?page=1&per_page=100
//     → {status:'success', servicios:[...], total, page, pages, per_page}
//   GET ?id=N
//     → {status:'success', servicio:{...}}
//   GET ?id=<otro-cliente> → 404 (BOLA/IDOR cerrado)
(function () {
    'use strict';

    window.PortalServicioModel = {
        cache: [],

        /**
         * Carga la lista de servicios del cliente autenticado.
         * Usa per_page=100 (máximo permitido por el backend) para
         * paginar client-side con pocos servicios.
         *
         * @param {number} perPage
         * @returns {Promise<Object>} JSON crudo del endpoint
         */
        load: async function (perPage = 100) {
            const res = await window.ApiClient.get('api_portal_servicios.php', {
                page: 1,
                per_page: perPage
            });
            if (res.status === 'success') {
                this.cache = Array.isArray(res.servicios) ? res.servicios : [];
            }
            return res;
        },

        /**
         * Detalle de un servicio. Devuelve `null` si el backend
         * responde 404 (servicio de otro cliente).
         *
         * @param {number|string} id
         * @returns {Promise<Object|null>}
         */
        loadDetail: async function (id) {
            try {
                const res = await window.ApiClient.get('api_portal_servicios.php', { id: id });
                if (res.status === 'success' && res.servicio) {
                    return res.servicio;
                }
                return null;
            } catch (e) {
                if (e && e.status === 404) return null;
                throw e;
            }
        },

        getById: function (id) {
            return this.cache.find(s => String(s.id) === String(id)) || null;
        }
    };
})();