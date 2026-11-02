// js/models/ContratoModel.js
(function () {
    'use strict';

    window.ContratoModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_contratos.php');
            if (res.status === 'success') {
                this.cache = res.contratos || [];
            }
            return this.cache;
        },

        loadVencer: async function () {
            // POST idempotente: materializa contratos vigentes cuya fecha_fin ya pasó.
            // Solo requirePageAccess (sin requireOperation): no lee datos controlados por el usuario.
            return window.ApiClient.post('api_contratos_vencer.php', {});
        },

        getById: function (id) {
            return this.cache.find(c => String(c.id) === String(id)) || null;
        },

        create: async function (data) {
            // El backend lee id_servicio (PK legacy, pre-ADR 016).
            return window.ApiClient.post('api_contratos.php', data);
        },

        update: async function (id, data) {
            // El backend lee id_contrato (PK legacy, pre-ADR 016).
            return window.ApiClient.put('api_contratos.php', { id_contrato: id, ...data });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_contratos.php', { id: id });
        }
    };
})();