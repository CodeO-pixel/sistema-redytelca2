// js/views/SlaDefinicionView.js
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

    function getPriorityClass(prioridad) {
        const s = String(prioridad || '').toLowerCase();
        if (s === 'alta') return 'status-alert';
        if (s === 'baja') return 'status-ok';
        return 'status-warning';
    }

    window.SlaDefinicionView = {
        renderTable: function (slas, search) {
            const tbody = document.getElementById('sla-definiciones-list');
            if (!tbody) return;

            if (slas.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay definiciones SLA todavía. <a href="#" data-action="new-sla-empty">Crear la primera</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('sla-definiciones-search');
                    if (i) i.value = '';
                    window.SlaDefinicionController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-sla-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm();
                });
                return;
            }

            tbody.innerHTML = slas.map(s => {
                const catNombre = s.categoria_nombre || '(categoría eliminada)';
                return `
                <tr data-module-row="sla-definiciones" data-id="${esc(s.id)}">
                    <td>${mark(catNombre, search)}</td>
                    <td><span class="status ${getPriorityClass(s.prioridad)}">${esc(s.prioridad)}</span></td>
                    <td>${esc(s.horas_respuesta)} h</td>
                    <td>${esc(s.horas_resolucion)} h</td>
                    <td>${mark(s.descripcion || '—', search)}</td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="sla-definiciones"]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['sla-definiciones'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="sla-definiciones"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['sla-definiciones'] = id;
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-toolbar="sla-definiciones"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                    });
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('sla-definiciones-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-sla-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-sla-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-sla-prev')?.addEventListener('click', () => {
                if (window.SlaDefinicionController) window.SlaDefinicionController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-sla-next')?.addEventListener('click', () => {
                if (window.SlaDefinicionController) window.SlaDefinicionController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        populateSelects: function (selectedCategoriaId = null) {
            const sel = document.getElementById('sla-categoria');
            if (!sel) return;
            const cats = window.CategoriaTicketModel ? window.CategoriaTicketModel.cache : [];
            let html = '<option value="">Selecciona una categoría</option>';
            html += cats.map(c => {
                const inactiva = Number(c.activa) === 0 ? ' (inactiva)' : '';
                return `<option value="${esc(c.id)}">${esc(c.nombre)}${esc(inactiva)}</option>`;
            }).join('');
            sel.innerHTML = html;
            if (selectedCategoriaId) sel.value = String(selectedCategoriaId);
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('sla-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                const title = document.getElementById('sla-modal-title');
                if (title) title.textContent = 'Nueva definición SLA';
                const idEl = document.getElementById('sla-id'); if (idEl) idEl.value = '';
                const pri = document.getElementById('sla-prioridad'); if (pri) pri.value = 'Media';
                const hr = document.getElementById('sla-horas-respuesta'); if (hr) hr.value = '';
                const hres = document.getElementById('sla-horas-resolucion'); if (hres) hres.value = '';
                const desc = document.getElementById('sla-descripcion'); if (desc) desc.value = '';
                this.populateSelects(null);
                if (window.SlaDefinicionController) window.SlaDefinicionController._dirty = false;
                const cat = document.getElementById('sla-categoria');
                if (cat) setTimeout(() => cat.focus(), 50);
            }
        },

        fillForm: function (s) {
            const title = document.getElementById('sla-modal-title');
            if (title) title.textContent = 'Editar definición SLA';
            document.getElementById('sla-id').value = s.id || '';
            this.populateSelects(s.categoria_id || null);
            document.getElementById('sla-prioridad').value = s.prioridad || 'Media';
            document.getElementById('sla-horas-respuesta').value = s.horas_respuesta != null ? s.horas_respuesta : '';
            document.getElementById('sla-horas-resolucion').value = s.horas_resolucion != null ? s.horas_resolucion : '';
            document.getElementById('sla-descripcion').value = s.descripcion || '';
            if (window.SlaDefinicionController) window.SlaDefinicionController._dirty = false;
        },

        _requestClose: function () {
            if (!window.SlaDefinicionController || !window.SlaDefinicionController._dirty) {
                this.toggleForm(true);
                return;
            }
            window.Modal.confirm('¿Descartar cambios sin guardar?', { danger: true, confirmText: 'Descartar' })
                .then(ok => {
                    if (ok) {
                        this.toggleForm(true);
                        if (window.SlaDefinicionController) window.SlaDefinicionController._dirty = false;
                    }
                });
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['sla-definiciones'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="sla-definiciones"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            document.querySelectorAll('.toolbar-btn[data-toolbar="sla-definiciones"]').forEach(btn => {
                btn.removeAttribute('onclick');
                const action = btn.dataset.action || '';
                if (action === 'new') {
                    btn.addEventListener('click', () => this.toggleForm());
                } else if (action === 'edit') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds?.['sla-definiciones'];
                        if (id) window.SlaDefinicionController.edit(id);
                    });
                } else if (action === 'delete') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds?.['sla-definiciones'];
                        if (id) window.SlaDefinicionController.confirmDelete(id);
                    });
                } else if (action === 'back-to-tickets') {
                    btn.addEventListener('click', () => window.Router.showPage('tickets'));
                }
            });

            const search = document.getElementById('sla-definiciones-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.SlaDefinicionController._searchTimer);
                    window.SlaDefinicionController._searchTimer = setTimeout(() => window.SlaDefinicionController.renderPage(1), 250);
                });
            }

            document.querySelectorAll('#sla-modal [data-action="save-sla"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.SlaDefinicionController.save());
            });
            document.querySelectorAll('#sla-modal [data-action="cancel-sla"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => this._requestClose());
            });

            ['sla-categoria', 'sla-prioridad', 'sla-horas-respuesta', 'sla-horas-resolucion', 'sla-descripcion'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => { window.SlaDefinicionController._dirty = true; });
                    el.addEventListener('change', () => { window.SlaDefinicionController._dirty = true; });
                }
            });

            const form = document.getElementById('sla-form');
            if (form) {
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                        e.preventDefault();
                        window.SlaDefinicionController.save();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this._requestClose();
                    }
                });
            }
        }
    };
})();