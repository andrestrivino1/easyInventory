/**
 * Alpine.js component para el formulario de Liquidación de Viaje.
 * Replica las fórmulas de App\Services\LiquidacionCalculator en cliente.
 */
window.liquidacionForm = function (config) {
    return {
        // Configuración inyectada desde Blade
        categories: config.categories || [],
        routePeajesUrlTpl: config.routePeajesUrlTpl,
        driverInfoUrlTpl: config.driverInfoUrlTpl,

        // Estado reactivo
        anticipo: parseInt(config.initialAnticipo || 0, 10),
        sobreanticipo: parseInt(config.initialSobreanticipo || 0, 10),
        valorFlete: parseInt(config.initialFlete || 0, 10),
        expenses: [],
        tolls: [],

        // Init: construye las 16 filas de gastos (pre-llena con existing si edit)
        init() {
            const existingMap = {};
            (config.existingExpenses || []).forEach(e => {
                existingMap[e.expense_category_id] = e;
            });

            this.expenses = this.categories.map(cat => {
                const existing = existingMap[cat.id];
                return {
                    expense_category_id: cat.id,
                    category_name: cat.name,
                    has_galones: cat.has_galones,
                    valor: existing ? parseInt(existing.valor, 10) || 0 : 0,
                    galones: existing && existing.galones !== null ? parseFloat(existing.galones) : null,
                };
            });

            this.tolls = (config.existingTolls || []).map(t => ({
                name: t.name,
                valor: parseInt(t.valor, 10) || 0,
                sort_order: parseInt(t.sort_order, 10),
                direction: t.direction || 'ida',
                route_toll_id: t.route_toll_id || null,
                is_adhoc: !!t.is_adhoc,
                is_used: t.is_used !== false,
                paid_by: t.paid_by || 'empresa',
            }));
        },

        // Fórmulas (deben coincidir EXACTAMENTE con LiquidacionCalculator.php)
        get sumGastosOperativos() {
            return this.expenses.reduce((s, e) => s + (parseInt(e.valor, 10) || 0), 0);
        },
        get sumPeajes() {
            return this.tolls
                .filter(t => t.is_used)
                .reduce((s, t) => s + (parseInt(t.valor, 10) || 0), 0);
        },
        get sumPeajesConductor() {
            return this.tolls
                .filter(t => t.is_used && t.paid_by === 'conductor')
                .reduce((s, t) => s + (parseInt(t.valor, 10) || 0), 0);
        },
        get sumPeajesEmpresa() {
            return this.sumPeajes - this.sumPeajesConductor;
        },
        get sumGastosTotales() {
            return this.sumGastosOperativos + this.sumPeajes;
        },
        get totalAnticipos() {
            return (parseInt(this.anticipo, 10) || 0) + (parseInt(this.sobreanticipo, 10) || 0);
        },
        get saldoViaje() {
            return this.totalAnticipos - this.sumGastosOperativos - this.sumPeajesConductor;
        },
        get gananciaViaje() {
            return (parseInt(this.valorFlete, 10) || 0) - this.sumGastosOperativos - this.sumPeajesEmpresa;
        },
        get aFavorDeLabel() {
            const s = this.saldoViaje;
            if (s > 0) return 'EMPRESA';
            if (s < 0) return 'CONDUCTOR';
            return 'NINGUNO';
        },

        // Helpers
        recalc() {
            // Alpine getters ya son reactivos; esta función es opcional para forzar repaint si hace falta
        },

        formatMoney(n) {
            const num = Math.round(parseFloat(n) || 0);
            return num.toLocaleString('es-CO');
        },

        loadDriver(driverId) {
            if (!driverId) return;
            fetch(this.driverInfoUrlTpl.replace('__ID__', driverId), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.vehicle_plate && this.$refs.plateInput) {
                    this.$refs.plateInput.value = data.vehicle_plate;
                }
            })
            .catch(err => console.error('No se pudo cargar conductor:', err));
        },

        loadRouteTolls(routeId) {
            if (!routeId) {
                // No borrar los peajes ya cargados si el usuario solo cambia a "ninguna ruta"
                // (preserva el trabajo capturado). Si quieres borrarlos, descomenta la siguiente línea:
                // this.tolls = [];
                return;
            }
            fetch(this.routePeajesUrlTpl.replace('__ID__', routeId), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                const arr = (data && data.tolls) ? data.tolls : [];
                this.tolls = arr.map(t => ({
                    name: t.name,
                    valor: parseInt(t.suggested_value, 10) || 0,
                    sort_order: parseInt(t.sort_order, 10),
                    direction: t.direction || 'ida',
                    route_toll_id: t.id,
                    is_adhoc: false,
                    is_used: true,
                    paid_by: 'empresa',
                }));
            })
            .catch(err => console.error('No se pudieron cargar los peajes de la ruta:', err));
        },

        addAdhocToll() {
            const nextOrder = this.tolls.length > 0
                ? Math.max(...this.tolls.map(t => t.sort_order)) + 1
                : 1;
            this.tolls.push({
                name: '',
                valor: 0,
                sort_order: nextOrder,
                direction: 'ida',
                route_toll_id: null,
                is_adhoc: true,
                is_used: true,
                paid_by: 'empresa',
            });
        },

        removeToll(idx) {
            this.tolls.splice(idx, 1);
        },
    };
};
