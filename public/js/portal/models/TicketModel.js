// js/portal/models/TicketModel.js
// Model del portal para tickets (096d).
//
// Contrato backend (api_portal_tickets.php):
//   GET  ?page=1&per_page=20&estado=Abierto&prioridad=Alta
//     → {status:'success', tickets:[...], total, page, pages, per_page}
//   GET  ?id=N
//     → {status:'success', ticket:{...}, respuestas:[...]} | 404
//   POST (JSON)
//     Fields: asunto, descripcion, prioridad, categoria_id, servicio_id
//     → {status:'success', message, ticket_id}
//
// Contrato backend (api_portal_ticket_respuestas.php):
//   POST (JSON) {id_ticket, mensaje}
//     → {status:'success', message, id_respuesta}
//
// Contrato backend (api_portal_categorias_ticket.php):
//   GET → {status:'success', categorias:[{id, nombre, descripcion, color, activa}]}
//
// El backend ya filtra por `cliente_id` de la sesión (BOLA/IDOR cerrado).
// El frontend no envía `cliente_id` en ningún request.
//
// `sla_vence_en` viene en UTC. El View lo convierte a local.
(function () {
    'use strict';

    window.PortalTicketModel = {
        cache: [],
        categoriasCache: [],
        total: 0,
        page: 1,
        pages: 1,
        perPage: 20,

        /**
         * Carga la lista paginada de tickets del cliente autenticado.
         *
         * @param {number} page
         * @param {number} perPage
         * @param {Object} filtros - {estado, prioridad}
         * @returns {Promise<Object>} JSON crudo del endpoint
         */
        load: async function (page = 1, perPage = 20, filtros = {}) {
            const params = { page: page, per_page: perPage };
            if (filtros.estado) params.estado = filtros.estado;
            if (filtros.prioridad) params.prioridad = filtros.prioridad;

            const res = await window.ApiClient.get('api_portal_tickets.php', params);

            if (res.status === 'success') {
                this.cache = Array.isArray(res.tickets) ? res.tickets : [];
                this.total = Number(res.total) || 0;
                this.page = Number(res.page) || 1;
                this.pages = Number(res.pages) || 1;
                this.perPage = Number(res.per_page) || perPage;
            }
            return res;
        },

        /**
         * Detalle de un ticket + hilo de respuestas.
         * Devuelve `null` si el backend responde 404.
         *
         * @param {number|string} id
         * @returns {Promise<Object|null>} {ticket, respuestas} o null
         */
        loadDetail: async function (id) {
            try {
                const res = await window.ApiClient.get('api_portal_tickets.php', { id: id });
                if (res.status === 'success' && res.ticket) {
                    return { ticket: res.ticket, respuestas: res.respuestas || [] };
                }
                return null;
            } catch (e) {
                if (e && e.status === 404) return null;
                throw e;
            }
        },

        /**
         * Crea un ticket desde el portal.
         *
         * El backend fuerza cliente_id de la sesión, origen='portal_cliente',
         * estado='Abierto' y usuario_creador_id=NULL. El frontend NO los envía.
         *
         * @param {Object} data - {asunto, descripcion, prioridad, categoria_id, servicio_id}
         * @returns {Promise<Object>} JSON del endpoint
         */
        create: async function (data) {
            return window.ApiClient.post('api_portal_tickets.php', {
                asunto: data.asunto,
                descripcion: data.descripcion || null,
                prioridad: data.prioridad,
                categoria_id: data.categoria_id || null,
                servicio_id: data.servicio_id || null
            });
        },

        /**
         * Responde un ticket del portal.
         *
         * El backend fuerza tipo_autor='cliente', credencial_id de la sesión,
         * usuario_id=NULL. El frontend NO los envía.
         *
         * @param {number|string} idTicket
         * @param {string} mensaje
         * @returns {Promise<Object>} JSON del endpoint
         */
        responder: async function (idTicket, mensaje) {
            return window.ApiClient.post('api_portal_ticket_respuestas.php', {
                id_ticket: idTicket,
                mensaje: mensaje
            });
        },

        /**
         * Carga el catálogo de categorías activas para el select de "Nuevo ticket".
         *
         * @returns {Promise<Array>}
         */
        loadCategorias: async function () {
            const res = await window.ApiClient.get('api_portal_categorias_ticket.php');
            if (res.status === 'success') {
                this.categoriasCache = Array.isArray(res.categorias) ? res.categorias : [];
            }
            return this.categoriasCache;
        },

        getById: function (id) {
            return this.cache.find(t => String(t.id) === String(id)) || null;
        }
    };
})();