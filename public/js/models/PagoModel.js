// js/models/PagoModel.js
(function () {
    'use strict';

    window.PagoModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_pagos.php');
            if (res.status === 'success') {
                this.cache = res.pagos || [];
            }
            return this.cache;
        },

        getById: function (id) {
            return this.cache.find(p => String(p.id) === String(id)) || null;
        },

        create: async function (data) {
            return window.ApiClient.post('api_pagos.php', data);
        },

        update: async function (id, data) {
            return window.ApiClient.put('api_pagos.php', { id_pago: id, ...data });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_pagos.php', { id: id });
        },

        updateEstado: async function (id, estado) {
            return window.ApiClient.put('api_pagos.php', { id_pago: id, estado: estado });
        }
    };
})();