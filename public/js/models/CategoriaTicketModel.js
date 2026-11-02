// js/models/CategoriaTicketModel.js
(function () {
    'use strict';

    window.CategoriaTicketModel = {
        cache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_categorias_ticket.php');
            if (res.status === 'success') {
                this.cache = res.categorias || [];
            }
            return this.cache;
        },

        getById: function (id) {
            return this.cache.find(c => String(c.id) === String(id)) || null;
        },

        create: async function (data) {
            return window.ApiClient.post('api_categorias_ticket.php', {
                nombre: data.nombre,
                descripcion: data.descripcion,
                color: data.color,
                activa: data.activa
            });
        },

        update: async function (id, data) {
            return window.ApiClient.put('api_categorias_ticket.php', {
                id_categoria: id,
                nombre: data.nombre,
                descripcion: data.descripcion,
                color: data.color,
                activa: data.activa
            });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_categorias_ticket.php', { id: id });
        }
    };
})();