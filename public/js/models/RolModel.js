// js/models/RolModel.js
(function () {
    'use strict';

    window.RolModel = {
        cache: [],
        load: async function () {
            const res = await window.ApiClient.get('roles_listar.php');
            if (res.status === 'success') {
                this.cache = res.roles || [];
                return this.cache;
            }
            return [];
        },
        getById: function (id) {
            return this.cache.find(r => String(r.id_rol) === String(id)) || null;
        },
        create: async function (data) {
            return window.ApiClient.post('roles_listar.php', data);
        },
        update: async function (id, data) {
            return window.ApiClient.put('roles_editar.php', { id_rol: id, ...data });
        },
        delete: async function (id) {
            return window.ApiClient.post('roles_eliminar.php', { id_rol: id });
        }
    };
})();