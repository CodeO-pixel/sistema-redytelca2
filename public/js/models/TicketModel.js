// js/models/TicketModel.js
(function () {
    'use strict';

    window.TicketModel = {
        cache: [],
        categoriasCache: [],
        slaCache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_tickets.php');
            if (res.status === 'success') {
                this.cache = res.tickets || [];
            }
            return this.cache;
        },

        getById: function (id) {
            return this.cache.find(t => String(t.id) === String(id)) || null;
        },

        create: async function (data) {
            // El backend fuerza estado='Abierto' y origen='backoffice' en crear.
            // No enviamos estado ni usuario_creador_id (A5).
            return window.ApiClient.post('api_tickets.php', data);
        },

        update: async function (id, data) {
            return window.ApiClient.put('api_tickets.php', { id_ticket: id, ...data });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_tickets.php', { id: id });
        },

        cerrar: async function (id, detalle) {
            const t = this.getById(id);
            if (!t) {
                return { status: 'error', message: 'Ticket no encontrado en cache.' };
            }
            const base = t.descripcion || '';
            const extra = String(detalle || '').trim();
            const descripcion = extra ? (base ? base + '\n\nCierre: ' + extra : 'Cierre: ' + extra) : base;
            return window.ApiClient.put('api_tickets.php', {
                id_ticket: id,
                asunto: t.asunto,
                descripcion: descripcion,
                estado: 'Cerrado',
                prioridad: t.prioridad,
                cliente_id: t.cliente_id,
                servicio_id: t.servicio_id,
                categoria_id: t.categoria_id
            });
        },

        loadCategorias: async function () {
            const res = await window.ApiClient.get('api_categorias_ticket.php');
            if (res.status === 'success') {
                this.categoriasCache = res.categorias || [];
            }
            return this.categoriasCache;
        },

        loadSla: async function () {
            const res = await window.ApiClient.get('api_sla_definiciones.php');
            if (res.status === 'success') {
                this.slaCache = res.slas || [];
            }
            return this.slaCache;
        },

        loadRespuestas: async function (idTicket) {
            const res = await window.ApiClient.get('api_ticket_respuestas.php', { id_ticket: idTicket });
            if (res.status === 'success') {
                return res.respuestas || [];
            }
            return [];
        },

        createRespuesta: async function (idTicket, mensaje) {
            return window.ApiClient.post('api_ticket_respuestas.php', {
                id_ticket: idTicket,
                mensaje: mensaje
            });
        }
    };
})();