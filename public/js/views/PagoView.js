// js/views/PagoView.js
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
        if (s === 'validado') return 'status-ok';
        if (s === 'pendiente') return 'status-warning';
        return 'status-alert';
    }

    function formatDual(usd, pago) {
        const n = Number(usd || 0);
        if (pago && pago.moneda_pago === 'VES' && pago.tasa_cambio && Number(pago.tasa_cambio) > 0) {
            const bs = (n * Number(pago.tasa_cambio)).toFixed(2);
            return `$${n.toFixed(2)}<br><small style="color:#64748b;">Bs. ${bs}</small>`;
        }
        return `$${n.toFixed(2)}`;
    }

    function mark(txt, search) {
        const s = window.normalizeForSearch(search || '');
        if (!s) return esc(txt);
        const raw = String(txt ?? '');
        const idx = window.normalizeForSearch(raw).indexOf(s);
        if (idx < 0) return esc(raw);
        return esc(raw.slice(0, idx)) + '<mark>' + esc(raw.slice(idx, idx + s.length)) + '</mark>' + esc(raw.slice(idx + s.length));
    }

    function labelServicio(s) {
        const nombre = `${s.nombres || ''} ${s.apellidos || ''}`.trim() || 'Cliente';
        const cedula = s.cedula ? ` (C.I. ${s.cedula})` : '';
        const alias = s.alias ? ` — ${s.alias}` : '';
        const plan = s.plan_nombre || 'Sin plan';
        const precio = s.plan_precio != null ? ` $${Number(s.plan_precio).toFixed(2)}` : '';
        return `${nombre}${cedula}${alias} (${plan}${precio})`;
    }

    window.PagoView = {
        renderTable: function (pagos, search) {
            const tbody = document.getElementById('pagos-list');
            if (!tbody) return;

            if (pagos.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay pagos todavía. <a href="#" data-action="new-pago-empty">Registrar el primero</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('pagos-search');
                    if (i) i.value = '';
                    window.PagoController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-pago-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm(false, true);
                });
                return;
            }

            const isAdmin = window.Session ? window.Session.isAdmin() : false;

            tbody.innerHTML = pagos.map(p => {
                const nombreCliente = `${p.nombres || ''} ${p.apellidos || ''}`.trim() || 'Sin cliente';
                const id = p.id;
                const estado = String(p.estado || '').toLowerCase();
                return `
                <tr data-module-row="pagos" data-id="${esc(id)}" data-estado="${esc(estado)}">
                    <td>${mark(nombreCliente, search)}</td>
                    <td>${mark(p.cedula || '—', search)}</td>
                    <td>${mark(p.servicio_alias || '—', search)}</td>
                    <td>${esc(p.factura_periodo || 'A cuenta')}</td>
                    <td>${formatDual(p.monto, p)}</td>
                    <td>${esc(p.fecha_pago)}</td>
                    <td>${esc(p.metodo_nombre || '—')}</td>
                    <td><span class="status ${getStatusClass(p.estado)}">${esc(p.estado)}</span></td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="pagos"]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    const estado = tr.getAttribute('data-estado');
                    const prevId = window.selectedIds ? window.selectedIds['pagos'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="pagos"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['pagos'] = id;
                    tr.classList.add('selected-row');

                    const canValidate = estado === 'pendiente' && isAdmin;

                    document.querySelectorAll('.toolbar-btn[data-module="pagos"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                        else if (action === 'validar' || action === 'rechazar') btn.disabled = !canValidate;
                    });
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('pagos-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-pagos-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-pagos-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-pagos-prev')?.addEventListener('click', () => {
                if (window.PagoController) window.PagoController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-pagos-next')?.addEventListener('click', () => {
                if (window.PagoController) window.PagoController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('pagos-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        populateSelects: function () {
            const srv = document.getElementById('pago-servicio');
            if (srv) {
                const cache = window.ServicioModel ? window.ServicioModel.cache : [];
                srv.innerHTML = '<option value="">Selecciona un servicio</option>' +
                    cache.filter(s => s.estado_comercial !== 'retirado').map(s =>
                        `<option value="${esc(s.id)}" data-cliente="${esc(s.cliente_id)}">${esc(labelServicio(s))}</option>`
                    ).join('');
            }

            const met = document.getElementById('pago-metodo');
            if (met) {
                const cache = window.MetodoPagoModel ? window.MetodoPagoModel.cache : [];
                met.innerHTML = '<option value="">Método de pago</option>' +
                    cache.map(m => `<option value="${esc(m.id)}">${esc(m.nombre)}</option>`).join('');
            }

            const fac = document.getElementById('pago-factura');
            if (fac) {
                fac.innerHTML = '<option value="">Sin factura asociada (a cuenta)</option>';
            }
        },

        populateFacturas: function (idServicio, facturaActualId) {
            const fac = document.getElementById('pago-factura');
            if (!fac) return;
            const cache = window.FacturaModel ? window.FacturaModel.cache : [];
            const filtered = cache.filter(f =>
                String(f.servicio_id) === String(idServicio) &&
                (String(f.id) === String(facturaActualId || '') || (f.estado !== 'pagada' && f.estado !== 'anulada'))
            );
            fac.innerHTML = '<option value="">Sin factura asociada (a cuenta)</option>' +
                filtered.map(f => `<option value="${esc(f.id)}">${esc(f.periodo)} - $${Number(f.monto).toFixed(2)} (${esc(f.estado)})</option>`).join('');
            if (facturaActualId) fac.value = facturaActualId;
            this._updateTasaRef();
        },

        _updateTasaRef: function () {
            const ref = document.getElementById('pago-tasa-factura-ref');
            if (!ref) return;
            const facId = document.getElementById('pago-factura')?.value;
            if (!facId) { ref.textContent = ''; return; }
            const fac = window.FacturaModel ? window.FacturaModel.cache.find(f => String(f.id) === String(facId)) : null;
            if (!fac) { ref.textContent = ''; return; }
            if (fac.moneda === 'VES' && fac.tasa_cambio) {
                ref.textContent = `Tasa de la factura (informativa): ${Number(fac.tasa_cambio).toFixed(4)} Bs/USD. La tasa del pago es la BCV del día del pago. La diferencia es una variación cambiaria normal.`;
                ref.style.color = '#64748b';
            } else {
                ref.textContent = `Factura en USD. La tasa del pago es la BCV del día.`;
                ref.style.color = '#64748b';
            }
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('pago-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['pago-id', 'pago-monto', 'pago-tasa-cambio', 'pago-fecha', 'pago-referencia'].forEach(id => {
                    const el = document.getElementById(id); if (el) el.value = '';
                });
                const mon = document.getElementById('pago-moneda'); if (mon) mon.value = 'USD';
                const fac = document.getElementById('pago-factura');
                if (fac) fac.innerHTML = '<option value="">Sin factura asociada (a cuenta)</option>';
                const ref = document.getElementById('pago-tasa-factura-ref'); if (ref) ref.textContent = '';
                this.populateSelects();
                if (window.PagoController) window.PagoController._dirty = false;
            }
        },

        fillForm: function (p) {
            document.getElementById('pago-id').value = p.id || '';
            this.populateSelects();
            document.getElementById('pago-servicio').value = p.servicio_id || '';
            this.populateFacturas(p.servicio_id, p.factura_id);
            document.getElementById('pago-monto').value = p.monto != null ? Number(p.monto).toFixed(2) : '';
            document.getElementById('pago-moneda').value = p.moneda_pago || 'USD';
            document.getElementById('pago-tasa-cambio').value = p.tasa_cambio != null ? Number(p.tasa_cambio).toFixed(4) : '';
            document.getElementById('pago-fecha').value = this._toDatetimeLocalValue(p.fecha_pago);
            document.getElementById('pago-metodo').value = p.metodo_id || '';
            document.getElementById('pago-referencia').value = p.referencia_bancaria || '';
            if (window.PagoController) window.PagoController._dirty = false;
        },

        _toDatetimeLocalValue: function (val) {
            if (!val) return '';
            return String(val).replace(' ', 'T').slice(0, 16);
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['pagos'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="pagos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'validar' || action === 'rechazar') {
                    btn.disabled = true;
                }
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="pagos"] [data-action="new"]');
            if (btnNew) { btnNew.removeAttribute('onclick'); btnNew.addEventListener('click', () => this.toggleForm()); }

            const btnEdit = document.querySelector('.toolbar[data-toolbar="pagos"] [data-action="edit"]');
            if (btnEdit) { btnEdit.removeAttribute('onclick'); btnEdit.addEventListener('click', () => { const id = window.selectedIds?.pagos; if (id) window.PagoController.edit(id); }); }

            const btnDel = document.querySelector('.toolbar[data-toolbar="pagos"] [data-action="delete"]');
            if (btnDel) { btnDel.removeAttribute('onclick'); btnDel.addEventListener('click', () => { const id = window.selectedIds?.pagos; if (id) window.PagoController.confirmDelete(id); }); }

            const btnValidar = document.querySelector('.toolbar[data-toolbar="pagos"] [data-action="validar"]');
            if (btnValidar) { btnValidar.removeAttribute('onclick'); btnValidar.addEventListener('click', () => { const id = window.selectedIds?.pagos; if (id) window.PagoController.updateEstado(id, 'validado'); }); }

            const btnRechazar = document.querySelector('.toolbar[data-toolbar="pagos"] [data-action="rechazar"]');
            if (btnRechazar) { btnRechazar.removeAttribute('onclick'); btnRechazar.addEventListener('click', () => { const id = window.selectedIds?.pagos; if (id) window.PagoController.updateEstado(id, 'rechazado'); }); }

            const search = document.getElementById('pagos-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.PagoController._searchTimer);
                    window.PagoController._searchTimer = setTimeout(() => window.PagoController.renderPage(1), 250);
                });
            }

            const btnSave = document.querySelector('#pago-form [data-action="save-pago"]');
            if (btnSave) { btnSave.removeAttribute('onclick'); btnSave.addEventListener('click', () => window.PagoController.save()); }

            const btnCancel = document.querySelector('#pago-form [data-action="cancel-pago"]');
            if (btnCancel) { btnCancel.removeAttribute('onclick'); btnCancel.addEventListener('click', () => this.toggleForm(true)); }

            const btnClose = document.querySelector('#pago-modal [data-action="close-pago-modal"]');
            if (btnClose) { btnClose.removeAttribute('onclick'); btnClose.addEventListener('click', () => this.toggleForm(true)); }

            const srv = document.getElementById('pago-servicio');
            if (srv) {
                srv.onchange = null;
                srv.addEventListener('change', () => window.PagoController._onServicioChange());
            }

            const facSel = document.getElementById('pago-factura');
            if (facSel) {
                facSel.addEventListener('change', () => this._updateTasaRef());
            }

            ['pago-monto', 'pago-tasa-cambio', 'pago-fecha', 'pago-referencia'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', () => { window.PagoController._dirty = true; });
            });
            ['pago-moneda', 'pago-metodo'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', () => { window.PagoController._dirty = true; });
            });
        }
    };
})();