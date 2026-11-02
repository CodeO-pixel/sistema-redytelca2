// js/views/RolView.js
(function () {
    'use strict';

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function mark(txt, search) {
        const s = window.normalizeForSearch(search || '');
        if (!s) return esc(txt);
        const raw = String(txt ?? '');
        const idx = window.normalizeForSearch(raw).indexOf(s);
        if (idx < 0) return esc(raw);
        return esc(raw.slice(0, idx)) + '<mark>' + esc(raw.slice(idx, idx + s.length)) + '</mark>' + esc(raw.slice(idx + s.length));
    }

    window.RolView = {
        renderTable: function (roles, search) {
            const tbody = document.getElementById('roles-list');
            if (!tbody) return;

            if (roles.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="2" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay roles todavía. <a href="#" data-action="new-role-empty">Crear el primero</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('roles-search');
                    if (i) i.value = '';
                    window.RolController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-role-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm();
                });
                return;
            }

            tbody.innerHTML = roles.map(r => `
                <tr data-module-row="roles" data-id="${esc(r.id_rol)}">
                    <td><strong>${mark(r.id_rol, search)}</strong></td>
                    <td>${mark(r.nombre_rol, search)}</td>
                </tr>
            `).join('');

            tbody.querySelectorAll('tr[data-module-row="roles"]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['roles'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="roles"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['roles'] = id;
                    tr.classList.add('selected-row');

                    const isAdmin = String(id) === '1';
                    document.querySelectorAll('.toolbar-btn[data-module="roles"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = isAdmin;
                    });
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('roles-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-roles-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-roles-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-roles-prev')?.addEventListener('click', () => {
                if (window.RolController) window.RolController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-roles-next')?.addEventListener('click', () => {
                if (window.RolController) window.RolController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('roles-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('role-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                const idInput = document.getElementById('role-id');
                const nameInput = document.getElementById('role-name');
                const feedback = document.getElementById('role-feedback');
                const title = document.getElementById('role-modal-title');
                if (idInput) idInput.value = '';
                if (nameInput) { nameInput.value = ''; nameInput.focus(); }
                if (feedback) feedback.textContent = '';
                if (title) title.textContent = 'Nuevo rol';
                if (window.RolController) window.RolController._dirty = false;
            }
        },

        fillForm: function (role) {
            const idInput = document.getElementById('role-id');
            const nameInput = document.getElementById('role-name');
            const title = document.getElementById('role-modal-title');
            const feedback = document.getElementById('role-feedback');
            if (idInput) idInput.value = role.id_rol;
            if (nameInput) { nameInput.value = role.nombre_rol; nameInput.focus(); }
            if (title) title.textContent = 'Editar rol';
            if (feedback) feedback.textContent = '';
            if (window.RolController) window.RolController._dirty = false;
        },

        _requestClose: function () {
            if (!window.RolController || !window.RolController._dirty) {
                this.toggleForm(true);
                return;
            }
            window.Modal.confirm('¿Descartar cambios sin guardar?', { danger: true, confirmText: 'Descartar' })
                .then(ok => {
                    if (ok) {
                        this.toggleForm(true);
                        if (window.RolController) window.RolController._dirty = false;
                    }
                });
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['roles'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="roles"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="roles"] [data-action="new"]');
            if (btnNew) {
                btnNew.removeAttribute('onclick');
                btnNew.addEventListener('click', () => this.toggleForm());
            }

            const btnEdit = document.querySelector('.toolbar[data-toolbar="roles"] [data-action="edit"]');
            if (btnEdit) {
                btnEdit.removeAttribute('onclick');
                btnEdit.addEventListener('click', () => {
                    const id = window.selectedIds?.roles;
                    if (id && window.RolController) window.RolController.edit(id);
                });
            }

            const btnDelete = document.querySelector('.toolbar[data-toolbar="roles"] [data-action="delete"]');
            if (btnDelete) {
                btnDelete.removeAttribute('onclick');
                btnDelete.addEventListener('click', () => {
                    const id = window.selectedIds?.roles;
                    if (id && window.RolController) window.RolController.confirmDelete(id);
                });
            }

            const search = document.getElementById('roles-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.RolController._searchTimer);
                    window.RolController._searchTimer = setTimeout(() => window.RolController.renderPage(1), 250);
                });
            }

            const btnSave = document.querySelector('#role-form [data-action="save-role"]');
            if (btnSave) {
                btnSave.removeAttribute('onclick');
                btnSave.addEventListener('click', () => {
                    if (window.RolController) window.RolController.save();
                });
            }

            const btnCancel = document.querySelector('#role-form [data-action="cancel-role"]');
            if (btnCancel) {
                btnCancel.removeAttribute('onclick');
                btnCancel.addEventListener('click', () => this._requestClose());
            }

            const btnClose = document.querySelector('#role-modal .modal-close');
            if (btnClose) {
                btnClose.removeAttribute('onclick');
                btnClose.addEventListener('click', () => this._requestClose());
            }

            ['role-name'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => {
                        if (window.RolController) window.RolController._dirty = true;
                    });
                }
            });

            const form = document.getElementById('role-form');
            if (form) {
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (window.RolController) window.RolController.save();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this._requestClose();
                    }
                });
            }
        }
    };
})();