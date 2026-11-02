// js/views/CategoriaTicketView.js
(function () {
    'use strict';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function mark(txt, search) {
        const s = window.normalizeForSearch(search || '');
        if (!s) return esc(txt);
        const raw = String(txt ?? '');
        const idx = window.normalizeForSearch(raw).indexOf(s);
        if (idx < 0) return esc(raw);
        return esc(raw.slice(0, idx)) + '<mark>' + esc(raw.slice(idx, idx + s.length)) + '</mark>' + esc(raw.slice(idx + s.length));
    }

    window.CategoriaTicketView = {
        renderTable: function (categorias, search) {
            const tbody = document.getElementById('categorias-ticket-list');
            if (!tbody) return;

            if (categorias.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay categorías todavía. <a href="#" data-action="new-categoria-empty">Crear la primera</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('categorias-ticket-search');
                    if (i) i.value = '';
                    window.CategoriaTicketController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-categoria-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm();
                });
                return;
            }

            tbody.innerHTML = categorias.map(c => {
                const activa = Number(c.activa) === 1;
                const badgeActiva = activa
                    ? '<span class="status status-ok">Activa</span>'
                    : '<span class="status status-alert">Inactiva</span>';
                const swatch = `<span style="display:inline-block; width:22px; height:22px; border-radius:4px; background:${esc(c.color || '#104aa8')}; border:1px solid #cbd5e1; vertical-align:middle;"></span>`;
                return `
                <tr data-module-row="categorias-ticket" data-id="${esc(c.id)}">
                    <td>${esc(c.id)}</td>
                    <td>${mark(c.nombre, search)}</td>
                    <td>${swatch}</td>
                    <td>${badgeActiva}</td>
                    <td>${mark(c.descripcion || '—', search)}</td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="categorias-ticket"]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['categorias-ticket'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="categorias-ticket"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['categorias-ticket'] = id;
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-toolbar="categorias-ticket"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                    });
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('categorias-ticket-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-cat-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-cat-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-cat-prev')?.addEventListener('click', () => {
                if (window.CategoriaTicketController) window.CategoriaTicketController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-cat-next')?.addEventListener('click', () => {
                if (window.CategoriaTicketController) window.CategoriaTicketController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('categoria-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                const title = document.getElementById('categoria-modal-title');
                if (title) title.textContent = 'Nueva categoría';
                const idEl = document.getElementById('categoria-id'); if (idEl) idEl.value = '';
                const nombre = document.getElementById('categoria-nombre'); if (nombre) nombre.value = '';
                const color = document.getElementById('categoria-color'); if (color) color.value = '#104aa8';
                const activa = document.getElementById('categoria-activa'); if (activa) activa.value = '1';
                const desc = document.getElementById('categoria-descripcion'); if (desc) desc.value = '';
                if (window.CategoriaTicketController) window.CategoriaTicketController._dirty = false;
                if (nombre) setTimeout(() => nombre.focus(), 50);
            }
        },

        fillForm: function (c) {
            const title = document.getElementById('categoria-modal-title');
            if (title) title.textContent = 'Editar categoría';
            document.getElementById('categoria-id').value = c.id || '';
            document.getElementById('categoria-nombre').value = c.nombre || '';
            document.getElementById('categoria-color').value = c.color || '#104aa8';
            document.getElementById('categoria-activa').value = String(Number(c.activa) === 1 ? 1 : 0);
            document.getElementById('categoria-descripcion').value = c.descripcion || '';
            if (window.CategoriaTicketController) window.CategoriaTicketController._dirty = false;
        },

        _requestClose: function () {
            if (!window.CategoriaTicketController || !window.CategoriaTicketController._dirty) {
                this.toggleForm(true);
                return;
            }
            window.Modal.confirm('¿Descartar cambios sin guardar?', { danger: true, confirmText: 'Descartar' })
                .then(ok => {
                    if (ok) {
                        this.toggleForm(true);
                        if (window.CategoriaTicketController) window.CategoriaTicketController._dirty = false;
                    }
                });
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['categorias-ticket'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="categorias-ticket"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            document.querySelectorAll('.toolbar-btn[data-toolbar="categorias-ticket"]').forEach(btn => {
                btn.removeAttribute('onclick');
                const action = btn.dataset.action || '';
                if (action === 'new') {
                    btn.addEventListener('click', () => this.toggleForm());
                } else if (action === 'edit') {
                    // FIX B3.8-3: corchetes, no property access con guion
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['categorias-ticket'] : null;
                        if (id) window.CategoriaTicketController.edit(id);
                    });
                } else if (action === 'delete') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['categorias-ticket'] : null;
                        if (id) window.CategoriaTicketController.confirmDelete(id);
                    });
                } else if (action === 'back-to-tickets') {
                    btn.addEventListener('click', () => window.Router.showPage('tickets'));
                }
            });

            const search = document.getElementById('categorias-ticket-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.CategoriaTicketController._searchTimer);
                    window.CategoriaTicketController._searchTimer = setTimeout(() => window.CategoriaTicketController.renderPage(1), 250);
                });
            }

            document.querySelectorAll('#categoria-modal [data-action="save-categoria"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.CategoriaTicketController.save());
            });
            document.querySelectorAll('#categoria-modal [data-action="cancel-categoria"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => this._requestClose());
            });

            ['categoria-nombre', 'categoria-color', 'categoria-activa', 'categoria-descripcion'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => { window.CategoriaTicketController._dirty = true; });
                    el.addEventListener('change', () => { window.CategoriaTicketController._dirty = true; });
                }
            });

            const form = document.getElementById('categoria-form');
            if (form) {
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                        e.preventDefault();
                        window.CategoriaTicketController.save();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this._requestClose();
                    }
                });
            }
        }
    };
})();