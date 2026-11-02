// js/controllers/MapaController.js
(function () {
    'use strict';

    window.MapaController = {
        _map: null,
        _initialized: false,
        _baseLayers: null,
        _markers: [],
        _markersById: new Map(),
        _filterState: { router: '', emisor: '', ubicacion: '' },
        _fallbackCenter: { lat: 10.6427, lon: -71.6125 },
        _pendingFocus: null,
        _allClientes: null,
        _allServicios: null,

        init: function () {
            if (window.MapaView) window.MapaView._attachListeners();
        },

        load: async function () {
            if (window.NodoModel && window.NodoModel.cache.length === 0) await window.NodoModel.load();
            if (window.OltModel && window.OltModel.cache.length === 0) await window.OltModel.load();
            if (window.NapModel && window.NapModel.cache.length === 0) await window.NapModel.load();

            await this._loadAllServicios();
            await this._loadAllClientes();

            this._initMap();
            this._capturePendingFocus();

            if (this._pendingFocus) {
                this._filterState = { router: '', emisor: '', ubicacion: '' };
                ['map-filter-router', 'map-filter-emisor', 'map-filter-ubicacion'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
            }

            if (this._map) {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        if (!this._map) return;
                        this._map.invalidateSize();
                        this.renderPage();
                    });
                });
            } else {
                this.renderPage();
            }
        },

        _loadAllServicios: async function () {
            try {
                const res = await window.ApiClient.get('api_servicios.php');
                if (res.status === 'success' && Array.isArray(res.servicios)) {
                    this._allServicios = res.servicios;
                    return;
                }
            } catch (e) { /* fallback */ }
            this._allServicios = window.ServicioModel ? window.ServicioModel.cache.slice() : [];
        },

        _loadAllClientes: async function () {
            const perPage = 100;
            const all = [];
            let page = 1;
            let pages = 1;
            try {
                do {
                    const res = await window.ApiClient.get('clientes_listar.php', {
                        filter: '', page: page, per_page: perPage
                    });
                    if (res.status !== 'success') break;
                    const rows = Array.isArray(res.clientes) ? res.clientes : [];
                    for (let i = 0; i < rows.length; i++) all.push(rows[i]);
                    pages = Number(res.pages) || 1;
                    page += 1;
                } while (page <= pages);
            } catch (e) { /* fallback */ }

            if (all.length === 0 && window.ClienteModel) {
                this._allClientes = window.ClienteModel.cache.slice();
            } else {
                this._allClientes = all;
            }
        },

        _initMap: function () {
            if (this._initialized || typeof L === 'undefined') return this._map;
            const container = document.getElementById('map-embed');
            if (!container) return null;

            const map = L.map('map-embed', { zoomControl: true, scrollWheelZoom: true });
            const normal = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
            const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });

            this._baseLayers = { Mapa: normal, Satelital: sat };
            normal.addTo(map);
            L.control.layers(this._baseLayers, null, { position: 'topright' }).addTo(map);
            map.setView([this._fallbackCenter.lat, this._fallbackCenter.lon], 13);

            // B3.13 — bug 1: los clientes se dibujan ENCIMA de la red.
            // Si un cliente comparte coordenadas con un nodo (dato del seed),
            // el cliente gana el z-index y es visible. Splynx/SmartOLT/AdminOLT
            // usan este orden: red al fondo, clientes encima.
            const PANES = [
                { name: 'nodosPane', z: 610 },
                { name: 'oltsPane', z: 620 },
                { name: 'napsPane', z: 630 },
                { name: 'serviciosPane', z: 640 },
                { name: 'clientesPane', z: 650 }
            ];
            PANES.forEach(p => {
                map.createPane(p.name);
                map.getPane(p.name).style.zIndex = String(p.z);
            });

            this._map = map;
            this._initialized = true;
            window.leafletMapInstance = map;
            return map;
        },

        _paneForType: function (type) {
            switch (type) {
                case 'Nodo': return 'nodosPane';
                case 'Router': return 'oltsPane';
                case 'Emisor': return 'napsPane';
                case 'Cliente': return 'clientesPane';
                case 'Servicio': return 'serviciosPane';
                default: return 'overlayPane';
            }
        },

        _capturePendingFocus: function () {
            if (!window.mapaFocus) return;
            const f = window.mapaFocus;
            window.mapaFocus = null;
            if (typeof f.lat === 'number' && typeof f.lon === 'number'
                && Number.isFinite(f.lat) && Number.isFinite(f.lon)
                && !(f.lat === 0 && f.lon === 0)) {
                this._pendingFocus = {
                    lat: f.lat,
                    lon: f.lon,
                    label: f.label || '',
                    markerId: f.markerId || null
                };
            }
        },

        _getItems: function () {
            const items = [];
            const nodoCache = window.NodoModel ? window.NodoModel.cache : [];
            const oltCache = window.OltModel ? window.OltModel.cache : [];
            const napCache = window.NapModel ? window.NapModel.cache : [];
            const servicioCache = this._allServicios || (window.ServicioModel ? window.ServicioModel.cache : []);
            const clienteCache = this._allClientes || (window.ClienteModel ? window.ClienteModel.cache : []);

            const hasCoords = function (lat, lon) {
                if (lat == null || lon == null) return false;
                const la = Number(lat);
                const lo = Number(lon);
                return Number.isFinite(la) && Number.isFinite(lo) && la !== 0 && lo !== 0;
            };

            const findNapByCodigo = function (codigo) {
                if (!codigo) return null;
                return napCache.find(n => n.codigo === codigo) || null;
            };

            nodoCache.forEach(n => {
                if (hasCoords(n.latitud, n.longitud)) {
                    const oltsDelNodo = oltCache.filter(o => String(o.nodo_id) === String(n.id));
                    const napsDelNodo = napCache.filter(na => {
                        const olt = oltCache.find(o => String(o.id) === String(na.olt_id));
                        return olt && String(olt.nodo_id) === String(n.id);
                    });
                    items.push({
                        id: 'nodo-' + n.id, type: 'Nodo', icon: 'N',
                        title: n.nombre, subtitle: n.ubicacion || '',
                        meta: 'Lat ' + n.latitud,
                        lat: Number(n.latitud), lon: Number(n.longitud),
                        className: 'nodo',
                        _nodoId: String(n.id),
                        _oltIds: oltsDelNodo.map(o => String(o.id)),
                        _napIds: napsDelNodo.map(na => String(na.id)),
                        _olts: oltsDelNodo,
                        _naps: napsDelNodo,
                        ubicacionValue: n.ubicacion || ''
                    });
                }
            });

            oltCache.forEach(o => {
                const nodo = nodoCache.find(n => String(n.id) === String(o.nodo_id));
                if (nodo && hasCoords(nodo.latitud, nodo.longitud)) {
                    const oltsDelNodo = oltCache.filter(x => String(x.nodo_id) === String(nodo.id));
                    const idx = oltsDelNodo.findIndex(x => String(x.id) === String(o.id));
                    const total = Math.max(oltsDelNodo.length, 1);
                    const angle = (idx / total) * 2 * Math.PI;
                    const radius = 0.0012;
                    const oltLat = Number(nodo.latitud) + Math.sin(angle) * radius;
                    const oltLon = Number(nodo.longitud) + Math.cos(angle) * radius;
                    const label = o.codigo || o.marca_modelo;
                    const nodoNombre = o.nombre_nodo || nodo.nombre;
                    items.push({
                        id: 'olt-' + o.id, type: 'Router', icon: 'O',
                        title: label, subtitle: 'en ' + nodoNombre,
                        meta: o.marca_modelo || '',
                        lat: oltLat, lon: oltLon,
                        className: 'router',
                        _nodoId: String(nodo.id),
                        _nodoNombre: nodoNombre,
                        _oltId: String(o.id),
                        _napIds: napCache.filter(na => String(na.olt_id) === String(o.id)).map(na => String(na.id)),
                        routerValue: label,
                        emisorValue: nodoNombre,
                        ubicacionValue: nodoNombre
                    });
                }
            });

            napCache.forEach(n => {
                if (hasCoords(n.latitud, n.longitud)) {
                    const olt = oltCache.find(o => String(o.id) === String(n.olt_id));
                    const nodo = olt ? nodoCache.find(nd => String(nd.id) === String(olt.nodo_id)) : null;
                    const oltCodigo = olt ? (olt.codigo || olt.marca_modelo) : '';
                    const nodoNombre = nodo ? nodo.nombre : '';
                    const partes = [];
                    if (oltCodigo) partes.push(oltCodigo);
                    if (nodoNombre) partes.push(nodoNombre);
                    items.push({
                        id: 'nap-' + n.id, type: 'Emisor', icon: 'P',
                        title: n.codigo, subtitle: partes.join(' · '),
                        meta: n.ubicacion_fisica || '',
                        lat: Number(n.latitud), lon: Number(n.longitud),
                        className: 'emisor',
                        _nodoId: nodo ? String(nodo.id) : null,
                        _nodoNombre: nodoNombre,
                        _oltId: olt ? String(olt.id) : null,
                        _oltCodigo: oltCodigo,
                        _napId: String(n.id),
                        routerValue: n.olt_codigo || '',
                        emisorValue: n.codigo,
                        ubicacionValue: n.ubicacion_fisica || ''
                    });
                }
            });

            clienteCache.forEach(c => {
                if (hasCoords(c.cliente_latitud, c.cliente_longitud)) {
                    const nombre = ((c.nombre || c.nombres || '') + ' ' + (c.apellido || c.apellidos || '')).trim();
                    const cedula = c.cedula ? ' (C.I. ' + c.cedula + ')' : '';
                    const dirFiscal = [c.direccion_cliente, c.cliente_carrera, c.cliente_avenida]
                        .filter(Boolean).join(', ');
                    items.push({
                        id: 'cliente-' + (c.id || c.id_cliente),
                        type: 'Cliente',
                        direccionTipo: 'fiscal',
                        icon: 'C',
                        title: nombre || 'Cliente',
                        subtitle: 'Dirección fiscal' + cedula,
                        meta: dirFiscal,
                        lat: Number(c.cliente_latitud),
                        lon: Number(c.cliente_longitud),
                        className: 'client-fiscal',
                        _nodoId: null,
                        _oltId: null,
                        _napId: null,
                        routerValue: '',
                        emisorValue: '',
                        ubicacionValue: dirFiscal
                    });
                }
            });

            servicioCache.forEach(s => {
                if (hasCoords(s.latitud, s.longitud)) {
                    const nombre = ((s.nombres || '') + ' ' + (s.apellidos || '')).trim();
                    const cedula = s.cedula ? ' (C.I. ' + s.cedula + ')' : '';
                    const alias = s.alias || ('Servicio #' + s.id);
                    const nap = findNapByCodigo(s.nap_codigo);
                    const olt = nap ? oltCache.find(o => String(o.id) === String(nap.olt_id)) : null;
                    const nodo = olt ? nodoCache.find(nd => String(nd.id) === String(olt.nodo_id)) : null;
                    const oltCodigo = olt ? (olt.codigo || olt.marca_modelo) : '';
                    const nodoNombre = nodo ? nodo.nombre : '';

                    const cadenaRed = [];
                    if (s.nap_codigo) cadenaRed.push(s.nap_codigo);
                    if (oltCodigo) cadenaRed.push(oltCodigo);
                    if (nodoNombre) cadenaRed.push(nodoNombre);
                    const cadenaTxt = cadenaRed.length ? ' · ' + cadenaRed.join(' · ') : '';

                    // B3.13 — bug 2: label del equipo asignado (si lo tiene).
                    const equipoLabel = s.equipo_tipo
                        ? (s.equipo_tipo + ' ' + (s.equipo_mac || '') + (s.equipo_estado ? ' (' + s.equipo_estado + ')' : '')).trim()
                        : '';

                    items.push({
                        id: 'servicio-' + s.id,
                        type: 'Servicio',
                        direccionTipo: 'instalacion',
                        icon: 'S',
                        title: nombre || alias,
                        subtitle: 'Instalación' + cedula + ' — ' + alias + cadenaTxt,
                        meta: s.direccion_texto || '',
                        lat: Number(s.latitud),
                        lon: Number(s.longitud),
                        className: 'client-service',
                        _nodoId: nodo ? String(nodo.id) : null,
                        _nodoNombre: nodoNombre,
                        _oltId: olt ? String(olt.id) : null,
                        _oltCodigo: oltCodigo,
                        _napId: nap ? String(nap.id) : null,
                        _napCodigo: s.nap_codigo || '',
                        _equipoLabel: equipoLabel,
                        routerValue: oltCodigo,
                        emisorValue: s.nap_codigo || '',
                        ubicacionValue: s.direccion_texto || ''
                    });
                }
            });

            return items;
        },

        _filteredItems: function (items) {
            const f = this._filterState;
            const hasRouter = !!f.router;
            const hasEmisor = !!f.emisor;
            const hasUbicacion = !!f.ubicacion;

            if (!hasRouter && !hasEmisor && !hasUbicacion) return items;

            let selectedOlt = null;
            let selectedNap = null;
            let selectedNodo = null;

            if (hasRouter) {
                selectedOlt = items.find(i => i.type === 'Router' && i.routerValue === f.router);
                if (selectedOlt) {
                    selectedNodo = items.find(i => i.type === 'Nodo' && i._nodoId === selectedOlt._nodoId);
                }
            }

            if (hasEmisor) {
                selectedNap = items.find(i => i.type === 'Emisor' && i.emisorValue === f.emisor);
                if (selectedNap) {
                    if (!selectedNodo && selectedNap._nodoId) {
                        selectedNodo = items.find(i => i.type === 'Nodo' && i._nodoId === selectedNap._nodoId);
                    }
                    if (!selectedOlt && selectedNap._oltId) {
                        selectedOlt = items.find(i => i.type === 'Router' && i._oltId === selectedNap._oltId);
                    }
                }
            }

            return items.filter(i => {
                if (hasUbicacion && i.ubicacionValue !== f.ubicacion) return false;

                if (hasRouter || hasEmisor) {
                    if (i.type === 'Nodo') {
                        return selectedNodo && i._nodoId === selectedNodo._nodoId;
                    }
                    if (i.type === 'Router') {
                        if (hasRouter) return i.routerValue === f.router;
                        if (hasEmisor && selectedNap) return i._oltId === selectedNap._oltId;
                        return false;
                    }
                    if (i.type === 'Emisor') {
                        if (hasEmisor) return i.emisorValue === f.emisor;
                        if (hasRouter && selectedOlt) return i._oltId === selectedOlt._oltId;
                        return false;
                    }
                    if (i.type === 'Servicio') {
                        if (hasEmisor) return i.emisorValue === f.emisor;
                        if (hasRouter && selectedOlt) {
                            return selectedOlt._napIds && selectedOlt._napIds.includes(i._napId);
                        }
                        return false;
                    }
                    if (i.type === 'Cliente') return false;
                }

                return true;
            });
        },

        renderPage: function () {
            if (!this._map) return;
            window.MapaView.populateFilters();
            const filtered = this._filteredItems(this._getItems());
            window.MapaView.renderList(filtered);
            this._renderMarkers(filtered);
            window.MapaView.toggleFilterBadge(this._hasFilters());
        },

        _hasFilters: function () {
            const f = this._filterState;
            return !!(f.router || f.emisor || f.ubicacion);
        },

        _iconColorByType: function (type) {
            switch (type) {
                case 'Nodo': return '#3b82f6';
                case 'Router': return '#16a34a';
                case 'Emisor': return '#f59e0b';
                case 'Cliente': return '#a855f7';
                case 'Servicio': return '#ec4899';
                default: return '#64748b';
            }
        },

        _buildPopupHtml: function (i) {
            let html = '<div style="min-width:180px;">';
            html += '<strong>' + this._esc(i.title) + '</strong>';
            if (i.subtitle) html += '<br><span>' + this._esc(i.subtitle) + '</span>';
            if (i.meta) html += '<br><small style="color:#64748b;">' + this._esc(i.meta) + '</small>';

            const lineas = [];
            if (i.type === 'Nodo') {
                const olts = i._olts || [];
                const naps = i._naps || [];
                if (olts.length > 0) {
                    lineas.push('OLTs (' + olts.length + '): ' + olts.map(o => this._esc(o.codigo || o.marca_modelo)).join(', '));
                }
                if (naps.length > 0) {
                    lineas.push('NAPs (' + naps.length + '): ' + naps.map(na => this._esc(na.codigo)).join(', '));
                }
            } else if (i.type === 'Router') {
                // B3.13 — bug 4: la OLT muestra sus NAPs en el popup.
                // Patrón AdminOLT (marcador NAP con total de ONUs) y
                // Wispro (navegador jerárquico OLT → NAP → ONU → Cliente).
                if (i._nodoNombre) lineas.push('Nodo: ' + this._esc(i._nodoNombre));
                if (window.NapModel && window.NapModel.cache) {
                    const naps = window.NapModel.cache.filter(na => String(na.olt_id) === String(i._oltId));
                    if (naps.length > 0) {
                        const maxMostrar = 5;
                        const nombres = naps.slice(0, maxMostrar).map(na => this._esc(na.codigo)).join(', ');
                        const extra = naps.length > maxMostrar ? ' … y ' + (naps.length - maxMostrar) + ' más' : '';
                        lineas.push('NAPs (' + naps.length + '): ' + nombres + extra);
                    } else {
                        lineas.push('Sin NAPs');
                    }
                }
            } else if (i.type === 'Emisor') {
                if (i._oltCodigo) lineas.push('OLT: ' + this._esc(i._oltCodigo));
                if (i._nodoNombre) lineas.push('Nodo: ' + this._esc(i._nodoNombre));
            } else if (i.type === 'Servicio') {
                if (i._napCodigo) lineas.push('NAP: ' + this._esc(i._napCodigo));
                if (i._oltCodigo) lineas.push('OLT: ' + this._esc(i._oltCodigo));
                if (i._nodoNombre) lineas.push('Nodo: ' + this._esc(i._nodoNombre));
                // B3.13 — bug 2: el servicio muestra su equipo.
                if (i._equipoLabel) lineas.push('Equipo: ' + this._esc(i._equipoLabel));
            }

            if (lineas.length > 0) {
                html += '<hr style="margin:6px 0;border:none;border-top:1px solid #e2e8f0;">';
                html += '<small style="line-height:1.5;">' + lineas.join('<br>') + '</small>';
            }
            html += '</div>';
            return html;
        },

        _renderMarkers: function (items) {
            if (!this._map) return;
            this._markers.forEach(m => m.remove());
            this._markers = [];
            this._markersById.clear();

            const bounds = [];
            items.forEach(i => {
                if (i.lat === 0 && i.lon === 0) return;

                const color = this._iconColorByType(i.type);
                const pane = this._paneForType(i.type);
                const iconHtml = '<div style="'
                    + 'width:28px;height:28px;border-radius:50%;'
                    + 'background:' + color + ';color:#fff;'
                    + 'display:flex;align-items:center;justify-content:center;'
                    + 'font-weight:700;font-size:13px;font-family:sans-serif;'
                    + 'box-shadow:0 2px 6px rgba(0,0,0,0.45);'
                    + 'border:2px solid #fff;'
                    + '">' + i.icon + '</div>';

                const m = L.marker([i.lat, i.lon], {
                    pane: pane,
                    icon: L.divIcon({
                        html: iconHtml,
                        className: 'leaflet-map-marker',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    })
                }).bindPopup(this._buildPopupHtml(i)).addTo(this._map);
                this._markers.push(m);
                this._markersById.set(i.id, m);
                bounds.push([i.lat, i.lon]);
            });

            if (this._pendingFocus) {
                // Si el caller pasó markerId y el marcador existe, centrar
                // en las coordenadas REALES del marcador (importante para
                // OLT, que vive en offset alrededor del nodo). Si no,
                // fallback a las coordenadas del pendingFocus.
                const marker = this._pendingFocus.markerId
                    ? this._markersById.get(this._pendingFocus.markerId)
                    : null;
                if (marker) {
                    this._map.setView(marker.getLatLng(), 16);
                    marker.openPopup();
                } else {
                    this._map.setView([this._pendingFocus.lat, this._pendingFocus.lon], 16);
                }
                this._pendingFocus = null;
                return;
            }

            if (bounds.length === 0) return;

            if (bounds.length === 1) {
                this._map.setView(bounds[0], 15);
                return;
            }

            this._map.invalidateSize();
            this._map.fitBounds(L.latLngBounds(bounds), { padding: [40, 40], maxZoom: 16 });
        },

        _esc: function (s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        },

        centerOnItem: function (id) {
            const m = this._markersById.get(id);
            if (m && this._map) {
                this._map.invalidateSize();
                this._map.setView(m.getLatLng(), 15);
                m.openPopup();
            }
        },

        // B3.13 — API pública. markerId opcional: 'nodo-N', 'nap-N',
        // 'olt-N', 'servicio-N', 'cliente-N'. Si se pasa y el marcador
        // existe, centra en sus coords reales y abre su popup.
        openAt: function (lat, lon, label, markerId) {
            const la = Number(lat);
            const lo = Number(lon);
            if (!Number.isFinite(la) || !Number.isFinite(lo)) return false;
            if (la === 0 && lo === 0) return false;
            window.mapaFocus = {
                lat: la,
                lon: lo,
                label: String(label || ''),
                markerId: markerId ? String(markerId) : null
            };
            if (window.Router) window.Router.showPage('mapa');
            return true;
        },

        onFilterChange: function () {
            this._filterState = {
                router: document.getElementById('map-filter-router')?.value || '',
                emisor: document.getElementById('map-filter-emisor')?.value || '',
                ubicacion: document.getElementById('map-filter-ubicacion')?.value || ''
            };
            this.renderPage();
        },

        clearFilters: function () {
            this._filterState = { router: '', emisor: '', ubicacion: '' };
            ['map-filter-router', 'map-filter-emisor', 'map-filter-ubicacion'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            this.renderPage();
        },

        getFilterState: function () {
            return this._filterState;
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.MapaController.init());
})();