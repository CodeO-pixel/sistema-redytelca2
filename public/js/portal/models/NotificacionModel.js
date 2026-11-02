// js/portal/models/NotificacionModel.js
// Model del portal para notificaciones del cliente (086d).
// Consume api_portal_notificaciones.php (A2-B.1).
//
// Contrato backend:
//   GET  ?count_only=1                → {status:'success', no_leidas: N}
//   GET  ?page=1&per_page=20          → {status, notificaciones:[...], total, page, pages, per_page, no_leidas}
//   PUT  {id_notificacion: N}         → {status, message}
//   PUT  {mark_all: true}             → {status, message, marked: N}
//
// El backend toma el cliente_id de la sesión (Auth::requireClientAuth).
// El frontend NUNCA envía cliente_id.
(function () {
    'use strict';

    window.PortalNotificacionModel = {
        cache: [],
        lastCount: 0,
        total: 0,
        page: 1,
        pages: 1,
        perPage: 20,

        /**
         * Carga la lista paginada de notificaciones del cliente.
         *
         * @param {Object} params - {page, per_page}
         * @returns {Promise<Object>} JSON crudo del endpoint
         */
        load: async function (params = {}) {
            const res = await window.ApiClient.get('api_portal_notificaciones.php', params);
            if (res.status === 'success') {
                this.cache = Array.isArray(res.notificaciones) ? res.notificaciones : [];
                this.total = Number(res.total) || 0;
                this.page = Number(res.page) || 1;
                this.pages = Number(res.pages) || 1;
                this.perPage = Number(res.per_page) || 20;
                this.lastCount = Number(res.no_leidas) || 0;
            }
            return res;
        },

        /**
         * Solo conteo de no leídas. Para el badge de la campana.
         * No reemplaza el cache.
         *
         * @returns {Promise<Object>}
         */
        countOnly: async function () {
            const res = await window.ApiClient.get('api_portal_notificaciones.php', { count_only: 1 });
            if (res.status === 'success') {
                this.lastCount = Number(res.no_leidas) || 0;
            }
            return res;
        },

        /**
         * Marca una notificación como leída.
         *
         * @param {number|string} id
         * @returns {Promise<Object>}
         */
        markRead: async function (id) {
            return window.ApiClient.put('api_portal_notificaciones.php', {
                id_notificacion: Number(id)
            });
        },

        /**
         * Marca todas las notificaciones del cliente como leídas.
         *
         * @returns {Promise<Object>}
         */
        markAllRead: async function () {
            return window.ApiClient.put('api_portal_notificaciones.php', {
                mark_all: true
            });
        },

        /**
         * Busca una notificación en cache por id.
         *
         * @param {number|string} id
         * @returns {Object|null}
         */
        getById: function (id) {
            return this.cache.find(n => String(n.id) === String(id)) || null;
        },

        /**
         * Resetea el cache. Se llama en Session.onClear.
         */
        reset: function () {
            this.cache = [];
            this.lastCount = 0;
            this.total = 0;
            this.page = 1;
            this.pages = 1;
        }
    };
})();