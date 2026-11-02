// js/views/MapaView.js
(function () {
    'use strict';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    var COLOR_BY_TYPE = {
        'Nodo': '#3b82f6',
        'Router': '#16a34a',
        'Emisor': '#f59e0b',
        'Cliente': '#a855f7',
        'Servicio': '#ec4899'
    };

    window.MapaView = {
        populateFilters: function () {
            const state = window.MapaController.getFilterState();
            const ubicaciones = new Set();

            const nodoCache = window.NodoModel ? window.NodoModel.cache : [];
            const oltCache = window.OltModel ? window.OltModel.cache : [];
            const napCache = window.NapModel ? window.NapModel.cache : [];

            // OLTs agrupados por Nodo
            const nodoGroups = {};
            nodoCache.forEach(n => {
                nodoGroups[String(n.id)] = {
                    nombre: n.nombre,
                    olts: oltCache.filter(o => String(o.nodo_id) === String(n.id))
                };
            });

            // NAPs agrupadas por OLT
            const napGroups = {};
            oltCache.forEach(o => {
                napGroups[String(o.id)] = {
                    codigo: o.codigo || o.marca_modelo,
                    naps: napCache.filter(na => String(na.olt_id) === String(o.id))
                };
            });

            // Ubicaciones
            nodoCache.forEach(n => { if (n.ubicacion) ubicaciones.add(n.ubicacion); });
            napCache.forEach(n => { if (n.ubicacion_fisica) ubicaciones.add(n.ubicacion_fisica); });
            const clienteCache = window.MapaController._allClientes || (window.ClienteModel ? window.ClienteModel.cache : []);
            clienteCache.forEach(c => {
                const dir = [c.direccion_cliente, c.cliente_carrera, c.cliente_avenida].filter(Boolean).join(', ');
                if (dir) ubicaciones.add(dir);
            });
            const servicioCache = window.MapaController._allServicios || (window.ServicioModel ? window.ServicioModel.cache : []);
            servicioCache.forEach(s => { if (s.direccion_texto) ubicaciones.add(s.direccion_texto); });

            // Poblar select de Router (OLTs agrupados por Nodo)
            this._fillSelectGrouped('map-filter-router', nodoGroups, 'Router (todos)', state.router);

            // Poblar select de Emisor (NAPs agrupadas por OLT)
            this._fillSelectGroupedNaps('map-filter-emisor', napGroups, 'Emisor (todos)', state.emisor);

            // Poblar select de Ubicación
            this._fillSelect('map-filter-ubicacion', ubicaciones, 'Ubicación (todas)', state.ubicacion);
        },

        _fillSelectGrouped: function (id, groups, defaultLabel, current) {
            const el = document.getElementById(id);
            if (!el) return;
            let html = '<option value="">' + esc(defaultLabel) + '</option>';
            Object.keys(groups).forEach(nodoId => {
                const g = groups[nodoId];
                if (g.olts.length === 0) return;
                html += '<optgroup label="' + esc(g.nombre) + '">';
                g.olts.forEach(o => {
                    const val = o.codigo || o.marca_modelo;
                    html += '<option value="' + esc(val) + '"' + (val === current ? ' selected' : '') + '>' + esc(val) + '</option>';
                });
                html += '</optgroup>';
            });
            el.innerHTML = html;
            if (current) el.value = current;
        },

        _fillSelectGroupedNaps: function (id, groups, defaultLabel, current) {
            const el = document.getElementById(id);
            if (!el) return;
            let html = '<option value="">' + esc(defaultLabel) + '</option>';
            Object.keys(groups).forEach(oltId => {
                const g = groups[oltId];
                if (g.naps.length === 0) return;
                html += '<optgroup label="' + esc(g.codigo) + '">';
                g.naps.forEach(na => {
                    html += '<option value="' + esc(na.codigo) + '"' + (na.codigo === current ? ' selected' : '') + '>' + esc(na.codigo) + '</option>';
                });
                html += '</optgroup>';
            });
            el.innerHTML = html;
            if (current) el.value = current;
        },

        _fillSelect: function (id, set, defaultLabel, current) {
            const el = document.getElementById(id);
            if (!el) return;
            const sorted = Array.from(set).sort();
            let html = '<option value="">' + esc(defaultLabel) + '</option>';
            html += sorted.map(v =>
                '<option value="' + esc(v) + '"' + (v === current ? ' selected' : '') + '>' + esc(v) + '</option>'
            ).join('');
            el.innerHTML = html;
            if (current) el.value = current;
        },

        renderList: function (items) {
            const list = document.getElementById('map-item-list');
            if (!list) return;
            if (!items || items.length === 0) {
                list.innerHTML = '<div class="map-item-empty">No hay datos visibles.</div>';
                return;
            }
            list.innerHTML = items.map(i => {
                const color = COLOR_BY_TYPE[i.type] || '#64748b';
                return `
                <div class="map-item map-item-${esc(i.className)}" data-map-item-id="${esc(i.id)}">
                    <div class="map-item-icon" style="background:${color};color:#fff;font-weight:700;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:15px;font-family:sans-serif;">${esc(i.icon)}</div>
                    <div class="map-item-type">${esc(i.type)}</div>
                    <div class="map-item-title">${esc(i.title)}</div>
                    <div class="map-item-subtitle">${esc(i.subtitle || '')}</div>
                    <div class="map-item-meta">${esc(i.meta || '')}</div>
                </div>
            `;
            }).join('');

            list.querySelectorAll('.map-item').forEach(card => {
                card.addEventListener('click', () => {
                    const id = card.dataset.mapItemId;
                    if (id && window.MapaController) window.MapaController.centerOnItem(id);
                });
            });
        },

        toggleFilterBadge: function (visible) {
            const badge = document.getElementById('map-filters-active');
            if (badge) badge.style.display = visible ? 'inline-flex' : 'none';
        },

        _attachListeners: function () {
            ['map-filter-router', 'map-filter-emisor', 'map-filter-ubicacion'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.removeAttribute('onchange');
                    el.addEventListener('change', () => window.MapaController.onFilterChange());
                }
            });

            document.querySelectorAll('#mapa [data-action="apply-filters"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.MapaController.onFilterChange());
            });

            document.querySelectorAll('#mapa [data-action="clear-filters"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.MapaController.clearFilters());
            });
        }
    };
})();