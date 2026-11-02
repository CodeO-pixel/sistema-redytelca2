// UsuarioModel.js — B3.4 + Fase 4.2 + 4.2-hotfix1
// Contrato confirmado en src/services/UsuarioService.php:415 (listarConRoles)
// y src/controllers/UsuarioController.php:47 (store) / :95 (update) / :saveRoles.
// 4.2-hotfix1: update() ya NO manda roles. Los roles se cambian solo con saveRoles().

window.UsuarioModel = {
    cache: [],

    load: async function () {
        const res = await window.ApiClient.get('usuarios_listar.php');
        this.cache = Array.isArray(res.usuarios) ? res.usuarios : [];
        return this.cache;
    },

    getById: function (id) {
        return this.cache.find(u => String(u.id) === String(id)) || null;
    },

    create: async function (data) {
        return window.ApiClient.post('usuarios_crear.php', {
            username: data.username,
            email: data.email,
            roles: Array.isArray(data.roles) ? data.roles.map(Number).filter(n => n > 0) : []
        });
    },

    /**
     * Actualiza identidad del usuario (username + email).
     *
     * 4.2-hotfix1: NO manda roles. Si el caller pasa data.roles,
     * se ignora a propósito. Los roles se cambian solo con saveRoles().
     *
     * @param {number|string} id
     * @param {{username: string, email: string}} data
     * @returns {Promise<Object>}
     */
    update: async function (id, data) {
        return window.ApiClient.put('usuarios_editar.php', {
            id_usuario: id,
            username: data.username,
            email: data.email
        });
    },

    /**
     * Actualiza SOLO los roles del usuario, sin tocar username/email.
     *
     * Ruta dedicada `usuarios_roles.php` (PUT). Backend:
     * UsuarioController::saveRoles → UsuarioService::asignarRoles.
     * Protege al último admin activo.
     *
     * @param {number|string} id
     * @param {number[]} roles
     * @returns {Promise<Object>} JSON del endpoint
     */
    saveRoles: async function (id, roles) {
        return window.ApiClient.put('usuarios_roles.php', {
            id_usuario: id,
            roles: Array.isArray(roles) ? roles.map(Number).filter(n => n > 0) : []
        });
    },

    delete: async function (id) {
        return window.ApiClient.post('usuarios_eliminar.php', { id_usuario: id });
    },

    verificarEmail: async function (id) {
        return window.ApiClient.post('usuarios_verificar_email.php', { id_usuario: id });
    }
};