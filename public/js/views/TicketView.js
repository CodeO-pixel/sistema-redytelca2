// js/views/TicketView.js
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

    function getStatusClass(status) {
        const s = String(status || '').toLowerCase();
        if (s === 'cerrado') return 'status-ok';
        if (s === 'en proceso') return 'status-warning';
        return 'status-alert'; // Abierto
    }

    function getPriorityClass(prioridad) {
        const s = String(prioridad || '').toLowerCase();
        if (s === 'alta') return 'status-alert';
        if (s === 'baja') return 'status-ok';
        return 'status-warning'; // Media
    }

    function formatSla(val) {
        if (!val) return '—';
        return String(val).replace('T', ' ').slice(0, 16);
    }

    function renderCategoriaBadge(categoriaNombre, categoriaColor, search) {
        const nombre = categoriaNombre || 'Sin categoría';
        if (!categoriaColor) {
            return mark(nombre, search);
        }
        return `<span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:${esc(categoriaColor)}; border:1px solid #cbd5e1; margin-right:6px; vertical-align:middle;"></span>${mark(nombre, search)}`;
    }

    window.TicketView = {
        renderTable: function (tickets, search) {
            const tbody = document.getElementById('ticket-list');
            if (!tbody) return;

            if (tickets.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay tickets todavía. <a href="#" data-action="new-ticket-empty">Crear el primero</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('tickets-search');
                    if (i) i.value = '';
                    window.TicketController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-ticket-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm(false, true);
                });
                return;
            }

            tbody.innerHTML = tickets.map(t => {
                const nombreCliente = `${t.nombres || ''} ${t.apellidos || ''}`.trim() || 'Sin cliente';
                const id = t.id;
                const estado = String(t.estado || '');
                const estaCerrado = estado.toLowerCase() === 'cerrado';

                const catCell = renderCategoriaBadge(t.categoria_nombre, t.categoria_color, search);

                const btnRespuestas = `<button type="button" class="table-action btn-ver-respuestas" data-id="${esc(id)}" style="width:auto; padding:4px 8px;">Ver hilo</button>`;
                const btnCerrar = estaCerrado
                    ? ''
                    : `<button type="button" class="table-action btn-cerrar-ticket" data-id="${esc(id)}" style="width:auto; padding:4px 8px; background:var(--success); color:#fff; border:none;">Cerrar</button>`;

                return `
                <tr data-module-row="tickets" data-id="${esc(id)}">
                    <td>${mark(t.asunto, search)}${t.creador ? `<br><small style="color:#64748b;">Creado por: ${esc(t.creador)}</small>` : ''}</td>
                    <td>${mark(nombreCliente, search)}</td>
                    <td>${mark(t.cedula || '—', search)}</td>
                    <td>${catCell}</td>
                    <td><span class="status ${getPriorityClass(t.prioridad)}">${esc(t.prioridad)}</span></td>
                    <td><span class="status ${getStatusClass(t.estado)}">${esc(t.estado)}</span></td>
                    <td>${esc(formatSla(t.sla_vence_en))}</td>
                    <td style="display:flex; gap:6px; flex-wrap:wrap;">${btnRespuestas}${btnCerrar}</td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="tickets"]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-ver-respuestas') || e.target.closest('.btn-cerrar-ticket')) return;
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['tickets'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="tickets"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['tickets'] = id;
                    tr.classList.add('selected-row');

                    const t = window.TicketModel.getById(id);
                    const estaCerrado = t && String(t.estado).toLowerCase() === 'cerrado';

                    document.querySelectorAll('.toolbar-btn[data-toolbar="tickets"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                        else if (action === 'cerrar') btn.disabled = estaCerrado;
                        else if (action === 'new-tarea') btn.disabled = false;
                        else if (action === 'new-pedido') btn.disabled = false;
                    });
                });
            });

            tbody.querySelectorAll('.btn-ver-respuestas').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.TicketController) window.TicketController.verRespuestas(id);
                });
            });

            tbody.querySelectorAll('.btn-cerrar-ticket').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.TicketController) window.TicketController.abrirCierreTicket(id);
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('tickets-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-tickets-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-tickets-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-tickets-prev')?.addEventListener('click', () => {
                if (window.TicketController) window.TicketController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-tickets-next')?.addEventListener('click', () => {
                if (window.TicketController) window.TicketController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('tickets-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        /**
         * Puebla los selects del modal de ticket.
         * IMPORTANTE: siempre setea el valor (a '' si es null/falsy) para
         * evitar que quede pegado el valor de la edición anterior (Bug B3.8-1).
         */
        populateSelects: function (clienteId = null, servicioId = null, categoriaId = null) {
            // Cliente
            const selCli = document.getElementById('ticket-client');
            if (selCli) {
                const clientes = window.ClienteModel ? window.ClienteModel.cache : [];
                let html = '<option value="">Selecciona un cliente</option>';
                html += clientes.map(c => {
                    const id = c.id || c.id_cliente;
                    const nombre = `${c.nombre || c.nombres || ''} ${c.apellido || c.apellidos || ''}`.trim() || c.cedula || 'Cliente';
                    const ci = c.cedula ? ` (C.I. ${c.cedula})` : '';
                    return `<option value="${esc(id)}">${esc(nombre)}${esc(ci)}</option>`;
                }).join('');
                selCli.innerHTML = html;
                selCli.value = clienteId ? String(clienteId) : '';
            }

            // Servicio (filtrado por cliente si aplica)
            const selSrv = document.getElementById('ticket-servicio');
            if (selSrv) {
                const servicios = window.ServicioModel ? window.ServicioModel.cache : [];
                const filtroCliente = clienteId || (selCli ? selCli.value : '');
                const filtered = filtroCliente
                    ? servicios.filter(s => String(s.cliente_id || s.id_cliente) === String(filtroCliente))
                    : servicios;
                let html = '<option value="">Sin servicio asociado</option>';
                html += filtered.filter(s => s.estado_comercial !== 'retirado').map(s => {
                    const id = s.id || s.id_servicio;
                    const nombre = s.alias || `${s.nombres || ''} ${s.apellidos || ''}`.trim() || `Servicio #${id}`;
                    const plan = s.plan_nombre ? ` — ${s.plan_nombre}` : '';
                    return `<option value="${esc(id)}">${esc(nombre)}${esc(plan)}</option>`;
                }).join('');
                selSrv.innerHTML = html;
                selSrv.value = servicioId ? String(servicioId) : '';
            }

            // Categoría
            const selCat = document.getElementById('ticket-categoria');
            if (selCat) {
                const cats = window.TicketModel ? window.TicketModel.categoriasCache : [];
                let html = '<option value="">Sin categoría</option>';
                html += cats.filter(c => Number(c.activa) === 1).map(c =>
                    `<option value="${esc(c.id)}">${esc(c.nombre)}</option>`
                ).join('');
                selCat.innerHTML = html;
                selCat.value = categoriaId ? String(categoriaId) : '';
            }
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('ticket-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['ticket-id', 'ticket-subject', 'ticket-description'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                const est = document.getElementById('ticket-status');
                if (est) {
                    est.value = 'Abierto';
                    est.disabled = false;
                    Array.from(est.options).forEach(opt => { opt.style.display = ''; });
                }
                const pri = document.getElementById('ticket-priority');
                if (pri) pri.value = 'Media';
                if (window.TicketController) window.TicketController._dirty = false;
                this.populateSelects(null, null, null);
                const first = document.getElementById('ticket-subject');
                if (first) setTimeout(() => first.focus(), 50);
            }
        },

        fillForm: function (t) {
            document.getElementById('ticket-id').value = t.id || '';
            document.getElementById('ticket-subject').value = t.asunto || '';
            document.getElementById('ticket-description').value = t.descripcion || '';
            document.getElementById('ticket-priority').value = t.prioridad || 'Media';

            // Estado: terminal en 'Cerrado'
            const est = document.getElementById('ticket-status');
            if (est) {
                const estaCerrado = String(t.estado || '').toLowerCase() === 'cerrado';
                est.value = t.estado || 'Abierto';
                est.disabled = estaCerrado;
            }

            // Poblar selects SIEMPRE con los valores del ticket, incluidos nulls (Bug B3.8-1)
            this.populateSelects(
                t.cliente_id || null,
                t.servicio_id || null,
                t.categoria_id || null
            );

            if (window.TicketController) window.TicketController._dirty = false;
        },

        renderRespuestasModal: function (ticket, respuestas) {
            const m = document.getElementById('ticket-respuestas-modal');
            const list = document.getElementById('ticket-respuestas-list');
            const title = document.getElementById('ticket-respuestas-title');
            const idInput = document.getElementById('ticket-respuesta-id-ticket');
            const msgInput = document.getElementById('ticket-respuesta-mensaje');
            if (!m || !list) return;

            if (title) title.textContent = `Hilo de respuestas — ${ticket.asunto || 'Ticket'}`;
            if (idInput) idInput.value = ticket.id || '';
            if (msgInput) msgInput.value = '';

            if (!respuestas || respuestas.length === 0) {
                list.innerHTML = '<p style="margin:0; color:#64748b;">Sin respuestas todavía. Sé el primero en responder.</p>';
            } else {
                list.innerHTML = respuestas.map(r => `
                    <div style="padding:10px; margin-bottom:8px; background:white; border-radius:8px; border:1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                            <strong style="font-size:0.85rem;">${esc(r.tipo_autor || 'staff')}${r.usuario_id ? ' (ID ' + esc(r.usuario_id) + ')' : (r.credencial_id ? ' (cliente)' : '')}</strong>
                            <small style="color:#64748b;">${esc(r.creado_en || '')}</small>
                        </div>
                        <p style="margin:0; font-size:0.9rem; white-space:pre-wrap;">${esc(r.mensaje)}</p>
                    </div>
                `).join('');
            }

            m.style.display = 'flex';
            if (msgInput) setTimeout(() => msgInput.focus(), 50);
        },

        _onClienteChange: function () {
            const selCli = document.getElementById('ticket-client');
            if (!selCli) return;
            // Preservar la categoría elegida por el usuario
            const catActual = document.getElementById('ticket-categoria')?.value || null;
            this.populateSelects(selCli.value || null, null, catActual);
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['tickets'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="tickets"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (['edit', 'delete', 'cerrar', 'new-tarea', 'new-pedido'].includes(action)) btn.disabled = true;
            });

            document.querySelectorAll('.toolbar-btn[data-toolbar="tickets"]').forEach(btn => {
                btn.removeAttribute('onclick');
                const action = btn.dataset.action || '';
                if (action === 'new') {
                    btn.addEventListener('click', () => this.toggleForm());
                } else if (action === 'edit') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['tickets'] : null;
                        if (id) window.TicketController.edit(id);
                    });
                } else if (action === 'delete') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['tickets'] : null;
                        if (id) window.TicketController.confirmDelete(id);
                    });
                } else if (action === 'cerrar') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['tickets'] : null;
                        if (id) window.TicketController.abrirCierreTicket(id);
                    });
                } else if (action === 'new-tarea') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['tickets'] : null;
                        if (id) {
                            const t = window.TicketModel.getById(id);
                            if (t && window.TareaController) window.TareaController.openNewFromTicket(t);
                        }
                    });
                } else if (action === 'new-pedido') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['tickets'] : null;
                        if (id) {
                            const t = window.TicketModel.getById(id);
                            if (t && window.PedidoController) window.PedidoController.openNewFromTicket(t);
                        }
                    });
                } else if (action === 'export-pdf') {
                    btn.addEventListener('click', () => window.TicketController.exportClosedTicketsPdf());
                } else if (action === 'goto-categorias') {
                    btn.addEventListener('click', () => window.Router.showPage('categorias-ticket'));
                } else if (action === 'goto-sla') {
                    btn.addEventListener('click', () => window.Router.showPage('sla-definiciones'));
                }
            });

            document.querySelectorAll('#ticket-filters [data-ticket-filter]').forEach(pill => {
                pill.addEventListener('click', () => {
                    document.querySelectorAll('#ticket-filters [data-ticket-filter]').forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    if (window.TicketController) window.TicketController.setFilter(pill.dataset.ticketFilter);
                });
            });

            const search = document.getElementById('tickets-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.TicketController._searchTimer);
                    window.TicketController._searchTimer = setTimeout(() => window.TicketController.renderPage(1), 250);
                });
            }

            // Modal ticket: save/cancel/close
            document.querySelectorAll('#ticket-modal [data-action="save-ticket"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.TicketController.save());
            });
            document.querySelectorAll('#ticket-modal [data-action="cancel-ticket"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => this.toggleForm(true));
            });

            const selCli = document.getElementById('ticket-client');
            if (selCli) {
                selCli.removeAttribute('onchange');
                selCli.addEventListener('change', () => this._onClienteChange());
            }

            document.querySelectorAll('#close-ticket-modal [data-action="cancel-close-ticket"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('close-ticket-modal');
                    if (m) m.style.display = 'none';
                });
            });
            document.querySelectorAll('#close-ticket-modal [data-action="confirm-close-ticket"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.TicketController.confirmarCierreTicket());
            });

            document.querySelectorAll('#ticket-respuestas-modal [data-action="close-respuestas-modal"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('ticket-respuestas-modal');
                    if (m) m.style.display = 'none';
                });
            });
            document.querySelectorAll('#ticket-respuestas-modal [data-action="save-respuesta"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.TicketController.crearRespuesta());
            });

            ['ticket-subject', 'ticket-description'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', () => { window.TicketController._dirty = true; });
            });
            ['ticket-status', 'ticket-priority', 'ticket-client', 'ticket-servicio', 'ticket-categoria'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', () => { window.TicketController._dirty = true; });
            });

            const form = document.getElementById('ticket-form');
            if (form) {
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                        e.preventDefault();
                        window.TicketController.save();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this.toggleForm(true);
                    }
                });
            }
        }
    };
})();