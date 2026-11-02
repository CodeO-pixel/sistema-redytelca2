// js/views/FacturaView.js
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

    function getStatusClass(status) {
        const s = String(status || '').toLowerCase();
        if (s === 'pagada') return 'status-ok';
        if (s === 'pendiente' || s === 'parcial') return 'status-warning';
        return 'status-alert';
    }

    function formatMonto(f) {
        const monto = Number(f.monto || 0);
        if (f.moneda === 'VES' && f.tasa_cambio && Number(f.tasa_cambio) > 0) {
            const bs = (monto * Number(f.tasa_cambio)).toFixed(2);
            return `Bs. ${bs}<br><small style="color:#64748b;">(USD ${monto.toFixed(2)})</small>`;
        }
        return `$${monto.toFixed(2)}`;
    }

    // Formatea un monto USD, añadiendo Bs. si la factura es VES.
    function formatDual(usd, factura) {
        const n = Number(usd || 0);
        if (factura && factura.moneda === 'VES' && factura.tasa_cambio && Number(factura.tasa_cambio) > 0) {
            const bs = (n * Number(factura.tasa_cambio)).toFixed(2);
            return `$${n.toFixed(2)}<br><small style="color:#64748b;">Bs. ${bs}</small>`;
        }
        return `$${n.toFixed(2)}`;
    }

    function labelServicio(s) {
        const nombre = `${s.nombres || ''} ${s.apellidos || ''}`.trim() || 'Cliente';
        const cedula = s.cedula ? ` (C.I. ${s.cedula})` : '';
        const alias = s.alias ? ` — ${s.alias}` : '';
        const plan = s.plan_nombre || 'Sin plan';
        const precio = s.plan_precio != null ? ` $${Number(s.plan_precio).toFixed(2)}` : '';
        return `${nombre}${cedula}${alias} (${plan}${precio})`;
    }

    function mark(txt, search) {
        const s = window.normalizeForSearch(search || '');
        if (!s) return esc(txt);
        const raw = String(txt ?? '');
        const idx = window.normalizeForSearch(raw).indexOf(s);
        if (idx < 0) return esc(raw);
        return esc(raw.slice(0, idx)) + '<mark>' + esc(raw.slice(idx, idx + s.length)) + '</mark>' + esc(raw.slice(idx + s.length));
    }

    window.FacturaView = {
        renderTable: function (facturas, search) {
            const tbody = document.getElementById('facturas-list');
            if (!tbody) return;

            if (facturas.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay facturas todavía. <a href="#" data-action="new-factura-empty">Crear la primera</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('facturas-search');
                    if (i) i.value = '';
                    window.FacturaController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-factura-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm(false, true);
                });
                return;
            }

            tbody.innerHTML = facturas.map(f => {
                const nombreCliente = `${f.nombres || ''} ${f.apellidos || ''}`.trim() || 'Sin cliente';
                const id = f.id;
                const tieneLineas = Number(f.total_lineas || 0) > 0;
                const btnLineas = tieneLineas
                    ? `<button type="button" class="table-action btn-lineas" data-lineas-id="${esc(id)}" style="width:auto; padding:4px 8px;">Ver líneas (${esc(f.total_lineas)})</button>`
                    : `<span class="text-muted" style="font-size:0.85rem;">Sin líneas</span>`;
                return `
                <tr data-module-row="facturas" data-id="${esc(id)}">
                    <td>${mark(nombreCliente, search)}${f.servicio_alias ? `<br><small style="color:#64748b;">${mark(f.servicio_alias, search)}</small>` : ''}</td>
                    <td>${mark(f.cedula || '—', search)}</td>
                    <td>${mark(f.periodo, search)}</td>
                    <td>${formatMonto(f)}</td>
                    <td>$${Number(f.total_pagado || 0).toFixed(2)}</td>
                    <td>${esc(f.fecha_vencimiento)}</td>
                    <td><span class="status ${getStatusClass(f.estado)}">${esc(f.estado)}</span></td>
                    <td>${btnLineas}</td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="facturas"]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-lineas')) return;
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['facturas'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="facturas"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['facturas'] = id;
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-module="facturas"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                    });
                });
            });

            tbody.querySelectorAll('.btn-lineas').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.lineasId;
                    if (id && window.FacturaController) window.FacturaController.showLineas(id);
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('facturas-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-facturas-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-facturas-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-facturas-prev')?.addEventListener('click', () => {
                if (window.FacturaController) window.FacturaController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-facturas-next')?.addEventListener('click', () => {
                if (window.FacturaController) window.FacturaController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('facturas-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        populateSelects: function () {
            const sel = document.getElementById('factura-servicio');
            if (!sel) return;
            const cache = window.ServicioModel ? window.ServicioModel.cache : [];
            sel.innerHTML = '<option value="">Selecciona un servicio</option>' +
                cache.filter(s => s.estado_comercial !== 'retirado').map(s =>
                    `<option value="${esc(s.id)}">${esc(labelServicio(s))}</option>`
                ).join('');
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('factura-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['factura-id', 'factura-periodo', 'factura-monto', 'factura-tasa-cambio', 'factura-fecha-emision', 'factura-fecha-vencimiento'].forEach(id => {
                    const el = document.getElementById(id); if (el) el.value = '';
                });
                const mon = document.getElementById('factura-moneda'); if (mon) mon.value = 'USD';
                const est = document.getElementById('factura-estado'); if (est) est.value = 'pendiente';
                const eq = document.getElementById('factura-equivalente-bs'); if (eq) eq.textContent = '';
                if (window.FacturaController) {
                    window.FacturaController._montoTouched = false;
                    window.FacturaController._dirty = false;
                }
                this.populateSelects();
            }
        },

        fillForm: function (f) {
            document.getElementById('factura-id').value = f.id || '';
            this.populateSelects();
            document.getElementById('factura-servicio').value = f.servicio_id || '';
            document.getElementById('factura-periodo').value = f.periodo || '';
            document.getElementById('factura-monto').value = f.monto != null ? Number(f.monto).toFixed(2) : '';
            document.getElementById('factura-moneda').value = f.moneda || 'USD';
            document.getElementById('factura-tasa-cambio').value = f.tasa_cambio != null ? Number(f.tasa_cambio).toFixed(4) : '';
            document.getElementById('factura-fecha-emision').value = f.fecha_emision || '';
            document.getElementById('factura-fecha-vencimiento').value = f.fecha_vencimiento || '';
            document.getElementById('factura-estado').value = f.estado || 'pendiente';
            this.updateEquivalenteBs();
            if (window.FacturaController) {
                window.FacturaController._montoTouched = true;
                window.FacturaController._dirty = false;
            }
        },

        updateEquivalenteBs: function () {
            const eq = document.getElementById('factura-equivalente-bs');
            if (!eq) return;
            const monto = Number(document.getElementById('factura-monto')?.value || 0);
            const moneda = document.getElementById('factura-moneda')?.value || 'USD';
            const tasa = Number(document.getElementById('factura-tasa-cambio')?.value || 0);
            if (moneda === 'VES' && monto > 0 && tasa > 0) {
                eq.textContent = `Bs. ${(monto * tasa).toFixed(2)}`;
            } else {
                eq.textContent = '';
            }
        },

        renderLineasModal: function (factura, lineas) {
            const m = document.getElementById('factura-lineas-modal');
            const title = document.getElementById('factura-lineas-title');
            const list = document.getElementById('factura-lineas-list');
            const total = document.getElementById('factura-lineas-total');
            if (!m || !list) return;

            const monedaLabel = factura.moneda === 'VES' && factura.tasa_cambio
                ? ` — VES (tasa ${Number(factura.tasa_cambio).toFixed(4)} Bs/USD)`
                : '';

            if (title) {
                title.textContent = `Líneas ${factura.periodo}${monedaLabel} — ${factura.nombres || ''} ${factura.apellidos || ''}`.trim();
            }

            list.innerHTML = lineas.length ? lineas.map(l => `
                <tr>
                    <td>${esc(l.concepto)}</td>
                    <td>${Number(l.cantidad || 0).toFixed(2)}</td>
                    <td>${formatDual(l.precio_unitario, factura)}</td>
                    <td>${formatDual(l.monto_linea, factura)}</td>
                    <td>${Number(l.impuesto_porcentaje || 0).toFixed(2)}% (${formatDual(l.impuesto_monto, factura)})</td>
                    <td><strong>${formatDual(l.total_linea, factura)}</strong></td>
                </tr>
            `).join('') : '<tr><td colspan="6">Sin líneas registradas.</td></tr>';

            if (total) {
                const montoF = Number(factura.monto || 0);
                const pagado = Number(factura.total_pagado || 0);
                const saldo = Math.max(0, montoF - pagado);
                total.innerHTML =
                    `Total factura: ${formatDual(montoF, factura)} &nbsp;|&nbsp; ` +
                    `Pagado: ${formatDual(pagado, factura)} &nbsp;|&nbsp; ` +
                    `Saldo: ${formatDual(saldo, factura)}`;
            }

            m.style.display = 'flex';
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['facturas'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="facturas"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="facturas"] [data-action="new"]');
            if (btnNew) { btnNew.removeAttribute('onclick'); btnNew.addEventListener('click', () => this.toggleForm()); }
            const btnEdit = document.querySelector('.toolbar[data-toolbar="facturas"] [data-action="edit"]');
            if (btnEdit) { btnEdit.removeAttribute('onclick'); btnEdit.addEventListener('click', () => { const id = window.selectedIds?.facturas; if (id) window.FacturaController.edit(id); }); }
            const btnDel = document.querySelector('.toolbar[data-toolbar="facturas"] [data-action="delete"]');
            if (btnDel) { btnDel.removeAttribute('onclick'); btnDel.addEventListener('click', () => { const id = window.selectedIds?.facturas; if (id) window.FacturaController.confirmDelete(id); }); }

            const search = document.getElementById('facturas-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.FacturaController._searchTimer);
                    window.FacturaController._searchTimer = setTimeout(() => window.FacturaController.renderPage(1), 250);
                });
            }

            const btnSave = document.querySelector('#factura-form [data-action="save-factura"]');
            if (btnSave) { btnSave.removeAttribute('onclick'); btnSave.addEventListener('click', () => window.FacturaController.save()); }
            const btnCancel = document.querySelector('#factura-form [data-action="cancel-factura"]');
            if (btnCancel) { btnCancel.removeAttribute('onclick'); btnCancel.addEventListener('click', () => this.toggleForm(true)); }
            const btnClose = document.querySelector('#factura-modal .modal-close');
            if (btnClose) { btnClose.removeAttribute('onclick'); btnClose.addEventListener('click', () => this.toggleForm(true)); }

            // Autocompletar monto desde plan_precio al cambiar servicio (Bug 4a)
            const srv = document.getElementById('factura-servicio');
            if (srv) srv.addEventListener('change', () => window.FacturaController._onServicioChange());

            // Marcar dirty al editar campos + recalcular Bs.
            ['factura-periodo', 'factura-fecha-emision', 'factura-fecha-vencimiento'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', () => { window.FacturaController._dirty = true; });
            });
            const monto = document.getElementById('factura-monto');
            if (monto) {
                monto.removeAttribute('oninput');
                monto.addEventListener('input', () => {
                    window.FacturaController._montoTouched = true;
                    window.FacturaController._dirty = true;
                    this.updateEquivalenteBs();
                });
            }
            ['factura-moneda', 'factura-tasa-cambio', 'factura-estado'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.removeAttribute('onchange');
                    el.addEventListener('change', () => {
                        window.FacturaController._dirty = true;
                        this.updateEquivalenteBs();
                    });
                    el.addEventListener('input', () => {
                        window.FacturaController._dirty = true;
                        this.updateEquivalenteBs();
                    });
                }
            });

            const btnLineasClose = document.querySelector('#factura-lineas-modal [data-action="close-lineas-modal"]');
            if (btnLineasClose) {
                btnLineasClose.addEventListener('click', () => {
                    document.getElementById('factura-lineas-modal').style.display = 'none';
                });
            }
        }
    };
})();