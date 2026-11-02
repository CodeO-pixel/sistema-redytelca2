// js/portal/models/FacturaModel.js
// Model del portal para "Mis facturas" (096b, read-only).
// Paginación server-side: el backend devuelve page/pages/total.
// Los filtros (estado, periodo) se envían como query params.
//
// Contrato backend (api_portal_facturas.php):
//   GET ?page=1&per_page=20&estado=pendiente&periodo=2026-10
//     → {status:'success', facturas:[...], total, page, pages, per_page}
//   GET ?id=N
//     → {status:'success', factura:{...}, lineas:[...]}
//   GET ?id=<otro-cliente> → 404 (BOLA/IDOR cerrado)
(function () {
    'use strict';

    window.PortalFacturaModel = {
        cache: [],
        cacheLineas: {},
        total: 0,
        page: 1,
        pages: 1,
        perPage: 20,

        /**
         * Carga la lista de facturas del cliente autenticado.
         *
         * @param {number} page
         * @param {number} perPage
         * @param {Object} filtros - {estado, periodo}
         * @returns {Promise<Object>} JSON crudo del endpoint
         */
        load: async function (page = 1, perPage = 20, filtros = {}) {
            const params = { page: page, per_page: perPage };
            if (filtros.estado) params.estado = filtros.estado;
            if (filtros.periodo) params.periodo = filtros.periodo;

            const res = await window.ApiClient.get('api_portal_facturas.php', params);

            if (res.status === 'success') {
                this.cache = Array.isArray(res.facturas) ? res.facturas : [];
                this.total = Number(res.total) || 0;
                this.page = Number(res.page) || 1;
                this.pages = Number(res.pages) || 1;
                this.perPage = Number(res.per_page) || perPage;
            }
            return res;
        },

        /**
         * Detalle de una factura + sus líneas.
         * Devuelve `null` si el backend responde 404.
         *
         * @param {number|string} id
         * @returns {Promise<Object|null>} {factura, lineas} o null
         */
        loadDetail: async function (id) {
            try {
                const res = await window.ApiClient.get('api_portal_facturas.php', { id: id });
                if (res.status === 'success' && res.factura) {
                    this.cacheLineas[String(id)] = Array.isArray(res.lineas) ? res.lineas : [];
                    return { factura: res.factura, lineas: this.cacheLineas[String(id)] };
                }
                return null;
            } catch (e) {
                if (e && e.status === 404) return null;
                throw e;
            }
        },

        getById: function (id) {
            return this.cache.find(f => String(f.id) === String(id)) || null;
        }
    };
})();