// js/models/ConfiguracionEmpresaModel.js
(function () {
    'use strict';

    window.ConfiguracionEmpresaModel = {
        cache: { publico: null, completo: null },

        loadPublica: async function () {
            const res = await window.ApiClient.get('api_empresa_publica.php');
            if (res.status === 'success' && res.config) {
                this.cache.publico = res.config;
            }
            return res;
        },

        loadCompleta: async function () {
            const res = await window.ApiClient.get('api_empresa.php');
            if (res.status === 'success' && res.config) {
                this.cache.completo = res.config;
            }
            return res;
        },

        /**
         * Envía la configuración al backend.
         *
         * @param {Object} data                 Campos de texto/numéricos
         * @param {Object|File|null} files      { login: File, sidebar: File }
         *                                       o un File suelto (back-compat B3.11: se trata como login)
         */
        update: async function (data, files) {
            const fd = new FormData();
            Object.entries(data || {}).forEach(function (entry) {
                const k = entry[0];
                const v = entry[1];
                if (v !== undefined && v !== null) {
                    fd.append(k, v);
                }
            });

            // Normalizar el argumento de archivos
            let loginFile = null;
            let sidebarFile = null;
            if (files instanceof File) {
                loginFile = files; // back-compat: un File suelto es el logo del login
            } else if (files && typeof files === 'object') {
                if (files.login instanceof File) loginFile = files.login;
                if (files.sidebar instanceof File) sidebarFile = files.sidebar;
            }

            if (loginFile) fd.append('logo_login', loginFile);
            if (sidebarFile) fd.append('logo_sidebar', sidebarFile);

            return window.ApiClient.upload('api_empresa.php', fd);
        }
    };
})();