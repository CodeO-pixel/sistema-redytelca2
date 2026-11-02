// UsuarioController.js — B3.4 + Fase 4.1 (U1) + Fase 4.2 + 4.2-hotfix1
// Patrón B2 (E1–E7) + UX 5.1 (loading, empty+CTA, contador, focus, Enter/Esc,
// discard, debounce, mark, sessionStorage).
// 4.2-hotfix1: _readEditForm ya NO lee roles. Los roles se editan solo
// desde el modal dedicado #user-roles-modal.

window.UsuarioController = {
    _searchTimer: null,
    _dirty: false,

    init: function () {
        if (window.UsuarioView) window.UsuarioView._attachListeners();
    },

    load: async function (page = 1) {
        if (window.selectedIds) window.selectedIds['usuarios'] = null;
        document.querySelectorAll(
            '.toolbar[data-toolbar="usuarios"] [data-action="edit"], ' +
            '.toolbar[data-toolbar="usuarios"] [data-action="delete"], ' +
            '.toolbar[data-toolbar="usuarios"] [data-action="reset"], ' +
            '.toolbar[data-toolbar="usuarios"] [data-action="roles"]'
        ).forEach(function (b) { b.disabled = true; });
        document.querySelectorAll('#users-table tbody tr.selected-row').forEach(function (r) {
            r.classList.remove('selected-row');
        });

        try {
            if (window.RolModel && window.RolModel.cache.length === 0) {
                await window.RolModel.load();
            }
            await window.UsuarioModel.load();
        } catch (e) {
            window.Toast.error(e.message || 'Error al cargar usuarios');
            return;
        }
        this.renderPage(page);
    },

    renderPage: function (page) {
        const search = this._readSearch();
        const filtered = this._filter(window.UsuarioModel.cache, search);
        const size = window.pageSize;
        const totalPages = Math.max(1, Math.ceil(filtered.length / size));
        const safePage = Math.min(Math.max(1, page), totalPages);
        window.__usuariosCurrentPage = safePage;
        this._persist(search, safePage);
        window.UsuarioView.renderTable(filtered.slice((safePage - 1) * size, safePage * size), search);
        window.UsuarioView.renderPagination(safePage, totalPages);
        window.UsuarioView.updateCounter(filtered.length, safePage, size);
    },

    save: async function () {
        const editForm = document.getElementById('edit-user-modal');
        const isEditing = editForm && editForm.dataset.editing === '1';
        const currentPage = window.__usuariosCurrentPage || 1;

        const data = isEditing ? this._readEditForm() : this._readCreateForm();
        if (!data) return;

        const btn = document.querySelector(isEditing ? '[data-action="save-user-edit"]' : '[data-action="save-user"]');
        const originalText = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

        try {
            const res = isEditing
                ? await window.UsuarioModel.update(data.id_usuario, data)
                : await window.UsuarioModel.create(data);
            if (res.status !== 'success') throw new Error(res.message || 'Error al guardar');
            window.Toast.success(res.message || 'Guardado');
            if (!isEditing && res.message && /temporal/i.test(res.message)) {
                window.Modal.confirm(res.message, { title: 'Usuario creado', danger: false, confirmText: 'Copiar contraseña' })
                    .then(function (ok) { if (ok && navigator.clipboard) navigator.clipboard.writeText('Redytelca123!'); })
                    .catch(function () {});
            }
            this._closeModals();
            this._dirty = false;
            await this.load(isEditing ? currentPage : 1);
            if (!isEditing) {
                const last = Math.max(1, Math.ceil(window.UsuarioModel.cache.length / window.pageSize));
                this.renderPage(last);
            }
        } catch (e) {
            window.Toast.error(e.message || 'Error al guardar');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = originalText; }
        }
    },

    edit: async function (id) {
        if (window.UsuarioModel.cache.length === 0) await window.UsuarioModel.load();
        const u = window.UsuarioModel.getById(id);
        if (!u) { window.Toast.error('Usuario no encontrado'); return; }
        window.UsuarioView.toggleForm(false, false, 'edit');
        window.UsuarioView.fillForm(u);
    },

    confirmDelete: async function (id) {
        const u = window.UsuarioModel.getById(id);
        if (!u) return;
        if (u.es_admin && window.UsuarioModel.cache.filter(function (x) { return x.es_admin && x.estado === 'Activo'; }).length <= 1) {
            window.Toast.error('No puedes eliminar al único administrador activo');
            return;
        }
        const ok = await window.Modal.confirm(
            '¿Desactivar al usuario "' + u.username + '"? Quedará inactivo y no podrá iniciar sesión.',
            { danger: true, confirmText: 'Desactivar' }
        );
        if (!ok) return;
        try {
            const res = await window.UsuarioModel.delete(id);
            window.Toast.success((res && res.message) || 'Usuario desactivado correctamente');
            this._dirty = false;
            await this.load(window.__usuariosCurrentPage || 1);
        } catch (e) {
            window.Toast.error(e.message || 'Error al desactivar');
        }
    },

    changePassword: function (id) {
        const u = window.UsuarioModel.getById(id);
        if (!u) return;
        window.UsuarioView.openResetPassword(u);
    },

    /**
     * Abre el modal de edición de roles del usuario seleccionado.
     *
     * Fase 4.2. Modal independiente del de "Editar usuario".
     * Carga UsuarioModel y RolModel si están vacíos antes de abrir.
     *
     * @param {number|string} id
     */
    openRolesModal: function (id) {
        const open = () => {
            const u = window.UsuarioModel.getById(id);
            if (!u) { window.Toast.error('Usuario no encontrado'); return; }
            window.UsuarioView.renderRolesModal(u);
        };
        if (window.UsuarioModel.cache.length === 0) {
            window.UsuarioModel.load().then(() => {
                if (window.RolModel && window.RolModel.cache.length === 0) {
                    window.RolModel.load().then(open).catch(e => window.Toast.error(e.message || 'Error al cargar roles'));
                } else {
                    open();
                }
            }).catch(e => window.Toast.error(e.message || 'Error al cargar usuarios'));
            return;
        }
        if (window.RolModel && window.RolModel.cache.length === 0) {
            window.RolModel.load().then(open).catch(e => window.Toast.error(e.message || 'Error al cargar roles'));
            return;
        }
        open();
    },

    /**
     * Guarda los roles del usuario desde el modal #user-roles-modal.
     *
     * Fase 4.2. Al success, cierra el modal y recarga la tabla
     * para reflejar roles_nombres.
     *
     * @returns {Promise<void>}
     */
    saveRoles: async function () {
        const id = parseInt(document.getElementById('user-roles-id')?.value || '0', 10);
        if (!id) { window.Toast.error('Usuario inválido'); return; }

        const container = document.getElementById('user-roles-checkboxes');
        if (!container) return;
        const roles = Array.from(container.querySelectorAll('input[type="checkbox"]:checked'))
            .map(function (cb) { return parseInt(cb.value, 10); })
            .filter(function (n) { return n > 0; });

        if (roles.length === 0) {
            window.Toast.warning('Asigna al menos un rol.');
            return;
        }

        const btn = document.querySelector('#user-roles-modal [data-action="save-user-roles"]');
        const originalText = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

        try {
            const res = await window.UsuarioModel.saveRoles(id, roles);
            if (res.status === 'success') {
                window.Toast.success(res.message || 'Roles actualizados correctamente');
                const m = document.getElementById('user-roles-modal');
                if (m) m.style.display = 'none';
                await this.load(window.__usuariosCurrentPage || 1);
            } else {
                window.Toast.error(res.message || 'Error al guardar roles');
            }
        } catch (e) {
            window.Toast.error(e.message || 'Error al guardar roles');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = originalText; }
        }
    },

    verificarEmail: async function (id) {
        try {
            await window.UsuarioModel.verificarEmail(id);
            window.Toast.success('Correo verificado');
            await this.load(window.__usuariosCurrentPage || 1);
        } catch (e) {
            window.Toast.error(e.message || 'Error al verificar');
        }
    },

    _readSearch: function () {
        const input = document.getElementById('usuarios-search');
        return input ? input.value : '';
    },

    _persist: function (search, page) {
        try {
            sessionStorage.setItem('redytelca.usuarios.search', search);
            sessionStorage.setItem('redytelca.usuarios.page', String(page));
        } catch (e) { /* no-op */ }
    },

    _filter: function (items, search) {
        const s = window.normalizeForSearch(search || '');
        if (!s) return items;
        return items.filter(function (u) {
            return window.normalizeForSearch(
                (u.username || '') + ' ' + (u.email || '') + ' ' + (u.roles_nombres || '') + ' ' + (u.estado || '')
            ).includes(s);
        });
    },

    _readCreateForm: function () {
        const username = (document.getElementById('new-user-username')?.value || '').trim();
        const email = (document.getElementById('new-user-email')?.value || '').trim();
        const roles = this._readRoleCheckboxes('new-user-roles-checkboxes');
        if (!username || !email) { window.Toast.error('Usuario y correo son obligatorios'); return null; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { window.Toast.error('Correo inválido'); return null; }
        if (roles.length === 0) { window.Toast.error('Asigna al menos un rol'); return null; }
        return { username: username, email: email, roles: roles };
    },

    /**
     * Lee el form de EDICIÓN (identity only).
     *
     * 4.2-hotfix1: ya NO lee roles. Devuelve solo id + username + email.
     * Los roles se cambian exclusivamente desde #user-roles-modal.
     *
     * @returns {{id_usuario: number, username: string, email: string}|null}
     */
    _readEditForm: function () {
        const id = parseInt(document.getElementById('edit-user-modal')?.dataset.userId || '0', 10);
        if (!id) { window.Toast.error('Usuario inválido'); return null; }
        const username = (document.getElementById('edit-user-username')?.value || '').trim();
        const email = (document.getElementById('edit-user-email')?.value || '').trim();
        if (!username || !email) { window.Toast.error('Usuario y correo son obligatorios'); return null; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { window.Toast.error('Correo inválido'); return null; }
        return { id_usuario: id, username: username, email: email };
    },

    _readRoleCheckboxes: function (containerId) {
        const container = document.getElementById(containerId);
        if (!container) return [];
        return Array.from(container.querySelectorAll('input[type="checkbox"]:checked'))
            .map(function (cb) { return parseInt(cb.value, 10); })
            .filter(function (n) { return n > 0; });
    },

    _closeModals: function () {
        ['user-modal', 'edit-user-modal', 'reset-password-modal', 'user-roles-modal'].forEach(function (id) {
            const m = document.getElementById(id);
            if (m) m.style.display = 'none';
        });
        const em = document.getElementById('edit-user-modal');
        if (em) em.dataset.editing = '0';
    }
};

document.addEventListener('DOMContentLoaded', function () {
    if (window.UsuarioController) window.UsuarioController.init();
    try {
        const s = sessionStorage.getItem('redytelca.usuarios.search');
        const p = sessionStorage.getItem('redytelca.usuarios.page');
        const input = document.getElementById('usuarios-search');
        if (input && s) input.value = s;
        if (p) window.__usuariosCurrentPage = parseInt(p, 10) || 1;
    } catch (e) { /* no-op */ }
});