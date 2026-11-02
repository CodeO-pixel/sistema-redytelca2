// js/models/FacturaModel.js
(function () {
    'use strict';

    window.FacturaModel = {
        cache: [],
        cacheLineas: {},

        load: async function () {
            const res = await window.ApiClient.get('api_facturas.php');
            if (res.status === 'success') {
                this.cache = res.facturas || [];
            }
            return this.cache;
        },

        loadVencer: async function () {
            return window.ApiClient.post('api_facturas_vencer.php', {});
        },

        getById: function (id) {
            return this.cache.find(f => String(f.id) === String(id)) || null;
        },

        create: async function (data) {
            return window.ApiClient.post('api_facturas.php', data);
        },

        update: async function (id, data) {
            return window.ApiClient.put('api_facturas.php', { id_factura: id, ...data });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_facturas.php', { id: id });
        },

        loadLineas: async function (idFactura) {
            const res = await window.ApiClient.get('api_factura_lineas.php', { id_factura: idFactura });
            if (res.status === 'success') {
                this.cacheLineas[String(idFactura)] = res.lineas || [];
                return this.cacheLineas[String(idFactura)];
            }
            return [];
        }
    };
})();