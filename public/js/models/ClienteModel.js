// js/models/ClienteModel.js
(function () {
    'use strict';

    window.ClienteModel = {
        cache: [],
        load: async function (filter = '', page = 1) {
            const res = await window.ApiClient.get('clientes_listar.php', { filter: filter, page: page, per_page: 10 });
            if (res.status === 'success') {
                this.cache = res.clientes || [];
                return res;
            }
            return { clientes: [], page: 1, pages: 1, total: 0 };
        },
        getById: function (id) {
            return this.cache.find(c => String(c.id) === String(id) || String(c.id_cliente) === String(id)) || null;
        },
        create: async function (data) {
            const res = await window.ApiClient.post('registrar_cliente.php', data);
            return res;
        },
        update: async function (id, data) {
            const res = await window.ApiClient.post('cliente_actualizar.php', { id_cliente: id, ...data });
            return res;
        },
        deactivate: async function (id) {
            const res = await window.ApiClient.post('cliente_eliminar.php', { id_cliente: id });
            return res;
        }
    };
})();