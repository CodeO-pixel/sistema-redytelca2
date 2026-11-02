// js/portal/views/ServicioView.js
// View del portal para "Mis servicios" (096e, read-only).
// Renderiza tabla, paginación, detalle (modal) y empty state.
// No hace fetch: solo renderiza y delega en PortalServicioController.
(function () {
    'use strict';

    const PAGE_SIZE = 10;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalize(s) {
        return String(s == null ? '' : s)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function statusClass(estado) {
        const s = String(estado || '').toLowerCase();
        if (s === 'activo') return 'portal-status-ok';
        if (s === 'retirado') return 'portal-status-alert';
        return 'portal-status-warning'; // pendiente, suspendido
    }

    function labelPlan(s) {
        const nombre = s.plan_nombre || 'Sin plan';
        if (s.plan_precio != null) {
            return nombre + ' ($' + Number(s.plan_precio).toFixed(2) + ')';
        }
        return nombre;
    }

    function labelRed(s) {
        const partes = [];
        if (s.nap_codigo) partes.push('NAP ' + s.nap_codigo);
        if (s.olt_codigo) partes.push('OLT ' + s.olt_codigo);
        if (s.nodo_nombre) partes.push(s.nodo_nombre);
        return partes.length ? partes.join(' · ') : '—';
    }

    function labelEquipo(s) {
        if (!s.equipo_tipo) return 'Sin equipo asignado';
        const marcaModelo = [s.equipo_marca, s.equipo_modelo].filter(Boolean).join(' ');
        const mac = s.equipo_mac ? ' (' + s.equipo_mac + ')' : '';
        const estado = s.equipo_estado ? ' — ' + s.equipo_estado : '';
        return (s.equipo_tipo + ' ' + marcaModelo + mac + estado).trim();
    }

    function formatDate(val) {
        if (!val) return '—';
        // El backend devuelve UTC. Convertir a local del navegador.
        const s = String(val).replace('T', ' ').trim();
        const looksNaive = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s);
        const iso = looksNaive ? s.replace(' ', 'T') + 'Z' : s;
        const d = new Date(iso);
        if (isNaN(d.getTime())) return s.slice(0, 16);
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} `
             + `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    window.PortalServicioView = {
        PAGE_SIZE: PAGE_SIZE,

        renderTable: function (items, search) {
            const tbody = document.getElementById('portal-servicios-list');
            if (!tbody) return;

            if (!items || items.length === 0) {
                const hasSearch = normalize(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:#64748b;">${
                    hasSearch
                        ? 'Sin resultados para "<strong>' + esc(search) + '</strong>".'
                        : 'Aún no tienes servicios activos. Contacta al administrador si crees que es un error.'
                }</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(s => {
                const id = s.id;
                const alias = s.alias || 'Servicio #' + id;
                return `
                <tr data-module-row="portal-servicios" data-id="${esc(id)}">
                    <td><strong>${esc(alias)}</strong></td>
                    <td>${esc(labelPlan(s))}</td>
                    <td>${s.plan_precio != null ? '$' + Number(s.plan_precio).toFixed(2) : '—'}</td>
                    <td><span class="portal-status ${statusClass(s.estado_comercial)}">${esc(s.estado_comercial || '—')}</span></td>
                    <td>${esc(labelRed(s))}</td>
                    <td>${esc(s.direccion_texto || '—')}</td>
                    <td class="portal-cell-actions">
                        <button type="button" class="table-action btn-portal-ver-detalle"
                                data-id="${esc(id)}"
                                style="width:auto;padding:5px 10px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--accent);font-size:0.82rem;cursor:pointer;">
                            Ver detalle
                        </button>
                    </td>
                </tr>`;
            }).join('');

            // Click en "Ver detalle" → controller.showDetail
            tbody.querySelectorAll('.btn-portal-ver-detalle').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PortalServicioController) {
                        window.PortalServicioController.showDetail(id);
                    }
                });
            });

            // Click en la fila (cualquier otra celda) → también abre detalle.
            tbody.querySelectorAll('tr[data-module-row="portal-servicios"]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-portal-ver-detalle')) return;
                    const id = tr.dataset.id;
                    if (id && window.PortalServicioController) {
                        window.PortalServicioController.showDetail(id);
                    }
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('portal-servicios-pagination');
            if (!pag) return;
            if (totalPages <= 1) {
                pag.innerHTML = '';
                return;
            }
            pag.innerHTML = `
                <div class="pagination-controls">
                    <button type="button" id="btn-portal-servicios-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span class="pagination-info">Página ${page} / ${totalPages}</span>
                    <button type="button" id="btn-portal-servicios-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>`;
            document.getElementById('btn-portal-servicios-prev')?.addEventListener('click', () => {
                if (window.PortalServicioController) {
                    window.PortalServicioController.renderPage(Math.max(1, page - 1));
                }
            });
            document.getElementById('btn-portal-servicios-next')?.addEventListener('click', () => {
                if (window.PortalServicioController) {
                    window.PortalServicioController.renderPage(Math.min(totalPages, page + 1));
                }
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('portal-servicios-counter');
            if (!span) return;
            if (total === 0) {
                span.textContent = '';
                return;
            }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        renderDetailModal: function (servicio) {
            const m = document.getElementById('portal-servicio-detail-modal');
            const content = document.getElementById('portal-servicio-detail-content');
            const title = document.getElementById('portal-servicio-detail-title');
            if (!m || !content) return;

            const alias = servicio.alias || 'Servicio #' + servicio.id;
            if (title) title.textContent = 'Detalle — ' + alias;

            const red = labelRed(servicio);
            const equipo = labelEquipo(servicio);
            const coords = (servicio.latitud != null && servicio.longitud != null)
                ? Number(servicio.latitud).toFixed(6) + ', ' + Number(servicio.longitud).toFixed(6)
                : '—';

            content.innerHTML = `
                <div class="portal-detail-grid">
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Alias</span>
                        <div class="portal-detail-value">${esc(alias)}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Estado</span>
                        <div class="portal-detail-value">
                            <span class="portal-status ${statusClass(servicio.estado_comercial)}">${esc(servicio.estado_comercial || '—')}</span>
                        </div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Plan</span>
                        <div class="portal-detail-value">${esc(servicio.plan_nombre || 'Sin plan')}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Precio mensual</span>
                        <div class="portal-detail-value">${servicio.plan_precio != null ? '$' + Number(servicio.plan_precio).toFixed(2) : '—'}</div>
                    </div>
                    <div class="portal-detail-card" style="grid-column: 1 / -1;">
                        <span class="portal-detail-label">Red</span>
                        <div class="portal-detail-value">${esc(red)}</div>
                    </div>
                    <div class="portal-detail-card" style="grid-column: 1 / -1;">
                        <span class="portal-detail-label">Dirección de instalación</span>
                        <div class="portal-detail-value">${esc(servicio.direccion_texto || '—')}</div>
                    </div>
                    <div class="portal-detail-card" style="grid-column: 1 / -1;">
                        <span class="portal-detail-label">Coordenadas</span>
                        <div class="portal-detail-value">${esc(coords)}</div>
                    </div>
                </div>

                <h4 class="portal-detail-section-title">Equipo</h4>
                <div class="portal-detail-card">
                    <div class="portal-detail-value">${esc(equipo)}</div>
                </div>

                <h4 class="portal-detail-section-title">Fechas</h4>
                <div class="portal-detail-grid">
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Alta del servicio</span>
                        <div class="portal-detail-value">${esc(formatDate(servicio.creado_en))}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Última actualización</span>
                        <div class="portal-detail-value">${esc(formatDate(servicio.actualizado_en))}</div>
                    </div>
                </div>
            `;

            m.style.display = 'flex';
        },

        closeDetailModal: function () {
            const m = document.getElementById('portal-servicio-detail-modal');
            if (m) m.style.display = 'none';
        },

        _attachListeners: function () {
            // Botón de cierre del modal de detalle
            document.querySelectorAll('[data-action="close-servicio-detail"]').forEach(b => {
                b.addEventListener('click', () => this.closeDetailModal());
            });

            // Click en el overlay (fuera del card) cierra el modal
            const m = document.getElementById('portal-servicio-detail-modal');
            if (m) {
                m.addEventListener('mousedown', (e) => {
                    if (e.target === m) this.closeDetailModal();
                });
                m.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.closeDetailModal();
                    }
                });
            }

            // Búsqueda con debounce
            const search = document.getElementById('portal-servicios-search');
            if (search) {
                search.addEventListener('input', () => {
                    clearTimeout(window.PortalServicioController._searchTimer);
                    window.PortalServicioController._searchTimer = setTimeout(() => {
                        window.PortalServicioController.renderPage(1);
                    }, 250);
                });
            }
        }
    };
})();