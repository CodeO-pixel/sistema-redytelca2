// js/portal/models/MetodoPagoModel.js
// Model del portal para el catálogo de métodos de pago (096c).
//
// Contrato backend (api_portal_metodos_pago.php):
//   GET → {status:'success', metodos:[{id, codigo, nombre, requiere_referencia}]}
// Reusa MetodoPagoController::index() (A2). Solo devuelve los métodos activos.
//
// El portal solo necesita: id, codigo, nombre, requiere_referencia.
// Cachea la lista completa (4 filas en el seed).
(function () {
    'use strict';

    window.PortalMetodoPagoModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_portal_metodos_pago.php');
            if (res.status === 'success') {
                this.cache = Array.isArray(res.metodos) ? res.metodos : [];
            }
            return res;
        },

        getById: function (id) {
            return this.cache.find(m => String(m.id) === String(id)) || null;
        }
    };
})();