// js/models/PedidoModel.js
(function () {
    'use strict';

    window.PedidoModel = {
        cache: [],
        equiposStockCache: [],

        load: async function () {
            const res = await window.ApiClient.get('api_pedidos.php');
            if (res.status === 'success') {
                this.cache = res.pedidos || [];
            }
            return this.cache;
        },

        getById: function (id) {
            return this.cache.find(p => String(p.id) === String(id)) || null;
        },

        create: async function (data) {
            return window.ApiClient.post('api_pedidos.php', data);
        },

        update: async function (id, data) {
            return window.ApiClient.put('api_pedidos.php', { id_pedido: id, ...data });
        },

        delete: async function (id) {
            return window.ApiClient.delete('api_pedidos.php', { id: id });
        },

        completar: async function (id) {
            return window.ApiClient.put('api_pedidos_completar.php', { id_pedido: id });
        },

        asignarTecnico: async function (idPedido, tecnicoId) {
            return window.ApiClient.post('api_pedido_asignaciones.php', {
                id_pedido: idPedido,
                tecnico_id: tecnicoId
            });
        },

        loadAsignaciones: async function (idPedido) {
            const res = await window.ApiClient.get('api_pedido_asignaciones.php', { id_pedido: idPedido });
            if (res.status === 'success') {
                return res.asignaciones || [];
            }
            return [];
        },

        loadMateriales: async function (idPedido) {
            const res = await window.ApiClient.get('api_pedido_materiales.php', { id_pedido: idPedido });
            if (res.status === 'success') {
                return res.materiales || [];
            }
            return [];
        },

        createMaterial: async function (data) {
            return window.ApiClient.post('api_pedido_materiales.php', data);
        },

        deleteMaterial: async function (idMaterial) {
            return window.ApiClient.delete('api_pedido_materiales.php', { id: idMaterial });
        },

        /**
         * Carga los equipos en stock disponibles para reservar.
         *
         * Excluye:
         *   - Equipos que no están en `estado_inventario = 'stock'`.
         *   - Equipos ya reservados en OTROS pedidos pendientes
         *     (no completados ni cancelados). Se mira
         *     `equipo_reservado_id` de cada pedido en cache.
         *
         * El pedido actual NO se excluye: si ya tiene un equipo
         * reservado, ese equipo sigue apareciendo para que el
         * operador pueda mantenerlo o cambiarlo.
         *
         * @param {number|null} pedidoId ID del pedido actual (opcional).
         * @returns {Promise<Array>}
         */
        loadEquiposStock: async function (pedidoId = null) {
            try {
                const res = await window.ApiClient.get('api_equipos.php', { estado: 'stock' });
                if (res.status === 'success') {
                    let equipos = (res.equipos || []).filter(eq =>
                        String(eq.estado_inventario || '').toLowerCase() === 'stock'
                    );

                    // Excluir equipos reservados en otros pedidos pendientes.
                    if (pedidoId !== null) {
                        const reservadosEnOtros = this._getEquiposReservadosEnOtros(pedidoId);
                        if (reservadosEnOtros.size > 0) {
                            equipos = equipos.filter(eq => !reservadosEnOtros.has(String(eq.id)));
                        }
                    }

                    this.equiposStockCache = equipos;
                    return equipos;
                }
            } catch (e) { /* fallback */ }
            this.equiposStockCache = [];
            return this.equiposStockCache;
        },

        /**
         * Devuelve el Set de IDs de equipos reservados en pedidos
         * pendientes distintos de `pedidoId`.
         *
         * @param {number} pedidoId
         * @returns {Set<string>}
         */
        _getEquiposReservadosEnOtros: function (pedidoId) {
            const reservados = new Set();
            const pedidos = this.cache || [];
            pedidos.forEach(p => {
                if (String(p.id) === String(pedidoId)) return;
                const estado = String(p.estado || '').toLowerCase();
                if (estado === 'completado' || estado === 'cancelado') return;
                if (p.equipo_reservado_id) {
                    reservados.add(String(p.equipo_reservado_id));
                }
            });
            return reservados;
        }
    };
})();