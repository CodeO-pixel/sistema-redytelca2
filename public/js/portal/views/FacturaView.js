// js/portal/views/FacturaView.js
// View del portal para "Mis facturas" (096b, read-only).
// 096f (2026-11-02): botón "Imprimir / PDF" en el modal de detalle.
//   Llama PortalFacturaPdfView.print(factura, lineas). El listener va
//   inline en renderDetailModal (mismo patrón que PagoView) porque el
//   modal se re-genera en cada apertura.
//
// Renderiza tabla, paginación, detalle (modal con líneas) y empty state.
// formatDual: si la factura es VES, muestra $X (Bs. Y).
// No hace fetch: solo renderiza y delega en PortalFacturaController.
//
// 096b-fix (2026-11-01): el backend del portal NO devuelve nombres,
// apellidos ni cedula del cliente (decisión A2-B.4: el cliente ya se
// conoce a sí mismo, no se repite PII). El modal de detalle los lee de
// PortalSession.get().cliente. Si en el futuro el backend los incluye,
// la vista los prefiere (factura.nombres || sesion.cliente.nombre).
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
        if (s === 'pagada') return 'portal-status-ok';
        if (s === 'pendiente' || s === 'parcial') return 'portal-status-warning';
        return 'portal-status-alert'; // vencida, anulada
    }

    /**
     * Formatea un monto dual.
     * - Si `factura.moneda === 'VES'` y `tasa_cambio > 0`:
     *   devuelve `$X (Bs. Y)` donde Y = X * tasa.
     * - Si `moneda === 'USD'` o falta tasa:
     *   devuelve solo `$X`.
     */
    function formatDual(usd, factura) {
        const n = Number(usd || 0);
        if (factura && factura.moneda === 'VES' && factura.tasa_cambio && Number(factura.tasa_cambio) > 0) {
            const bs = (n * Number(factura.tasa_cambio)).toFixed(2);
            return `$${n.toFixed(2)}<small style="display:block;font-weight:400;color:var(--muted);font-size:0.75rem;">Bs. ${bs}</small>`;
        }
        return `$${n.toFixed(2)}`;
    }

    function formatDate(val) {
        if (!val) return '—';
        const s = String(val).replace('T', ' ').trim();
        // Las fechas DATE (fecha_emision, fecha_vencimiento) NO se convierten
        // de zona horaria. Son DATE puros.
        return s.slice(0, 10);
    }

    /**
     * Devuelve {nombre, cedula} del cliente del portal.
     *
     * Prioridad:
     *   1. Si la factura trae los datos (algún día, si el backend los
     *      añade), usarlos.
     *   2. Si no, leer de PortalSession.get().cliente (el cliente
     *      autenticado).
     *   3. Fallback: 'Sin cliente' / '—'.
     */
    function getDatosCliente(factura) {
        const session = window.PortalSession ? window.PortalSession.get() : null;
        const clienteSesion = session && session.cliente ? session.cliente : null;

        const nombreFactura = `${factura.nombres || ''} ${factura.apellidos || ''}`.trim();
        const nombreSesion = clienteSesion
            ? `${clienteSesion.nombre || ''} ${clienteSesion.apellido || ''}`.trim()
            : '';

        return {
            nombre: nombreFactura || nombreSesion || 'Sin cliente',
            cedula: (factura.cedula || (clienteSesion ? clienteSesion.cedula : '') || '—')
        };
    }

    window.PortalFacturaView = {
        PAGE_SIZE: PAGE_SIZE,

        // Cache del modal abierto. Necesario para el listener del botón
        // "Imprimir / PDF", que se conecta en renderDetailModal (no en
        // _attachListeners, porque el modal se re-genera en cada apertura).
        _currentFactura: null,
        _currentLineas: null,

        renderTable: function (items, search) {
            const tbody = document.getElementById('portal-facturas-list');
            if (!tbody) return;

            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:24px;color:#64748b;">
                    No hay facturas que coincidan con los filtros.
                </td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(f => {
                const id = f.id;
                const saldo = Number(f.saldo || 0);
                const saldoClass = saldo > 0 ? 'portal-factura-saldo' : 'portal-factura-saldo cero';
                const pagado = Number(f.total_pagado || 0);
                return `
                <tr data-module-row="portal-facturas" data-id="${esc(id)}">
                    <td><strong>${esc(f.periodo || '—')}</strong></td>
                    <td>${esc(f.servicio_alias || '—')}</td>
                    <td class="portal-factura-monto">${formatDual(f.monto, f)}</td>
                    <td>${formatDual(pagado, f)}</td>
                    <td class="${saldoClass}">${formatDual(saldo, f)}</td>
                    <td>${esc(formatDate(f.fecha_vencimiento))}</td>
                    <td><span class="portal-status ${statusClass(f.estado)}">${esc(f.estado || '—')}</span></td>
                    <td class="portal-cell-actions">
                        <button type="button" class="table-action btn-portal-ver-factura"
                                data-id="${esc(id)}"
                                style="width:auto;padding:5px 10px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--accent);font-size:0.82rem;cursor:pointer;">
                            Ver detalle
                        </button>
                    </td>
                </tr>`;
            }).join('');

            // Click en "Ver detalle"
            tbody.querySelectorAll('.btn-portal-ver-factura').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PortalFacturaController) {
                        window.PortalFacturaController.showDetail(id);
                    }
                });
            });

            // Click en la fila (cualquier otra celda) → también abre detalle.
            tbody.querySelectorAll('tr[data-module-row="portal-facturas"]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-portal-ver-factura')) return;
                    const id = tr.dataset.id;
                    if (id && window.PortalFacturaController) {
                        window.PortalFacturaController.showDetail(id);
                    }
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('portal-facturas-pagination');
            if (!pag) return;
            if (totalPages <= 1) {
                pag.innerHTML = '';
                return;
            }
            pag.innerHTML = `
                <div class="pagination-controls">
                    <button type="button" id="btn-portal-facturas-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span class="pagination-info">Página ${page} / ${totalPages}</span>
                    <button type="button" id="btn-portal-facturas-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>`;
            document.getElementById('btn-portal-facturas-prev')?.addEventListener('click', () => {
                if (window.PortalFacturaController) {
                    window.PortalFacturaController.load(Math.max(1, page - 1));
                }
            });
            document.getElementById('btn-portal-facturas-next')?.addEventListener('click', () => {
                if (window.PortalFacturaController) {
                    window.PortalFacturaController.load(Math.min(totalPages, page + 1));
                }
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('portal-facturas-counter');
            if (!span) return;
            if (total === 0) {
                span.textContent = '';
                return;
            }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        renderDetailModal: function (factura, lineas) {
            const m = document.getElementById('portal-factura-detail-modal');
            const content = document.getElementById('portal-factura-detail-content');
            const title = document.getElementById('portal-factura-detail-title');
            if (!m || !content) return;

            // Guardar para el listener de "Imprimir / PDF".
            this._currentFactura = factura;
            this._currentLineas = Array.isArray(lineas) ? lineas : [];

            const periodo = factura.periodo || '—';
            const datosCliente = getDatosCliente(factura);
            const nombreCliente = datosCliente.nombre;
            const cedulaCliente = datosCliente.cedula;

            if (title) title.textContent = `Factura ${periodo} — ${nombreCliente}`;

            const monedaLabel = factura.moneda === 'VES' && factura.tasa_cambio
                ? `VES (tasa ${Number(factura.tasa_cambio).toFixed(4)} Bs/USD)`
                : 'USD';

            const lineasHtml = (lineas && lineas.length > 0)
                ? lineas.map(l => `
                    <tr>
                        <td>${esc(l.concepto)}</td>
                        <td class="num">${Number(l.cantidad || 0).toFixed(2)}</td>
                        <td class="num">${formatDual(l.precio_unitario, factura)}</td>
                        <td class="num">${formatDual(l.monto_linea, factura)}</td>
                        <td class="num">${Number(l.impuesto_porcentaje || 0).toFixed(2)}%</td>
                        <td class="num"><strong>${formatDual(l.total_linea, factura)}</strong></td>
                    </tr>
                `).join('')
                : `<tr><td colspan="6" style="text-align:center;padding:16px;color:var(--muted);">Sin líneas registradas.</td></tr>`;

            const monto = Number(factura.monto || 0);
            const pagado = Number(factura.total_pagado || 0);
            const saldo = Math.max(0, monto - pagado);

            content.innerHTML = `
                <div class="portal-detail-grid">
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Cliente</span>
                        <div class="portal-detail-value">${esc(nombreCliente)}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Cédula</span>
                        <div class="portal-detail-value">${esc(cedulaCliente)}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Periodo</span>
                        <div class="portal-detail-value">${esc(periodo)}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Moneda de presentación</span>
                        <div class="portal-detail-value">${esc(monedaLabel)}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Emisión</span>
                        <div class="portal-detail-value">${esc(formatDate(factura.fecha_emision))}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Vencimiento</span>
                        <div class="portal-detail-value">${esc(formatDate(factura.fecha_vencimiento))}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Estado</span>
                        <div class="portal-detail-value">
                            <span class="portal-status ${statusClass(factura.estado)}">${esc(factura.estado || '—')}</span>
                        </div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Servicio</span>
                        <div class="portal-detail-value">${esc(factura.servicio_alias || '—')}</div>
                    </div>
                </div>

                <h4 class="portal-detail-section-title">Líneas de la factura</h4>
                <div class="portal-table-wrap">
                    <table class="portal-lineas-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="num">Cantidad</th>
                                <th class="num">P. Unitario</th>
                                <th class="num">Monto</th>
                                <th class="num">Impuesto</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>${lineasHtml}</tbody>
                    </table>
                </div>

                <div class="portal-lineas-total">
                    <div class="total-row"><span>Total factura</span><span>${formatDual(monto, factura)}</span></div>
                    <div class="total-row"><span>Pagado</span><span>${formatDual(pagado, factura)}</span></div>
                    <div class="total-row"><span>Saldo pendiente</span><span class="${saldo > 0 ? 'portal-factura-saldo' : ''}">${formatDual(saldo, factura)}</span></div>
                </div>

                <div class="portal-modal-actions" style="margin-top:16px;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                    <button type="button" class="portal-btn-secondary" data-action="print-factura-pdf">
                        Imprimir / PDF
                    </button>
                    <button type="button" class="portal-btn-secondary" data-action="close-factura-detail">
                        Cerrar
                    </button>
                </div>
            `;

            // Listener del botón "Imprimir / PDF" (inline, no en
            // _attachListeners: el modal se re-genera en cada apertura).
            const btnPrint = content.querySelector('[data-action="print-factura-pdf"]');
            if (btnPrint) {
                btnPrint.addEventListener('click', () => {
                    if (window.PortalFacturaPdfView) {
                        window.PortalFacturaPdfView.print(this._currentFactura, this._currentLineas);
                    }
                });
            }

            // Listener del botón "Cerrar" (inline, mismo motivo).
            const btnClose = content.querySelector('[data-action="close-factura-detail"]');
            if (btnClose) {
                btnClose.addEventListener('click', () => this.closeDetailModal());
            }

            m.style.display = 'flex';
        },

        closeDetailModal: function () {
            const m = document.getElementById('portal-factura-detail-modal');
            if (m) m.style.display = 'none';
            this._currentFactura = null;
            this._currentLineas = null;
        },

        _attachListeners: function () {
            // Cierre del modal de detalle (botones que estén en el DOM
            // inicial; los del modal re-generado se conectan inline).
            document.querySelectorAll('[data-action="close-factura-detail"]').forEach(b => {
                b.addEventListener('click', () => this.closeDetailModal());
            });

            const m = document.getElementById('portal-factura-detail-modal');
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

            // Botones del toolbar de filtros
            document.querySelectorAll('[data-toolbar="portal-facturas"] [data-action="apply-filters"]').forEach(b => {
                b.addEventListener('click', () => {
                    if (window.PortalFacturaController) window.PortalFacturaController.applyFilters();
                });
            });
            document.querySelectorAll('[data-toolbar="portal-facturas"] [data-action="clear-filters"]').forEach(b => {
                b.addEventListener('click', () => {
                    if (window.PortalFacturaController) window.PortalFacturaController.clearFilters();
                });
            });

            // Enter en el input de periodo aplica filtros
            const periodoInput = document.getElementById('portal-facturas-periodo');
            if (periodoInput) {
                periodoInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (window.PortalFacturaController) window.PortalFacturaController.applyFilters();
                    }
                });
            }

            // Select de estado aplica al cambiar
            const estadoSelect = document.getElementById('portal-facturas-estado');
            if (estadoSelect) {
                estadoSelect.addEventListener('change', () => {
                    if (window.PortalFacturaController) window.PortalFacturaController.applyFilters();
                });
            }
        }
    };
})();