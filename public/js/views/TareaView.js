// js/views/TareaView.js
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

    function getStatusClass(estado) {
        const s = String(estado || '').toLowerCase();
        if (s === 'completada') return 'status-ok';
        if (s === 'en curso') return 'status-warning';
        return 'status-alert'; // Pendiente
    }

    function getPriorityClass(prioridad) {
        const s = String(prioridad || '').toLowerCase();
        if (s === 'alta') return 'status-alert';
        if (s === 'baja') return 'status-ok';
        return 'status-warning'; // Media
    }

    function labelServicio(t) {
        const alias = t.servicio_alias ? String(t.servicio_alias).trim() : '';
        if (alias) return alias;
        const dir = t.servicio_direccion ? String(t.servicio_direccion).trim() : '';
        if (dir) return dir;
        return '';
    }

    window.TareaView = {
        renderTable: function (tareas, search) {
            const tbody = document.getElementById('tareas-list');
            if (!tbody) return;

            if (tareas.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay tareas todavía. <a href="#" data-action="new-tarea-empty">Crear la primera</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('tareas-search');
                    if (i) i.value = '';
                    window.TareaController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-tarea-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm();
                });
                return;
            }

            tbody.innerHTML = tareas.map(t => {
                const nombreCliente = `${t.nombres || ''} ${t.apellidos || ''}`.trim();
                const cedula = t.cedula || '';
                const creador = t.creador || '';
                const servicio = labelServicio(t);
                const desc = t.descripcion
                    ? `<br><small style="color:#64748b;">${esc(t.descripcion)}</small>`
                    : '';
                return `
                <tr data-module-row="tareas" data-id="${esc(t.id)}">
                    <td>${mark(t.titulo, search)}${desc}</td>
                    <td>${nombreCliente ? mark(nombreCliente, search) : '<span class="text-muted">Sin cliente</span>'}</td>
                    <td>${cedula ? mark(cedula, search) : '—'}</td>
                    <td>${servicio ? mark(servicio, search) : '<span class="text-muted">Sin servicio</span>'}</td>
                    <td>${creador ? mark(creador, search) : '—'}</td>
                    <td><span class="status ${getPriorityClass(t.prioridad)}">${esc(t.prioridad)}</span></td>
                    <td><span class="status ${getStatusClass(t.estado)}">${esc(t.estado)}</span></td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="tareas"]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['tareas'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="tareas"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['tareas'] = id;
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-toolbar="tareas"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                    });
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('tareas-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-tareas-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-tareas-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-tareas-prev')?.addEventListener('click', () => {
                if (window.TareaController) window.TareaController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-tareas-next')?.addEventListener('click', () => {
                if (window.TareaController) window.TareaController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('tareas-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        populateSelects: function (clienteId = null, selectedTicketId = null, selectedServicioId = null) {
            // Cliente
            const selCli = document.getElementById('task-client');
            if (selCli) {
                const clientes = window.ClienteModel ? window.ClienteModel.cache : [];
                let html = '<option value="">Sin cliente</option>';
                html += clientes.map(c => {
                    const id = c.id || c.id_cliente;
                    const nombre = `${c.nombre || c.nombres || ''} ${c.apellido || c.apellidos || ''}`.trim() || 'Cliente';
                    const ci = c.cedula ? ` (C.I. ${c.cedula})` : '';
                    return `<option value="${esc(id)}">${esc(nombre)}${esc(ci)}</option>`;
                }).join('');
                selCli.innerHTML = html;
                selCli.value = clienteId ? String(clienteId) : '';
            }

            // Ticket (filtrado por cliente si aplica)
            const selTkt = document.getElementById('task-ticket');
            if (selTkt) {
                const tickets = window.TicketModel ? window.TicketModel.cache : [];
                const filtered = clienteId
                    ? tickets.filter(t => String(t.cliente_id) === String(clienteId))
                    : [];
                let html = '<option value="">Sin ticket</option>';
                html += filtered.map(t => {
                    const label = `#${t.id} — ${t.asunto || 'Ticket'}`;
                    return `<option value="${esc(t.id)}">${esc(label)}</option>`;
                }).join('');
                // Fallback: si selectedTicketId no está en la lista filtrada
                if (selectedTicketId && !filtered.some(t => String(t.id) === String(selectedTicketId))) {
                    const t = tickets.find(x => String(x.id) === String(selectedTicketId));
                    if (t) {
                        const label = `#${t.id} — ${t.asunto || 'Ticket'}`;
                        html += `<option value="${esc(t.id)}">${esc(label)} (fuera de filtro)</option>`;
                    }
                }
                selTkt.innerHTML = html;
                selTkt.value = selectedTicketId ? String(selectedTicketId) : '';
            }

            // Servicio (filtrado por cliente si aplica)
            const selSrv = document.getElementById('task-servicio');
            if (selSrv) {
                const servicios = window.ServicioModel ? window.ServicioModel.cache : [];
                const filtered = clienteId
                    ? servicios.filter(s => String(s.cliente_id) === String(clienteId))
                    : [];
                let html = '<option value="">Sin servicio</option>';
                html += filtered.map(s => {
                    const id = s.id || s.id_servicio;
                    const alias = s.alias || `${s.nombres || ''} ${s.apellidos || ''}`.trim() || `Servicio #${id}`;
                    const dir = s.direccion_texto ? ` — ${s.direccion_texto}` : '';
                    return `<option value="${esc(id)}">${esc(alias)}${esc(dir)}</option>`;
                }).join('');
                if (selectedServicioId && !filtered.some(s => String(s.id || s.id_servicio) === String(selectedServicioId))) {
                    const s = servicios.find(x => String(x.id || x.id_servicio) === String(selectedServicioId));
                    if (s) {
                        const alias = s.alias || `Servicio #${s.id}`;
                        html += `<option value="${esc(s.id || s.id_servicio)}">${esc(alias)} (fuera de filtro)</option>`;
                    }
                }
                selSrv.innerHTML = html;
                selSrv.value = selectedServicioId ? String(selectedServicioId) : '';
            }
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('task-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['task-id', 'task-title', 'task-description'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                const est = document.getElementById('task-status');
                if (est) est.value = 'Pendiente';
                const pri = document.getElementById('task-priority');
                if (pri) pri.value = 'Media';
                const title = document.getElementById('task-modal-title');
                if (title) title.textContent = 'Nueva tarea';
                ['task-client', 'task-ticket', 'task-servicio'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = false;
                });
                this.populateSelects(null, null, null);
                if (window.TareaController) window.TareaController._dirty = false;
                const first = document.getElementById('task-title');
                if (first) setTimeout(() => first.focus(), 50);
            }
        },

        fillForm: function (t) {
            const title = document.getElementById('task-modal-title');
            if (title) title.textContent = 'Editar tarea';
            document.getElementById('task-id').value = t.id || '';
            document.getElementById('task-title').value = t.titulo || '';
            document.getElementById('task-description').value = t.descripcion || '';
            document.getElementById('task-status').value = t.estado || 'Pendiente';
            document.getElementById('task-priority').value = t.prioridad || 'Media';
            // Re-habilitar selects: si venían de "Nueva tarea desde Tickets"
            // quedaron bloqueados, y al editar no deben estarlo.
            ['task-client', 'task-ticket', 'task-servicio'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = false;
            });
            this.populateSelects(t.cliente_id, t.ticket_id, t.servicio_id);
            if (window.TareaController) window.TareaController._dirty = false;
        },

        _requestClose: function () {
            if (!window.TareaController || !window.TareaController._dirty) {
                this.toggleForm(true);
                return;
            }
            window.Modal.confirm('¿Descartar cambios sin guardar?', { danger: true, confirmText: 'Descartar' })
                .then(ok => {
                    if (ok) {
                        this.toggleForm(true);
                        if (window.TareaController) window.TareaController._dirty = false;
                    }
                });
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['tareas'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="tareas"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            document.querySelectorAll('.toolbar-btn[data-toolbar="tareas"]').forEach(btn => {
                btn.removeAttribute('onclick');
                const action = btn.dataset.action || '';
                if (action === 'new') {
                    btn.addEventListener('click', () => this.toggleForm());
                } else if (action === 'edit') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['tareas'] : null;
                        if (id) window.TareaController.edit(id);
                    });
                } else if (action === 'delete') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['tareas'] : null;
                        if (id) window.TareaController.confirmDelete(id);
                    });
                }
            });

            const search = document.getElementById('tareas-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.TareaController._searchTimer);
                    window.TareaController._searchTimer = setTimeout(() => window.TareaController.renderPage(1), 250);
                });
            }

            document.querySelectorAll('#task-modal [data-action="save-tarea"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.TareaController.save());
            });
            document.querySelectorAll('#task-modal [data-action="cancel-tarea"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => this._requestClose());
            });

            const cli = document.getElementById('task-client');
            if (cli) {
                cli.removeAttribute('onchange');
                cli.addEventListener('change', () => window.TareaController._onClienteChange());
            }

            ['task-title', 'task-description'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', () => { window.TareaController._dirty = true; });
            });
            ['task-status', 'task-priority', 'task-client', 'task-ticket', 'task-servicio'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', () => { window.TareaController._dirty = true; });
            });

            const form = document.getElementById('task-form');
            if (form) {
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                        e.preventDefault();
                        window.TareaController.save();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this._requestClose();
                    }
                });
            }
        }
    };
})();