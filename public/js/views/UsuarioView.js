// UsuarioView.js — B3.4 + Fase 4.2 + 4.2-hotfix1
// 4.2-hotfix1: fillForm ya NO puebla #edit-user-roles-checkboxes (el bloque
// se eliminó del HTML). Los roles viven solo en #user-roles-modal.
// Listener de dirty en edit-user-roles-checkboxes eliminado.

window.UsuarioView = {
    _attachListeners: function () {
        // Toolbar: solo dentro de #usuarios
        document.querySelectorAll('.toolbar[data-toolbar="usuarios"] [data-action="new"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => this.toggleForm(true, true, 'new'));
        });
        document.querySelectorAll('.toolbar[data-toolbar="usuarios"] [data-action="edit"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => {
                const id = window.selectedIds['usuarios'];
                if (id) window.UsuarioController.edit(id);
            });
        });
        document.querySelectorAll('.toolbar[data-toolbar="usuarios"] [data-action="delete"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => {
                const id = window.selectedIds['usuarios'];
                if (id) window.UsuarioController.confirmDelete(id);
            });
        });
        document.querySelectorAll('.toolbar[data-toolbar="usuarios"] [data-action="reset"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => {
                const id = window.selectedIds['usuarios'];
                if (id) window.UsuarioController.changePassword(id);
            });
        });
        // Fase 4.2 — Botón Roles (edita solo los roles del usuario)
        document.querySelectorAll('.toolbar[data-toolbar="usuarios"] [data-action="roles"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => {
                const id = window.selectedIds['usuarios'];
                if (id) window.UsuarioController.openRolesModal(id);
            });
        });

        // Modales: save / cancel / close
        document.querySelectorAll('[data-action="save-user"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => window.UsuarioController.save());
        });
        document.querySelectorAll('[data-action="cancel-user"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => this._requestClose('user-modal'));
        });
        document.querySelectorAll('[data-action="save-user-edit"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => window.UsuarioController.save());
        });
        document.querySelectorAll('[data-action="cancel-user-edit"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => this._requestClose('edit-user-modal'));
        });
        document.querySelectorAll('[data-action="save-password"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => this._submitPassword());
        });
        document.querySelectorAll('[data-action="cancel-password"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => { const m = document.getElementById('reset-password-modal'); if (m) m.style.display = 'none'; });
        });
        // Fase 4.2 — Modal #user-roles-modal
        document.querySelectorAll('#user-roles-modal [data-action="save-user-roles"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => window.UsuarioController.saveRoles());
        });
        document.querySelectorAll('#user-roles-modal [data-action="cancel-user-roles"]').forEach(b => {
            b.removeAttribute('onclick');
            b.addEventListener('click', () => { const m = document.getElementById('user-roles-modal'); if (m) m.style.display = 'none'; });
        });

        // Búsqueda con debounce
        const search = document.getElementById('usuarios-search');
        if (search) {
            search.removeAttribute('oninput');
            search.addEventListener('input', () => {
                clearTimeout(window.UsuarioController._searchTimer);
                window.UsuarioController._searchTimer = setTimeout(() => window.UsuarioController.renderPage(1), 250);
            });
        }

        // Enter/Esc en modales
        ['user-modal', 'edit-user-modal', 'reset-password-modal', 'user-roles-modal'].forEach(id => {
            const m = document.getElementById(id);
            if (!m) return;
            m.addEventListener('keydown', (ev) => {
                if (ev.key === 'Escape') { ev.preventDefault(); this._requestClose(id); }
                if (ev.key === 'Enter' && ev.target.tagName === 'INPUT') {
                    ev.preventDefault();
                    if (id === 'reset-password-modal') this._submitPassword();
                    else if (id === 'user-roles-modal') window.UsuarioController.saveRoles();
                    else window.UsuarioController.save();
                }
            });
        });

        // Estado inicial disabled
        ['edit', 'delete', 'reset', 'roles'].forEach(a => {
            document.querySelectorAll(`.toolbar[data-toolbar="usuarios"] [data-action="${a}"]`).forEach(b => { b.disabled = true; });
        });
    },

    renderTable: function (usuarios, search) {
        const tbody = document.querySelector('#users-table tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (usuarios.length === 0) {
            const hasSearch = window.normalizeForSearch(search || '').length > 0;
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;">${
                hasSearch
                    ? `Sin resultados para "<strong>${window.escapeHtml(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                    : `No hay usuarios todavía. <a href="#" data-action="new-user-empty">Crear el primero</a>`
            }</td></tr>`;
            const clear = tbody.querySelector('[data-action="clear-search"]');
            if (clear) clear.addEventListener('click', (ev) => { ev.preventDefault(); const i = document.getElementById('usuarios-search'); if (i) i.value = ''; window.UsuarioController.renderPage(1); });
            const cta = tbody.querySelector('[data-action="new-user-empty"]');
            if (cta) cta.addEventListener('click', (ev) => { ev.preventDefault(); this.toggleForm(true, true, 'new'); });
            return;
        }
        const mark = (txt) => {
            const s = window.normalizeForSearch(search || '');
            if (!s) return window.escapeHtml(txt);
            const raw = String(txt ?? '');
            const idx = window.normalizeForSearch(raw).indexOf(s);
            if (idx < 0) return window.escapeHtml(raw);
            return window.escapeHtml(raw.slice(0, idx)) + '<mark>' + window.escapeHtml(raw.slice(idx, idx + s.length)) + '</mark>' + window.escapeHtml(raw.slice(idx + s.length));
        };
        usuarios.forEach(u => {
            const tr = document.createElement('tr');
            tr.dataset.moduleRow = 'usuarios';
            tr.dataset.id = u.id;
            const verifiedBadge = u.email_verified
                ? '<span class="status-badge status-ok">Verificado</span>'
                : '<span class="status-badge status-warning">Pendiente</span>';
            tr.innerHTML = `
                <td>${u.id}</td>
                <td>${mark(u.username)}</td>
                <td>${mark(u.email)}</td>
                <td>${u.roles_nombres ? window.escapeHtml(u.roles_nombres) : '<span style="opacity:.5">—</span>'}</td>
                <td><span class="status-badge ${window.getStatusClass(u.estado)}">${window.escapeHtml(u.estado)}</span></td>
                <td>${verifiedBadge}</td>
                <td>${u.ultima_conexion ? window.escapeHtml(u.ultima_conexion.slice(0, 16)) : '<span style="opacity:.5">Nunca</span>'}</td>
            `;
            tr.addEventListener('click', () => this._selectRow(u.id, tr));
            tbody.appendChild(tr);
        });
    },

    renderPagination: function (page, totalPages) {
        const div = document.getElementById('users-pagination');
        if (!div) return;
        div.innerHTML = `
            <button type="button" class="btn-secondary" ${page <= 1 ? 'disabled' : ''} data-action="prev">Anterior</button>
            <span style="margin:0 12px;">Página ${page} / ${totalPages}</span>
            <button type="button" class="btn-secondary" ${page >= totalPages ? 'disabled' : ''} data-action="next">Siguiente</button>
        `;
        const prev = div.querySelector('[data-action="prev"]');
        const next = div.querySelector('[data-action="next"]');
        if (prev) prev.addEventListener('click', () => window.UsuarioController.renderPage(page - 1));
        if (next) next.addEventListener('click', () => window.UsuarioController.renderPage(page + 1));
    },

    updateCounter: function (total, page, size) {
        const span = document.getElementById('usuarios-counter');
        if (!span) return;
        if (total === 0) { span.textContent = ''; return; }
        const from = (page - 1) * size + 1;
        const to = Math.min(total, page * size);
        span.textContent = `Mostrando ${from}–${to} de ${total}`;
    },

    toggleForm: function (forceClose, reset, mode) {
        if (mode === 'edit') {
            const m = document.getElementById('edit-user-modal');
            if (!m) return;
            if (forceClose === false) { m.style.display = 'flex'; return; }
            m.style.display = 'none';
            return;
        }
        const m = document.getElementById('user-modal');
        if (!m) return;
        if (forceClose === true) {
            m.style.display = 'flex';
            if (reset) this._resetCreateForm();
            const first = m.querySelector('input,select,textarea');
            if (first) setTimeout(() => first.focus(), 50);
        } else {
            m.style.display = 'none';
        }
    },

    fillForm: function (u) {
        const m = document.getElementById('edit-user-modal');
        if (!m) return;
        m.dataset.userId = String(u.id);
        m.dataset.editing = '1';
        const u1 = document.getElementById('edit-user-username');
        const u2 = document.getElementById('edit-user-email');
        if (u1) u1.value = u.username || '';
        if (u2) u2.value = u.email || '';
        // 4.2-hotfix1: NO se puebla #edit-user-roles-checkboxes (bloque eliminado).
        m.style.display = 'flex';
        if (u1) setTimeout(() => u1.focus(), 50);
    },

    populateRoleCheckboxes: function (containerId, selectedIds) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const roles = (window.RolModel && window.RolModel.cache) || [];
        const sel = new Set((selectedIds || []).map(String));
        container.innerHTML = '';
        roles.forEach(r => {
            const id = String(r.id_rol);
            const label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:8px;cursor:pointer;';
            label.innerHTML = `<input type="checkbox" value="${id}" ${sel.has(id) ? 'checked' : ''}> <span>${window.escapeHtml(r.nombre_rol || id)}</span>`;
            container.appendChild(label);
        });
    },

    /**
     * Renderiza el modal de edición de roles del usuario.
     *
     * Fase 4.2. Reusa populateRoleCheckboxes con el container
     * #user-roles-checkboxes. Muestra el username en el header.
     *
     * @param {Object} user
     */
    renderRolesModal: function (user) {
        const m = document.getElementById('user-roles-modal');
        if (!m) return;
        const idEl = document.getElementById('user-roles-id');
        const nameEl = document.getElementById('user-roles-username');
        if (idEl) idEl.value = user.id || '';
        if (nameEl) nameEl.textContent = user.username || '—';
        this.populateRoleCheckboxes('user-roles-checkboxes', user.roles_ids || []);
        m.style.display = 'flex';
        const first = m.querySelector('input[type="checkbox"]');
        if (first) setTimeout(() => first.focus(), 50);
    },

    openResetPassword: function (u) {
        const m = document.getElementById('reset-password-modal');
        if (!m) return;
        const name = document.getElementById('reset-username');
        if (name) name.value = u.username || '';
        const pwd = document.getElementById('reset-new-password');
        if (pwd) pwd.value = '';
        m.dataset.userId = String(u.id);
        m.style.display = 'flex';
        if (pwd) setTimeout(() => pwd.focus(), 50);
    },

    _resetCreateForm: function () {
        const u = document.getElementById('new-user-username');
        const e = document.getElementById('new-user-email');
        if (u) u.value = '';
        if (e) e.value = '';
        this.populateRoleCheckboxes('new-user-roles-checkboxes', []);
    },

    _selectRow: function (id, tr) {
        const prev = document.querySelector('#users-table tbody tr.selected-row');
        if (prev) prev.classList.remove('selected-row');
        tr.classList.add('selected-row');
        window.selectedIds['usuarios'] = id;
        ['edit', 'delete', 'reset', 'roles'].forEach(a => {
            document.querySelectorAll(`.toolbar[data-toolbar="usuarios"] [data-action="${a}"]`).forEach(b => { b.disabled = false; });
        });
    },

    _requestClose: function (modalId) {
        if (!window.UsuarioController._dirty) { const m = document.getElementById(modalId); if (m) m.style.display = 'none'; return; }
        window.Modal.confirm('¿Descartar cambios sin guardar?', { danger: true, confirmText: 'Descartar' })
            .then(ok => { if (ok) { const m = document.getElementById(modalId); if (m) m.style.display = 'none'; window.UsuarioController._dirty = false; } });
    },

    _submitPassword: function () {
        const m = document.getElementById('reset-password-modal');
        const id = parseInt(m?.dataset.userId || '0', 10);
        const pwd = (document.getElementById('reset-new-password')?.value || '').trim();
        if (!id || pwd.length < 8) { window.Toast.error('Mínimo 8 caracteres'); return; }
        window.ApiClient.post('cambiar_password.php', { user_id: id, password_nueva: pwd })
            .then(res => {
                if (res.status !== 'success') throw new Error(res.message || 'Error');
                window.Toast.success('Contraseña actualizada');
                m.style.display = 'none';
                window.UsuarioController._dirty = false;
            })
            .catch(err => window.Toast.error(err.message || 'Error'));
    }
};

// Inputs marcan dirty al editar
['new-user-username','new-user-email','reset-new-password'].forEach(id => {
    document.addEventListener('input', (ev) => {
        if (ev.target && ev.target.id === id) window.UsuarioController._dirty = true;
    });
});
['edit-user-username','edit-user-email'].forEach(id => {
    document.addEventListener('input', (ev) => {
        if (ev.target && ev.target.id === id) window.UsuarioController._dirty = true;
    });
});
// 4.2-hotfix1: solo new-user-roles-checkboxes. edit-user-roles-checkboxes eliminado del HTML.
['new-user-roles-checkboxes'].forEach(id => {
    document.addEventListener('change', (ev) => {
        if (ev.target && ev.target.closest(`#${id}`)) window.UsuarioController._dirty = true;
    });
});