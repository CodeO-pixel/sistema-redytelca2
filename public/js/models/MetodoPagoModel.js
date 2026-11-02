// js/models/MetodoPagoModel.js
(function () {
    'use strict';

    window.MetodoPagoModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_metodos_pago.php');
            if (res.status === 'success') {
                this.cache = res.metodos || [];
            }
            return this.cache;
        },

        getById: function (id) {
            return this.cache.find(m => String(m.id) === String(id)) || null;
        }
    };
})();