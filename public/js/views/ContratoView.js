// js/views/ContratoView.js
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
        if (s === 'vigente') return 'status-ok';
        if (s === 'vencido') return 'status-warning';
        return 'status-alert'; // rescindido
    }

    function formatTipo(tipo) {
        const map = {
            'indefinido': 'Indefinido',
            'plazo_fijo': 'Plazo fijo',
            'promocional': 'Promocional'
        };
        return map[String(tipo || '').toLowerCase()] || tipo || '—';
    }

    function mark(txt, search) {
        const s = window.normalizeForSearch(search || '');
        if (!s) return esc(txt);
        const raw = String(txt ?? '');
        const idx = window.normalizeForSearch(raw).indexOf(s);
        if (idx < 0) return esc(raw);
        return esc(raw.slice(0, idx)) + '<mark>' + esc(raw.slice(idx, idx + s.length)) + '</mark>' + esc(raw.slice(idx + s.length));
    }

    window.ContratoView = {
        renderTable: function (contratos, search) {
            const tbody = document.getElementById('contratos-list');
            if (!tbody) return;

            if (contratos.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay contratos todavía. <a href="#" data-action="new-contrato-empty">Crear el primero</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('contratos-search');
                    if (i) i.value = '';
                    window.ContratoController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-contrato-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm(false, true);
                });
                return;
            }

            tbody.innerHTML = contratos.map(c => {
                const nombreCliente = `${c.nombres || ''} ${c.apellidos || ''}`.trim() || 'Sin cliente';
                const id = c.id;
                const srvRetirado = String(c.servicio_estado || '').toLowerCase() === 'retirado';
                const srvCell = srvRetirado
                    ? `${mark(c.servicio_alias || c.direccion_texto || '—', search)}<br><small style="color:var(--danger);font-weight:600;">Servicio retirado</small>`
                    : mark(c.servicio_alias || c.direccion_texto || '—', search);
                const rowStyle = srvRetirado ? ' style="opacity:0.55;"' : '';
                return `
                <tr data-module-row="contratos" data-id="${esc(id)}"${rowStyle}>
                    <td>${mark(nombreCliente, search)}</td>
                    <td>${mark(c.cedula || '—', search)}</td>
                    <td>${srvCell}</td>
                    <td>${esc(c.fecha_inicio)}</td>
                    <td>${esc(c.fecha_fin || 'Indefinido')}</td>
                    <td>${esc(formatTipo(c.tipo_contrato))}</td>
                    <td><span class="status ${getStatusClass(c.estado)}">${esc(c.estado)}</span></td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="contratos"]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['contratos'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="contratos"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['contratos'] = id;
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-module="contratos"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                    });
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('contratos-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-contratos-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-contratos-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-contratos-prev')?.addEventListener('click', () => {
                if (window.ContratoController) window.ContratoController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-contratos-next')?.addEventListener('click', () => {
                if (window.ContratoController) window.ContratoController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('contratos-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        populateSelects: function (selectedServicioId, servicioFallback) {
            const sel = document.getElementById('contrato-servicio');
            if (!sel) return;
            const cache = window.ServicioModel ? window.ServicioModel.cache : [];
            let html = '<option value="">Selecciona un servicio</option>';
            let found = false;

            html += cache.filter(s => s.estado_comercial !== 'retirado').map(s => {
                const id = s.id || s.id_servicio;
                if (String(id) === String(selectedServicioId)) found = true;
                const nombre = `${s.nombres || ''} ${s.apellidos || ''}`.trim() || 'Cliente';
                const cedula = s.cedula ? ` (C.I. ${s.cedula})` : '';
                const alias = s.alias ? ` — ${s.alias}` : '';
                const plan = s.plan_nombre || 'Sin plan';
                const precio = s.plan_precio != null ? ` $${Number(s.plan_precio).toFixed(2)}` : '';
                return `<option value="${esc(id)}">${esc(nombre)}${esc(cedula)}${esc(alias)} (${esc(plan)}${esc(precio)})</option>`;
            }).join('');

            // Si el servicio actual no está en cache (fue retirado), inyectar
            // <option> con datos del JOIN marcados como SERVICIO RETIRADO.
            if (selectedServicioId && !found && servicioFallback) {
                html += `<option value="${esc(selectedServicioId)}">${esc(servicioFallback)}</option>`;
            }

            sel.innerHTML = html;
            if (selectedServicioId) sel.value = selectedServicioId;
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('contrato-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['contrato-id', 'contrato-fecha-inicio', 'contrato-fecha-fin', 'contrato-observaciones', 'contrato-draft-preview'].forEach(id => {
                    const el = document.getElementById(id); if (el) el.value = '';
                });
                const elT = document.getElementById('contrato-tipo'); if (elT) elT.value = 'indefinido';

                // UX-01: en creación, el estado solo puede ser 'vigente'.
                const elE = document.getElementById('contrato-estado');
                if (elE) {
                    elE.value = 'vigente';
                    elE.disabled = true;
                    Array.from(elE.options).forEach(opt => {
                        opt.style.display = (opt.value === 'vigente') ? '' : 'none';
                    });
                }

                // En creación, el select de servicio está habilitado.
                const elSrv = document.getElementById('contrato-servicio');
                if (elSrv) elSrv.disabled = false;

                this.populateSelects(null, null);
                if (window.ContratoController) {
                    window.ContratoController._dirty = false;
                }
                this._refreshDraft();
                const first = m.querySelector('select, input:not([type="hidden"])');
                if (first) setTimeout(() => first.focus(), 50);
            }
        },

        fillForm: function (c) {
            document.getElementById('contrato-id').value = c.id || '';

            // Bug A: si el servicio del contrato está retirado, inyectar fallback
            // visible y deshabilitar el select.
            const srvRetirado = String(c.servicio_estado || '').toLowerCase() === 'retirado';
            const nombre = `${c.nombres || ''} ${c.apellidos || ''}`.trim() || 'Cliente';
            const cedula = c.cedula ? ` (C.I. ${c.cedula})` : '';
            const alias = c.servicio_alias ? ` — ${c.servicio_alias}` : '';
            const fallback = srvRetirado
                ? `${nombre}${cedula}${alias} (SERVICIO RETIRADO)`
                : (c.servicio_id ? `${nombre}${cedula}${alias}` : null);
            this.populateSelects(c.servicio_id || '', fallback);

            document.getElementById('contrato-fecha-inicio').value = c.fecha_inicio || '';
            document.getElementById('contrato-fecha-fin').value = c.fecha_fin || '';
            document.getElementById('contrato-tipo').value = c.tipo_contrato || 'indefinido';
            document.getElementById('contrato-observaciones').value = c.observaciones || '';

            // Bug 1 (B3.7-hotfix2): 'rescindido' es estado terminal. No se puede
            // cambiar desde la UI. El backend sigue permitiendo la transición
            // (F2.6bis, Fase 2), pero la UI no la expone.
            const esRescindido = String(c.estado || '').toLowerCase() === 'rescindido';
            const elE = document.getElementById('contrato-estado');
            if (elE) {
                if (esRescindido) {
                    elE.value = 'rescindido';
                    elE.disabled = true;
                } else {
                    elE.disabled = false;
                    Array.from(elE.options).forEach(opt => { opt.style.display = ''; });
                    elE.value = c.estado || 'vigente';
                }
            }

            // El select de servicio se bloquea si el servicio está retirado
            // o si el contrato es rescindido (no se reasigna un contrato cerrado).
            const elSrv = document.getElementById('contrato-servicio');
            if (elSrv) elSrv.disabled = srvRetirado || esRescindido;

            if (window.ContratoController) window.ContratoController._dirty = false;
            this._refreshDraft();
        },

        _refreshDraft: function () {
            const draft = document.getElementById('contrato-draft-preview');
            if (!draft) return;
            const idSrv = document.getElementById('contrato-servicio')?.value;
            const srv = window.ServicioModel
                ? window.ServicioModel.cache.find(s => String(s.id || s.id_servicio) === String(idSrv))
                : null;
            const clName = srv
                ? (`${srv.nombres || ''} ${srv.apellidos || ''}`.trim() || srv.cedula || 'cliente')
                : 'cliente';
            const dir = srv?.direccion_texto || 'sin dirección';
            const alias = srv?.alias ? ` (${srv.alias})` : '';
            const fIni = document.getElementById('contrato-fecha-inicio')?.value || 'fecha por definir';
            const fFin = document.getElementById('contrato-fecha-fin')?.value || 'sin fecha de fin';
            const tipo = document.getElementById('contrato-tipo')?.value || 'indefinido';
            const obs = document.getElementById('contrato-observaciones')?.value.trim() || 'Sin observaciones.';
            const tipoLbl = tipo === 'plazo_fijo' ? 'a plazo fijo' : tipo === 'promocional' ? 'promocional' : 'indefinido';

            draft.value = `Borrador de contrato de prestación de servicios.\n\nREDYTELCA acuerda con ${clName} la prestación del servicio${alias} en ${dir}. El presente acuerdo inicia el ${fIni} y, en caso de aplicarse, concluye el ${fFin}. La modalidad contractual corresponde a un contrato ${tipoLbl}. Observaciones: ${obs}`;
        },

        _requestClose: function () {
            if (!window.ContratoController || !window.ContratoController._dirty) {
                this.toggleForm(true);
                return;
            }
            window.Modal.confirm('¿Descartar cambios sin guardar?', { danger: true, confirmText: 'Descartar' })
                .then(ok => {
                    if (ok) {
                        this.toggleForm(true);
                        if (window.ContratoController) window.ContratoController._dirty = false;
                    }
                });
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['contratos'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="contratos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="contratos"] [data-action="new"]');
            if (btnNew) { btnNew.removeAttribute('onclick'); btnNew.addEventListener('click', () => this.toggleForm()); }

            const btnEdit = document.querySelector('.toolbar[data-toolbar="contratos"] [data-action="edit"]');
            if (btnEdit) { btnEdit.removeAttribute('onclick'); btnEdit.addEventListener('click', () => { const id = window.selectedIds?.contratos; if (id) window.ContratoController.edit(id); }); }

            const btnDel = document.querySelector('.toolbar[data-toolbar="contratos"] [data-action="delete"]');
            if (btnDel) { btnDel.removeAttribute('onclick'); btnDel.addEventListener('click', () => { const id = window.selectedIds?.contratos; if (id) window.ContratoController.confirmDelete(id); }); }

            const search = document.getElementById('contratos-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.ContratoController._searchTimer);
                    window.ContratoController._searchTimer = setTimeout(() => window.ContratoController.renderPage(1), 250);
                });
            }

            const btnSave = document.querySelector('#contrato-form [data-action="save-contrato"]');
            if (btnSave) { btnSave.removeAttribute('onclick'); btnSave.addEventListener('click', () => window.ContratoController.save()); }

            const btnExport = document.querySelector('#contrato-form [data-action="export-contrato"]');
            if (btnExport) { btnExport.removeAttribute('onclick'); btnExport.addEventListener('click', () => window.ContratoController.exportContratoPdf()); }

            const btnCancel = document.querySelector('#contrato-form [data-action="cancel-contrato"]');
            if (btnCancel) { btnCancel.removeAttribute('onclick'); btnCancel.addEventListener('click', () => this._requestClose()); }

            const btnClose = document.querySelector('#contrato-modal .modal-close');
            if (btnClose) { btnClose.removeAttribute('onclick'); btnClose.addEventListener('click', () => this._requestClose()); }

            ['contrato-servicio', 'contrato-fecha-inicio', 'contrato-fecha-fin', 'contrato-tipo'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.removeAttribute('onchange');
                    el.removeAttribute('oninput');
                    el.addEventListener('change', () => this._refreshDraft());
                    el.addEventListener('input', () => this._refreshDraft());
                }
            });

            const obs = document.getElementById('contrato-observaciones');
            if (obs) {
                obs.removeAttribute('oninput');
                obs.removeAttribute('onchange');
                obs.addEventListener('input', () => {
                    this._refreshDraft();
                    if (window.ContratoController) window.ContratoController._dirty = true;
                });
            }

            ['contrato-fecha-inicio', 'contrato-fecha-fin', 'contrato-tipo', 'contrato-estado', 'contrato-servicio'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', () => { if (window.ContratoController) window.ContratoController._dirty = true; });
                if (el) el.addEventListener('change', () => { if (window.ContratoController) window.ContratoController._dirty = true; });
            });

            const form = document.getElementById('contrato-form');
            if (form) {
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                        e.preventDefault();
                        if (window.ContratoController) window.ContratoController.save();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this._requestClose();
                    }
                });
            }
        }
    };
})();