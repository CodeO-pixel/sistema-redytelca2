// js/portal/views/PagoView.js
// View del portal para "Mis pagos" (096c).
// Renderiza tabla, paginación, detalle (modal), formulario de creación
// con upload de comprobante.
//
// formatDual: si moneda_pago === 'VES', muestra $X (Bs. Y).
// formatDateTime: convierte TIMESTAMP UTC → hora local (VET, UTC-4).
//
// Nombre y cédula del cliente en el detalle:
//   A2-B.4 decidió NO devolver nombres/apellidos/cedula en los endpoints
//   del portal (el cliente ya se conoce a sí mismo). El detalle los lee
//   de PortalSession.get().cliente con fallback a pago.nombres/cedula.
//
// FIXES 2026-11-02 (auditoría del portal):
//   - B1: el listener de "Nuevo pago" ahora envuelve las 3 cargas de
//     catálogos (Servicio, MetodoPago, Factura) en un único try/catch.
//     Si algo falla, muestra Toast.error y NO abre el modal. Antes
//     abría el modal con selects vacíos y sin feedback al usuario.
//   - B2: si no hay servicios activos, el listener muestra Toast.info
//     "No tienes servicios activos. Contacta al administrador." y NO
//     abre el modal (crear un pago sin servicio es imposible). En
//     populateSelects, si una lista está vacía se pinta un <option>
//     informativo ("No tienes servicios activos" / "No hay métodos de
//     pago disponibles") en vez de un único <option value="">.
(function () {
    'use strict';

    const PAGE_SIZE = 20;
    const COMPROBANTE_MAX_MB = 2;
    const COMPROBANTE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

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
        if (s === 'validado') return 'portal-status-ok';
        if (s === 'pendiente') return 'portal-status-warning';
        return 'portal-status-alert';
    }

    function formatDual(usd, pago) {
        const n = Number(usd || 0);
        if (pago && pago.moneda_pago === 'VES' && pago.tasa_cambio && Number(pago.tasa_cambio) > 0) {
            const bs = (n * Number(pago.tasa_cambio)).toFixed(2);
            return `$${n.toFixed(2)}<small style="display:block;font-weight:400;color:var(--muted);font-size:0.75rem;">Bs. ${bs}</small>`;
        }
        return `$${n.toFixed(2)}`;
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

    function getDatosCliente(pago) {
        const session = window.PortalSession ? window.PortalSession.get() : null;
        const clienteSesion = session && session.cliente ? session.cliente : null;
        const nombrePago = `${pago.nombres || ''} ${pago.apellidos || ''}`.trim();
        const nombreSesion = clienteSesion
            ? `${clienteSesion.nombre || ''} ${clienteSesion.apellido || ''}`.trim()
            : '';
        return {
            nombre: nombrePago || nombreSesion || 'Sin cliente',
            cedula: (pago.cedula || (clienteSesion ? clienteSesion.cedula : '') || '—')
        };
    }

    window.PortalPagoView = {
        PAGE_SIZE: PAGE_SIZE,

        renderTable: function (items) {
            const tbody = document.getElementById('portal-pagos-list');
            if (!tbody) return;

            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:#64748b;">
                    No hay pagos que coincidan con los filtros.
                </td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(p => {
                const id = p.id;
                const facturaLabel = p.factura_periodo || 'A cuenta';
                const facturaCell = p.factura_id
                    ? esc(facturaLabel)
                    : `<span style="color:#92400e;">${esc(facturaLabel)}</span>`;
                const comprobanteBtn = p.comprobante_path
                    ? `<button type="button" class="portal-btn-secondary btn-portal-comprobante" data-id="${esc(id)}" style="padding:5px 10px;font-size:0.8rem;">Ver</button>`
                    : `<span class="text-muted">—</span>`;
                return `
                <tr data-module-row="portal-pagos" data-id="${esc(id)}">
                    <td>${facturaCell}</td>
                    <td>${esc(p.servicio_alias || '—')}</td>
                    <td class="portal-pago-monto">${formatDual(p.monto, p)}</td>
                    <td>${esc(formatDateTime(p.fecha_pago))}</td>
                    <td>${esc(p.metodo_nombre || '—')}</td>
                    <td><span class="portal-status ${statusClass(p.estado)}">${esc(p.estado || '—')}</span></td>
                    <td class="portal-cell-actions" style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button type="button" class="portal-btn-secondary btn-portal-ver-pago" data-id="${esc(id)}" style="padding:5px 10px;font-size:0.8rem;">Detalle</button>
                        ${comprobanteBtn}
                    </td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('.btn-portal-ver-pago').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (window.PortalPagoController) window.PortalPagoController.showDetail(btn.dataset.id);
                });
            });
            tbody.querySelectorAll('.btn-portal-comprobante').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (window.PortalPagoController) window.PortalPagoController.downloadComprobante(btn.dataset.id);
                });
            });
            tbody.querySelectorAll('tr[data-module-row="portal-pagos"]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('button')) return;
                    if (window.PortalPagoController) window.PortalPagoController.showDetail(tr.dataset.id);
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('portal-pagos-pagination');
            if (!pag) return;
            if (totalPages <= 1) { pag.innerHTML = ''; return; }
            pag.innerHTML = `
                <div class="pagination-controls">
                    <button type="button" id="btn-portal-pagos-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span class="pagination-info">Página ${page} / ${totalPages}</span>
                    <button type="button" id="btn-portal-pagos-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>`;
            const prev = document.getElementById('btn-portal-pagos-prev');
            const next = document.getElementById('btn-portal-pagos-next');
            if (prev) prev.addEventListener('click', () => {
                if (window.PortalPagoController) window.PortalPagoController.load(Math.max(1, page - 1));
            });
            if (next) next.addEventListener('click', () => {
                if (window.PortalPagoController) window.PortalPagoController.load(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('portal-pagos-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        toggleForm: function (forceClose, reset) {
            const m = document.getElementById('portal-pago-form-modal');
            if (!m) return;
            const open = forceClose === false;
            m.style.display = open ? 'flex' : 'none';
            if (open && reset) this._resetForm();
        },

        _resetForm: function () {
            const idEl = document.getElementById('portal-pago-id'); if (idEl) idEl.value = '';
            const monEl = document.getElementById('portal-pago-moneda'); if (monEl) monEl.value = 'USD';
            const montoEl = document.getElementById('portal-pago-monto'); if (montoEl) montoEl.value = '';
            const tasaEl = document.getElementById('portal-pago-tasa'); if (tasaEl) tasaEl.value = '';
            const fechaEl = document.getElementById('portal-pago-fecha');
            if (fechaEl) {
                const now = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                fechaEl.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
            }
            const refEl = document.getElementById('portal-pago-referencia'); if (refEl) refEl.value = '';
            const fileEl = document.getElementById('portal-pago-comprobante'); if (fileEl) fileEl.value = '';
            const preview = document.getElementById('portal-pago-comprobante-preview');
            if (preview) { preview.style.display = 'none'; preview.innerHTML = ''; }
            const fb = document.getElementById('portal-pago-feedback'); if (fb) fb.textContent = '';
            this._toggleTasaWrap();
            this._toggleReferenciaWrap();
        },

        /**
         * Puebla los selects del form. B2: si una lista está vacía,
         * pinta un <option> informativo en lugar de dejar el select
         * con solo "Selecciona…" (que confundiría al usuario: parece
         * que puede elegir, pero no hay opciones).
         */
        populateSelects: async function () {
            const selSrv = document.getElementById('portal-pago-servicio');
            if (selSrv) {
                const servicios = window.PortalServicioModel ? window.PortalServicioModel.cache : [];
                if (servicios.length === 0) {
                    selSrv.innerHTML = '<option value="">No tienes servicios activos</option>';
                } else {
                    selSrv.innerHTML = '<option value="">Selecciona un servicio</option>' + servicios.map(s => {
                        const alias = s.alias || ('Servicio #' + s.id);
                        const plan = s.plan_nombre ? ' — ' + s.plan_nombre : '';
                        return `<option value="${esc(s.id)}">${esc(alias + plan)}</option>`;
                    }).join('');
                }
            }

            const selMet = document.getElementById('portal-pago-metodo');
            if (selMet) {
                const metodos = window.PortalMetodoPagoModel ? window.PortalMetodoPagoModel.cache : [];
                if (metodos.length === 0) {
                    selMet.innerHTML = '<option value="">No hay métodos de pago disponibles</option>';
                } else {
                    selMet.innerHTML = '<option value="">Selecciona un método</option>' + metodos.map(m => {
                        return `<option value="${esc(m.id)}" data-requiere-ref="${m.requiere_referencia ? '1' : '0'}">${esc(m.nombre)}</option>`;
                    }).join('');
                }
            }
        },

        populateFacturas: function (servicioId, facturaIdActual) {
            const sel = document.getElementById('portal-pago-factura');
            if (!sel) return;

            if (!servicioId) {
                sel.innerHTML = '<option value="">Sin factura asociada (pago a cuenta)</option>';
                return;
            }

            const facturas = window.PortalFacturaModel ? window.PortalFacturaModel.cache : [];
            const filtered = facturas.filter(f =>
                String(f.servicio_id) === String(servicioId) &&
                (String(f.id) === String(facturaIdActual || '') ||
                 (f.estado !== 'pagada' && f.estado !== 'anulada'))
            );

            sel.innerHTML = '<option value="">Sin factura asociada (pago a cuenta)</option>' + filtered.map(f => {
                const periodo = f.periodo || '—';
                const monto = Number(f.monto || 0).toFixed(2);
                const saldo = Number(f.saldo || 0).toFixed(2);
                return `<option value="${esc(f.id)}">${esc(periodo)} — $${monto} (saldo $${saldo})</option>`;
            }).join('');

            if (facturaIdActual) sel.value = String(facturaIdActual);
        },

        _toggleTasaWrap: function () {
            const mon = document.getElementById('portal-pago-moneda');
            const wrap = document.getElementById('portal-pago-tasa-wrap');
            const tasa = document.getElementById('portal-pago-tasa');
            if (!mon || !wrap) return;
            const isVes = mon.value === 'VES';
            wrap.style.display = isVes ? '' : 'none';
            if (tasa) tasa.required = isVes;
        },

        _toggleReferenciaWrap: function () {
            const sel = document.getElementById('portal-pago-metodo');
            const wrap = document.getElementById('portal-pago-referencia-wrap');
            const ref = document.getElementById('portal-pago-referencia');
            if (!sel || !wrap) return;
            const opt = sel.options[sel.selectedIndex];
            const requiere = opt && opt.dataset.requiereRef === '1';
            wrap.style.display = requiere ? '' : 'none';
            if (ref) ref.required = requiere;
        },

        _onComprobanteChange: function () {
            const input = document.getElementById('portal-pago-comprobante');
            const preview = document.getElementById('portal-pago-comprobante-preview');
            if (!input || !preview) return;
            const file = input.files && input.files[0];
            if (!file) { preview.style.display = 'none'; preview.innerHTML = ''; return; }

            if (COMPROBANTE_TYPES.indexOf(file.type) === -1) {
                if (window.Toast) window.Toast.warning('Formato no soportado. Usa JPG, PNG, WEBP o PDF.');
                input.value = '';
                preview.style.display = 'none';
                return;
            }
            if (file.size > COMPROBANTE_MAX_MB * 1024 * 1024) {
                if (window.Toast) window.Toast.warning('El archivo excede el límite de 2 MB.');
                input.value = '';
                preview.style.display = 'none';
                return;
            }

            preview.style.display = 'block';
            if (file.type === 'application/pdf') {
                preview.innerHTML = `<div class="portal-comprobante-file">📄 ${esc(file.name)} (${(file.size / 1024).toFixed(1)} KB)</div>`;
            } else {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Comprobante">`;
                };
                reader.readAsDataURL(file);
            }
        },

        renderDetailModal: function (pago) {
            const m = document.getElementById('portal-pago-detail-modal');
            const title = document.getElementById('portal-pago-detail-title');
            const content = document.getElementById('portal-pago-detail-content');
            if (!m || !content) return;

            const datosCliente = getDatosCliente(pago);
            if (title) title.textContent = `Pago #${pago.id} — ${datosCliente.nombre}`;

            const facturaLabel = pago.factura_periodo
                ? `Factura ${pago.factura_periodo}`
                : 'Pago a cuenta (sin factura)';
            const comprobanteHtml = pago.comprobante_path
                ? `<button type="button" class="portal-comprobante-link" data-action="download-comprobante" data-id="${esc(pago.id)}">Descargar comprobante</button>`
                : '<span class="text-muted">Sin comprobante</span>';

            content.innerHTML = `
                <div class="portal-detail-grid">
                    <div class="portal-detail-card"><span class="portal-detail-label">Cliente</span><div class="portal-detail-value">${esc(datosCliente.nombre)}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Cédula</span><div class="portal-detail-value">${esc(datosCliente.cedula)}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Factura</span><div class="portal-detail-value">${esc(facturaLabel)}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Servicio</span><div class="portal-detail-value">${esc(pago.servicio_alias || '—')}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Monto</span><div class="portal-detail-value">${formatDual(pago.monto, pago)}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Moneda</span><div class="portal-detail-value">${esc(pago.moneda_pago || 'USD')}${pago.tasa_cambio ? ' (tasa ' + Number(pago.tasa_cambio).toFixed(4) + ')' : ''}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Fecha</span><div class="portal-detail-value">${esc(formatDateTime(pago.fecha_pago))}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Método</span><div class="portal-detail-value">${esc(pago.metodo_nombre || '—')}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Referencia</span><div class="portal-detail-value">${esc(pago.referencia_bancaria || '—')}</div></div>
                    <div class="portal-detail-card"><span class="portal-detail-label">Estado</span><div class="portal-detail-value"><span class="portal-status ${statusClass(pago.estado)}">${esc(pago.estado || '—')}</span></div></div>
                    ${pago.motivo_rechazo ? `<div class="portal-detail-card" style="grid-column:1/-1;"><span class="portal-detail-label">Motivo de rechazo</span><div class="portal-detail-value">${esc(pago.motivo_rechazo)}</div></div>` : ''}
                    <div class="portal-detail-card" style="grid-column:1/-1;"><span class="portal-detail-label">Comprobante</span><div class="portal-detail-value">${comprobanteHtml}</div></div>
                </div>`;

            const dl = content.querySelector('[data-action="download-comprobante"]');
            if (dl) dl.addEventListener('click', () => {
                if (window.PortalPagoController) window.PortalPagoController.downloadComprobante(dl.dataset.id);
            });

            m.style.display = 'flex';
        },

        closeDetailModal: function () {
            const m = document.getElementById('portal-pago-detail-modal');
            if (m) m.style.display = 'none';
        },

        _attachListeners: function () {
            document.querySelectorAll('[data-action="close-pago-detail"]').forEach(b => {
                b.addEventListener('click', () => this.closeDetailModal());
            });
            document.querySelectorAll('[data-action="cancel-pago"]').forEach(b => {
                b.addEventListener('click', () => this.toggleForm(true));
            });
            const detailModal = document.getElementById('portal-pago-detail-modal');
            if (detailModal) {
                detailModal.addEventListener('mousedown', (e) => {
                    if (e.target === detailModal) this.closeDetailModal();
                });
                detailModal.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') { e.preventDefault(); this.closeDetailModal(); }
                });
            }

            const btnClear = document.querySelector('[data-toolbar="portal-pagos"] [data-action="clear-filters"]');
            if (btnClear) btnClear.addEventListener('click', () => {
                if (window.PortalPagoController) window.PortalPagoController.clearFilters();
            });
            const estSel = document.getElementById('portal-pagos-estado');
            if (estSel) estSel.addEventListener('change', () => {
                if (window.PortalPagoController) window.PortalPagoController.load(1);
            });

            // B1 + B2: cargar catálogos con try/catch único. Si algo falla
            // (red, 403, sesión expirada), Toast.error + return, sin abrir
            // el modal con selects vacíos. Si no hay servicios, Toast.info
            // "No tienes servicios activos" + return.
            const btnNew = document.querySelector('[data-toolbar="portal-pagos"] [data-action="new-pago"]');
            if (btnNew) btnNew.addEventListener('click', async () => {
                try {
                    if (!window.PortalServicioModel || window.PortalServicioModel.cache.length === 0) {
                        await window.PortalServicioModel.load(100);
                    }
                    if (!window.PortalMetodoPagoModel || window.PortalMetodoPagoModel.cache.length === 0) {
                        await window.PortalMetodoPagoModel.load();
                    }
                    if (!window.PortalFacturaModel || window.PortalFacturaModel.cache.length === 0) {
                        await window.PortalFacturaModel.load(1, 100);
                    }
                } catch (e) {
                    if (window.Toast) {
                        window.Toast.error(e.message || 'No se pudieron cargar los catálogos del pago.');
                    }
                    return;
                }

                if (!window.PortalServicioModel || window.PortalServicioModel.cache.length === 0) {
                    if (window.Toast) {
                        window.Toast.info('No tienes servicios activos. Contacta al administrador.');
                    }
                    return;
                }

                await this.populateSelects();
                this.populateFacturas(null, null);
                this.toggleForm(false, true);
            });

            const form = document.getElementById('portal-pago-form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    if (window.PortalPagoController) window.PortalPagoController.save();
                });
            }
            const monSel = document.getElementById('portal-pago-moneda');
            if (monSel) monSel.addEventListener('change', () => this._toggleTasaWrap());
            const metSel = document.getElementById('portal-pago-metodo');
            if (metSel) metSel.addEventListener('change', () => this._toggleReferenciaWrap());
            const srvSel = document.getElementById('portal-pago-servicio');
            if (srvSel) srvSel.addEventListener('change', () => {
                this.populateFacturas(srvSel.value, null);
            });
            const fileInput = document.getElementById('portal-pago-comprobante');
            if (fileInput) fileInput.addEventListener('change', () => this._onComprobanteChange());
        }
    };
})();