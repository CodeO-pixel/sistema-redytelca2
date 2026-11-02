// js/models/PermisoModel.js
(function () {
    'use strict';

    window.PermisoModel = {
        cache: {
            modulos: [],
            permisos_catalogo: [],
            lastIdRol: null
        },

        loadMatrix: async function (idRol) {
            const res = await window.ApiClient.get('permisos_listar.php', { id_rol: idRol });
            if (res.status === 'success') {
                this.cache.modulos = res.modulos || [];
                this.cache.permisos_catalogo = res.permisos_catalogo || [];
                return {
                    modulos: this.cache.modulos,
                    permisos_catalogo: this.cache.permisos_catalogo
                };
            }
            return { modulos: [], permisos_catalogo: [] };
        },

        saveMatrix: async function (payload) {
            return window.ApiClient.post('permisos_guardar.php', payload);
        }
    };
})();