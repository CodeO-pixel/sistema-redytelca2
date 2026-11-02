// js/portal/models/BancoModel.js
// Catálogo de bancos emisores activos para el registro de pagos.
(function () {
    'use strict';

    window.PortalBancoModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_portal_bancos.php');
            if (res.status === 'success') {
                this.cache = Array.isArray(res.bancos) ? res.bancos : [];
            }
            return res;
        },

        getById: function (id) {
            return this.cache.find(b => String(b.id) === String(id)) || null;
        }
    };

    if (window.PortalSession) {
        window.PortalSession.onClear(() => {
            window.PortalBancoModel.cache = [];
        });
    }
})();
