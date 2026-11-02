// js/portal/views/TicketView.js
// View del portal para tickets (096d).
//
// Muestra:
//   - Tabla de tickets con Asunto, Categoría, Prioridad, Estado, Creado, SLA, Acciones.
//   - Modal de detalle con hilo de respuestas.
//   - Formulario "Nuevo ticket" con select de categoría y servicio.
//   - Botón "Responder" deshabilitado si el ticket está Cerrado (Zendesk: Closed es terminal).
//
// Formatea timestamps UTC → hora local (VET, UTC-4) con `formatDateTime`.
(function () {
    'use strict';

    const PAGE_SIZE = 20;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function statusClass(estado) {
        const s = String(estado || '').toLowerCase();
        if (s === 'cerrado') return 'portal-status-ok';
        if (s === 'en proceso') return 'portal-status-warning';
        return 'portal-status-alert'; // Abierto
    }

    function priorityClass(prioridad) {
        const s = String(prioridad || '').toLowerCase();
        if (s === 'alta') return 'portal-status-alert';
        if (s === 'baja') return 'portal-status-ok';
        return 'portal-status-warning'; // Media
    }

    function formatDateTime(val) {
        if (!val) return '—';
        const s = String(val).replace('T', ' ').trim();
        const looksNaive = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s);
        const iso = looksNaive ? s.replace(' ', 'T') + 'Z' : s;
        const d = new Date(iso);
        if (isNaN(d.getTime())) return s.slice(0, 16);
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} `
             + `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function categoriaBadge(nombre, color) {
        if (!color) return esc(nombre || 'Sin categoría');
        return `<span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:${esc(color)};border:1px solid #cbd5e1;margin-right:6px;vertical-align:middle;"></span>${esc(nombre || 'Sin categoría')}`;
    }

    window.PortalTicketView = {
        PAGE_SIZE: PAGE_SIZE,

        renderTable: function (items) {
            const tbody = document.getElementById('portal-tickets-list');
            if (!tbody) return;

            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:#64748b;">
                    No tienes tickets registrados. Usa el botón <strong>Nuevo ticket</strong> para reportar un problema.
                </td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(t => {
                const id = t.id;
                const asunto = t.asunto || '—';
                const cat = categoriaBadge(t.categoria_nombre, t.categoria_color);
                const prio = `<span class="portal-status ${priorityClass(t.prioridad)}">${esc(t.prioridad || '—')}</span>`;
                const est = `<span class="portal-status ${statusClass(t.estado)}">${esc(t.estado || '—')}</span>`;
                const creado = formatDateTime(t.creado_en);
                const sla = t.sla_vence_en ? formatDateTime(t.sla_vence_en) : '—';
                return `
                <tr data-module-row="portal-tickets" data-id="${esc(id)}">
                    <td><strong>${esc(asunto)}</strong></td>
                    <td>${cat}</td>
                    <td>${prio}</td>
                    <td>${est}</td>
                    <td>${esc(creado)}</td>
                    <td>${esc(sla)}</td>
                    <td class="portal-cell-actions">
                        <button type="button" class="btn-portal-ver-ticket"
                                data-id="${esc(id)}"
                                style="width:auto;padding:5px 10px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--accent);font-size:0.82rem;cursor:pointer;">
                            Ver detalle
                        </button>
                    </td>
                </tr>`;
            }).join('');

            // Click en "Ver detalle"
            tbody.querySelectorAll('.btn-portal-ver-ticket').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PortalTicketController) {
                        window.PortalTicketController.showDetail(id);
                    }
                });
            });

            // Click en la fila (cualquier otra celda) → también abre detalle.
            tbody.querySelectorAll('tr[data-module-row="portal-tickets"]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-portal-ver-ticket')) return;
                    const id = tr.dataset.id;
                    if (id && window.PortalTicketController) {
                        window.PortalTicketController.showDetail(id);
                    }
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('portal-tickets-pagination');
            if (!pag) return;
            if (totalPages <= 1) {
                pag.innerHTML = '';
                return;
            }
            pag.innerHTML = `
                <div class="pagination-controls">
                    <button type="button" id="btn-portal-tickets-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span class="pagination-info">Página ${page} / ${totalPages}</span>
                    <button type="button" id="btn-portal-tickets-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>`;
            document.getElementById('btn-portal-tickets-prev')?.addEventListener('click', () => {
                if (window.PortalTicketController) {
                    window.PortalTicketController.load(Math.max(1, page - 1));
                }
            });
            document.getElementById('btn-portal-tickets-next')?.addEventListener('click', () => {
                if (window.PortalTicketController) {
                    window.PortalTicketController.load(Math.min(totalPages, page + 1));
                }
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('portal-tickets-counter');
            if (!span) return;
            if (total === 0) {
                span.textContent = '';
                return;
            }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        /**
         * Renderiza el modal de detalle con el hilo de respuestas.
         *
         * @param {Object} ticket
         * @param {Array} respuestas
         */
        renderDetailModal: function (ticket, respuestas) {
            const m = document.getElementById('portal-ticket-detail-modal');
            const content = document.getElementById('portal-ticket-detail-content');
            const title = document.getElementById('portal-ticket-detail-title');
            if (!m || !content) return;

            const id = ticket.id;
            const asunto = ticket.asunto || 'Ticket';
            const estado = String(ticket.estado || '').toLowerCase();
            const esCerrado = estado === 'cerrado';
            const cat = categoriaBadge(ticket.categoria_nombre, ticket.categoria_color);

            if (title) title.textContent = `Ticket #${id} — ${asunto}`;

            const respuestasHtml = (respuestas && respuestas.length > 0)
                ? respuestas.map(r => `
                    <div style="padding:10px;margin-bottom:8px;background:white;border-radius:8px;border:1px solid var(--border);">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <strong style="font-size:0.85rem;">${esc(r.autor_label || r.tipo_autor || '—')}</strong>
                            <small style="color:#64748b;">${esc(formatDateTime(r.creado_en))}</small>
                        </div>
                        <p style="margin:0;font-size:0.9rem;white-space:pre-wrap;">${esc(r.mensaje)}</p>
                    </div>
                `).join('')
                : '<p style="margin:0;color:#64748b;">Sin respuestas todavía. Sé el primero en responder.</p>';

            content.innerHTML = `
                <div class="portal-detail-grid">
                    <div class="portal-detail-card"><span class="portal-detail-label">Asunto</span><div class="portal-detail-value">${esc(asunto)}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Estado</span><div class="portal-detail-value"><span class="portal-status ${statusClass(ticket.estado)}">${esc(ticket.estado || '—')}</span></div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Prioridad</span><div class="portal-detail-value"><span class="portal-status ${priorityClass(ticket.prioridad)}">${esc(ticket.prioridad || '—')}</span></div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Categoría</span><div class="portal-detail-value">${cat}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Servicio</span><div class="portal-detail-value">${esc(ticket.servicio_alias || 'Sin servicio')}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Creado</span><div class="portal-detail-value">${esc(formatDateTime(ticket.creado_en))}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">SLA vence</span><div class="portal-detail-value">${ticket.sla_vence_en ? esc(formatDateTime(ticket.sla_vence_en)) : '—'}</div></div>
                </div>

                <h4 class="portal-detail-section-title">Descripción</h4>
                <div class="portal-detail-card" style="white-space:pre-wrap;">${esc(ticket.descripcion || 'Sin descripción.')}</div>

                <h4 class="portal-detail-section-title">Conversación</h4>
                <div id="portal-ticket-respuestas-list" style="max-height:340px;overflow-y:auto;padding:8px;background:var(--surface);border-radius:10px;border:1px solid var(--border);">
                    ${respuestasHtml}
                </div>

                <div style="margin-top:12px;">
                    <textarea id="portal-ticket-respuesta-mensaje" placeholder="${esCerrado ? 'Ticket cerrado. Crea un ticket nuevo para continuar la conversación.' : 'Escribe una respuesta...'}" ${esCerrado ? 'disabled' : ''} style="min-height:80px;width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;resize:vertical;"></textarea>
                    <input id="portal-ticket-respuesta-id-ticket" type="hidden" value="${esc(id)}">
                    <div style="display:flex;gap:8px;margin-top:8px;">
                        <button type="button" id="portal-ticket-respuesta-submit" ${esCerrado ? 'disabled' : ''} style="width:auto;padding:10px 14px;background:${esCerrado ? '#94a3b8' : 'var(--accent)'};color:#fff;border:none;border-radius:8px;font-weight:600;cursor:${esCerrado ? 'not-allowed' : 'pointer'};">
                            Enviar respuesta
                        </button>
                        <button type="button" data-action="close-ticket-detail" style="width:auto;padding:10px 14px;background:#64748b;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;">
                            Cerrar
                        </button>
                    </div>
                    ${esCerrado ? '<p style="margin:6px 0 0;font-size:0.8rem;color:#92400e;">Este ticket está cerrado. Si necesitas reabrir la conversación, crea un ticket nuevo.</p>' : ''}
                </div>
            `;

            const btnEnviar = document.getElementById('portal-ticket-respuesta-submit');
            if (btnEnviar && !esCerrado) {
                btnEnviar.addEventListener('click', () => {
                    if (window.PortalTicketController) {
                        window.PortalTicketController.responder();
                    }
                });
            }

            content.querySelectorAll('[data-action="close-ticket-detail"]').forEach(b => {
                b.addEventListener('click', () => this.closeDetailModal());
            });

            m.style.display = 'flex';
        },

        closeDetailModal: function () {
            const m = document.getElementById('portal-ticket-detail-modal');
            if (m) m.style.display = 'none';
        },

        /**
         * Renderiza el modal de "Nuevo ticket".
         *
         * @param {Array} categorias - catálogo de categorías activas
         * @param {Array} servicios - servicios del cliente (de PortalServicioModel.cache)
         */
        renderCreateModal: function (categorias, servicios) {
            const m = document.getElementById('portal-ticket-form-modal');
            if (!m) return;

            const selCat = document.getElementById('portal-ticket-categoria');
            if (selCat) {
                selCat.innerHTML = '<option value="">Sin categoría</option>' +
                    (categorias || []).map(c =>
                        `<option value="${esc(c.id)}">${esc(c.nombre)}</option>`
                    ).join('');
            }

            const selSrv = document.getElementById('portal-ticket-servicio');
            if (selSrv) {
                selSrv.innerHTML = '<option value="">Sin servicio asociado</option>' +
                    (servicios || []).map(s => {
                        const alias = s.alias || ('Servicio #' + s.id);
                        return `<option value="${esc(s.id)}">${esc(alias)}</option>`;
                    }).join('');
            }

            // Reset del formulario
            const asunto = document.getElementById('portal-ticket-asunto');
            const desc = document.getElementById('portal-ticket-descripcion');
            const prio = document.getElementById('portal-ticket-prioridad');
            if (asunto) asunto.value = '';
            if (desc) desc.value = '';
            if (prio) prio.value = 'Media';

            const fb = document.getElementById('portal-ticket-form-feedback');
            if (fb) fb.textContent = '';

            m.style.display = 'flex';
            if (asunto) setTimeout(() => asunto.focus(), 60);
        },

        closeCreateModal: function () {
            const m = document.getElementById('portal-ticket-form-modal');
            if (m) m.style.display = 'none';
        },

        _attachListeners: function () {
            // Cierre del modal de detalle
            document.querySelectorAll('[data-action="close-ticket-detail"]').forEach(b => {
                b.addEventListener('click', () => this.closeDetailModal());
            });
            const detailModal = document.getElementById('portal-ticket-detail-modal');
            if (detailModal) {
                detailModal.addEventListener('mousedown', (e) => {
                    if (e.target === detailModal) this.closeDetailModal();
                });
                detailModal.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.closeDetailModal();
                    }
                });
            }

            // Cierre del modal de creación
            document.querySelectorAll('[data-action="close-ticket-form"]').forEach(b => {
                b.addEventListener('click', () => this.closeCreateModal());
            });
            const formModal = document.getElementById('portal-ticket-form-modal');
            if (formModal) {
                formModal.addEventListener('mousedown', (e) => {
                    if (e.target === formModal) this.closeCreateModal();
                });
                formModal.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.closeCreateModal();
                    }
                });
            }

            // Botón "Nuevo ticket"
            const btnNew = document.querySelector('[data-toolbar="portal-tickets"] [data-action="new-ticket"]');
            if (btnNew) {
                btnNew.addEventListener('click', async () => {
                    if (!window.PortalTicketModel.categoriasCache.length) {
                        try { await window.PortalTicketModel.loadCategorias(); } catch (e) {}
                    }
                    if (!window.PortalServicioModel || window.PortalServicioModel.cache.length === 0) {
                        try { await window.PortalServicioModel.load(100); } catch (e) {}
                    }
                    this.renderCreateModal(
                        window.PortalTicketModel.categoriasCache,
                        window.PortalServicioModel ? window.PortalServicioModel.cache : []
                    );
                });
            }

            // Submit del formulario de creación
            const form = document.getElementById('portal-ticket-form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    if (window.PortalTicketController) {
                        window.PortalTicketController.save();
                    }
                });
            }

            // Filtro de estado
            const estadoSel = document.getElementById('portal-tickets-estado');
            if (estadoSel) {
                estadoSel.addEventListener('change', () => {
                    if (window.PortalTicketController) window.PortalTicketController.load(1);
                });
            }

            // Filtro de prioridad
            const prioSel = document.getElementById('portal-tickets-prioridad');
            if (prioSel) {
                prioSel.addEventListener('change', () => {
                    if (window.PortalTicketController) window.PortalTicketController.load(1);
                });
            }
        }
    };
})();