<template>
    <div v-if="show" class="modal-overlay" @click.self="cerrar">
        <div class="modal-container" style="max-width: 750px;">
            <!-- Header -->
            <div class="modal-header bg-dark text-white d-flex justify-content-between align-items-center px-3 py-2">
                <h5 class="mb-0"><i class="fa fa-file-invoice-dollar me-2"></i>Cobrar Renta — {{ clientInfo.name }}</h5>
                <button class="btn btn-sm btn-outline-light" @click="cerrar">&times;</button>
            </div>

            <div class="modal-body p-3" style="max-height: 75vh; overflow-y: auto;">
                <!-- Loading -->
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Cargando rentas...</p>
                </div>

                <template v-if="!loading">
                    <!-- Sin rentas -->
                    <div v-if="rentas.length === 0" class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle me-2"></i>Este cliente no tiene rentas activas.
                    </div>

                    <!-- Renta unica -->
                    <div v-if="rentas.length === 1 && !rentaSeleccionada" class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>RENTA UNICA. Cargando...
                    </div>

                    <!-- Multiples rentas: selector -->
                    <div v-if="rentas.length > 1 && !rentaSeleccionada" class="mb-3">
                        <label class="form-label fw-bold">Seleccionar Renta:</label>
                        <select class="form-select" @change="seleccionarRenta($event.target.value)">
                            <option value="">-- Seleccione una renta --</option>
                            <option v-for="r in rentas" :key="r.id" :value="r.id">
                                Renta #{{ r.folio || r.id }} — {{ r.location_descripcion || 'Sin descripcion' }} ({{ r.rent_detail_count }} equipos)
                            </option>
                        </select>
                    </div>

                    <!-- Cambiar renta (si ya hay una seleccionada y hay varias) -->
                    <div v-if="rentas.length > 1 && rentaSeleccionada" class="mb-2">
                        <button class="btn btn-sm btn-outline-secondary" @click="rentaSeleccionada = null; eqContNuevos = {};">
                            <i class="fa fa-exchange me-1"></i> Cambiar Renta
                        </button>
                    </div>

                    <!-- Info de renta seleccionada -->
                    <template v-if="rentaSeleccionada">
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong>RENTA #{{ rentaSeleccionada.folio || rentaSeleccionada.id }}</strong>
                            </div>
                            <div class="card-body py-2">
                                <div class="row small">
                                    <div class="col-6"><strong>Dia de corte:</strong> {{ rentaSeleccionada.cutoff }}</div>
                                    <div class="col-6"><strong>Descripcion:</strong> {{ rentaSeleccionada.location_descripcion }}</div>
                                    <div class="col-6"><strong>Direccion:</strong> {{ rentaSeleccionada.location_address }}</div>
                                    <div class="col-6"><strong>Movil:</strong> {{ rentaSeleccionada.location_phone }}</div>
                                    <div class="col-12" v-if="rentaSeleccionada.location_email"><strong>Email:</strong> {{ rentaSeleccionada.location_email }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Cards de equipos -->
                        <div v-for="equipo in rentaSeleccionada.rent_detail" :key="equipo.id" class="card mb-3 border-primary">
                            <div class="card-header bg-primary bg-opacity-10 py-2">
                                <strong>{{ equipo.trademark }} {{ equipo.model }}</strong>
                                <small class="text-muted ms-2">No. Serie: {{ equipo.serial_number }}</small>
                                <span class="badge bg-success float-end">${{ formatNumber(equipo.rent_price) }}</span>
                            </div>
                            <div class="card-body py-2">
                                <!-- Seccion Blanco y Negro -->
                                <div v-if="equipo.monochrome && eqContNuevos[equipo.id]" class="mb-3">
                                    <h6 class="border-bottom pb-1"><i class="fa fa-print me-1"></i> Blanco y Negro</h6>
                                    <div class="row small">
                                        <div class="col-4">
                                            <span class="text-muted">Pag. incluidas:</span><br>
                                            <strong>{{ equipo.pages_included_mono }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted">Costo pag. extra:</span><br>
                                            <strong>${{ formatNumber(equipo.extra_page_cost_mono) }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted">Contador actual:</span><br>
                                            <strong>{{ equipo.counter_mono }}</strong>
                                        </div>
                                    </div>
                                    <div class="row mt-2 align-items-end">
                                        <div class="col-4">
                                            <label class="form-label small text-danger mb-0 fw-bold">Nuevo Contador</label>
                                            <input type="number" class="form-control form-control-sm"
                                                v-model.number="eqContNuevos[equipo.id].mono.contador"
                                                :min="equipo.counter_mono"
                                                @input="recalcularContador(equipo, 'mono')">
                                        </div>
                                        <div class="col-2 text-center small">
                                            <span class="text-muted">Dif.</span><br>
                                            <strong class="text-primary">{{ eqContNuevos[equipo.id].mono.diferencia }}</strong>
                                        </div>
                                        <div class="col-3 text-center small">
                                            <span class="text-muted">Pag. Exc.</span><br>
                                            <strong class="text-primary">{{ eqContNuevos[equipo.id].mono.paginas_extra }}</strong>
                                        </div>
                                        <div class="col-3 text-end small">
                                            <span class="text-muted">Costo Exc.</span><br>
                                            <strong class="text-primary">${{ formatNumber(eqContNuevos[equipo.id].mono.subtotal) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Seccion Color -->
                                <div v-if="equipo.color && eqContNuevos[equipo.id]" class="mb-3">
                                    <h6 class="border-bottom pb-1"><i class="fa fa-paint-brush me-1"></i> Color</h6>
                                    <div class="row small">
                                        <div class="col-4">
                                            <span class="text-muted">Pag. incluidas:</span><br>
                                            <strong>{{ equipo.pages_included_color }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted">Costo pag. extra:</span><br>
                                            <strong>${{ formatNumber(equipo.extra_page_cost_color) }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted">Contador actual:</span><br>
                                            <strong>{{ equipo.counter_color }}</strong>
                                        </div>
                                    </div>
                                    <div class="row mt-2 align-items-end">
                                        <div class="col-4">
                                            <label class="form-label small text-danger mb-0 fw-bold">Nuevo Contador</label>
                                            <input type="number" class="form-control form-control-sm"
                                                v-model.number="eqContNuevos[equipo.id].color.contador"
                                                :min="equipo.counter_color"
                                                @input="recalcularContador(equipo, 'color')">
                                        </div>
                                        <div class="col-2 text-center small">
                                            <span class="text-muted">Dif.</span><br>
                                            <strong class="text-primary">{{ eqContNuevos[equipo.id].color.diferencia }}</strong>
                                        </div>
                                        <div class="col-3 text-center small">
                                            <span class="text-muted">Pag. Exc.</span><br>
                                            <strong class="text-primary">{{ eqContNuevos[equipo.id].color.paginas_extra }}</strong>
                                        </div>
                                        <div class="col-3 text-end small">
                                            <span class="text-muted">Costo Exc.</span><br>
                                            <strong class="text-primary">${{ formatNumber(eqContNuevos[equipo.id].color.subtotal) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Totales por equipo -->
                                <div v-if="eqContNuevos[equipo.id]" class="bg-light rounded p-2 mt-2">
                                    <div class="d-flex justify-content-between small">
                                        <span>Renta:</span>
                                        <strong>${{ formatNumber(equipo.rent_price) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                        <span>Excedentes:</span>
                                        <strong>${{ formatNumber(eqContNuevos[equipo.id].suma_exedentes) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small fw-bold border-top pt-1 mt-1">
                                        <span>Subtotal equipo:</span>
                                        <span>${{ formatNumber(eqContNuevos[equipo.id].subtotal_x_equipo) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DETALLES DEL RECIBO -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fa fa-cog me-1"></i> Detalles del Recibo</strong>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Estatus</label>
                                        <select class="form-select form-select-sm" v-model="estatus">
                                            <option value="POR COBRAR">POR COBRAR</option>
                                            <option value="PAGADA">PAGADA</option>
                                            <option value="POR FACTURAR">POR FACTURAR</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Forma de Pago</label>
                                        <select class="form-select form-select-sm" v-model="formaPago">
                                            <option value="EFECTIVO">EFECTIVO</option>
                                            <option value="TARJETA">TARJETA</option>
                                            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Descuento -->
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Descuento</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" v-model.number="descuentoMonto"
                                                min="0" @input="calcularTotales">
                                            <button class="btn btn-outline-secondary" :class="{ active: descuentoTipo === '$' }"
                                                @click="descuentoTipo = '$'; calcularTotales()">$</button>
                                            <button class="btn btn-outline-secondary" :class="{ active: descuentoTipo === '%' }"
                                                @click="descuentoTipo = '%'; calcularTotales()">%</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Cantidad a recibir</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" v-model.number="cantidadRecibida" min="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Totales -->
                                <table class="table table-sm mb-2">
                                    <tbody>
                                        <tr>
                                            <td class="text-end"><strong>Subtotal renta:</strong></td>
                                            <td class="text-end" style="width: 120px;">${{ formatNumber(totalPagar) }}</td>
                                        </tr>
                                        <tr v-if="descuentoMonto > 0">
                                            <td class="text-end text-danger"><strong>Descuento ({{ descuentoTipo }}{{ descuentoMonto }}):</strong></td>
                                            <td class="text-end text-danger">-${{ formatNumber(descuentoCalculado) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-end">
                                                <div class="form-check form-switch d-inline-flex align-items-center">
                                                    <input class="form-check-input me-2" type="checkbox" v-model="conIva" @change="calcularTotales">
                                                    <strong>{{ $shopTaxName }} {{ $shopTaxRate }}%:</strong>
                                                </div>
                                            </td>
                                            <td class="text-end">${{ formatNumber(iva) }}</td>
                                        </tr>
                                        <tr class="table-dark">
                                            <td class="text-end"><strong>TOTAL:</strong></td>
                                            <td class="text-end"><strong>${{ formatNumber(totalFinal) }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Descripcion y Periodo -->
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Descripcion</label>
                                        <input type="text" class="form-control form-control-sm" v-model="descripcion"
                                            placeholder="Descripcion del recibo">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Periodo (mes)</label>
                                        <select class="form-select form-select-sm" v-model="rentaMes" @change="generarPeriodoTexto">
                                            <option v-for="m in meses" :key="m.value" :value="m.value">{{ m.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Año</label>
                                        <input type="number" class="form-control form-control-sm" v-model.number="rentaAnio"
                                            @change="generarPeriodoTexto">
                                    </div>
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted">{{ periodoTexto }}</small>
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>

            <!-- Footer -->
            <div v-if="rentaSeleccionada" class="modal-footer d-flex justify-content-between px-3 py-2">
                <button class="btn btn-secondary" @click="cerrar">Cancelar</button>
                <button class="btn btn-primary" :disabled="!btnHabilitado || guardando" @click="guardarRecibo">
                    <span v-if="guardando" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="fa fa-check me-1"></i>
                    Generar Recibo
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CobrarRentaModal',
    data() {
        return {
            show: false,
            loading: false,
            clientId: null,
            clientInfo: {},
            rentas: [],
            rentaSeleccionada: null,
            eqContNuevos: {},

            estatus: 'POR COBRAR',
            formaPago: 'EFECTIVO',
            descuentoMonto: 0,
            descuentoTipo: '$',
            conIva: false,
            cantidadRecibida: 0,
            descripcion: '',
            rentaMes: new Date().getMonth() + 1,
            rentaAnio: new Date().getFullYear(),
            periodoTexto: '',

            totalPagar: 0,
            descuentoCalculado: 0,
            iva: 0,
            totalFinal: 0,

            guardando: false,

            meses: [
                { value: 1, label: 'ENERO' }, { value: 2, label: 'FEBRERO' },
                { value: 3, label: 'MARZO' }, { value: 4, label: 'ABRIL' },
                { value: 5, label: 'MAYO' }, { value: 6, label: 'JUNIO' },
                { value: 7, label: 'JULIO' }, { value: 8, label: 'AGOSTO' },
                { value: 9, label: 'SEPTIEMBRE' }, { value: 10, label: 'OCTUBRE' },
                { value: 11, label: 'NOVIEMBRE' }, { value: 12, label: 'DICIEMBRE' },
            ],
        };
    },
    computed: {
        btnHabilitado() {
            if (!this.rentaSeleccionada) return false;
            // Validar que todos los equipos tengan contadores ingresados
            for (const equipo of this.rentaSeleccionada.rent_detail) {
                const ec = this.eqContNuevos[equipo.id];
                if (!ec) return false;
                if (equipo.monochrome && ec.mono.contador <= 0) return false;
                if (equipo.color && ec.color.contador <= 0) return false;
            }
            return true;
        },
    },
    methods: {
        abrir(clientId) {
            this.clientId = clientId;
            this.show = true;
            this.resetForm();
            this.cargarRentas();
        },
        resetForm() {
            this.rentas = [];
            this.rentaSeleccionada = null;
            this.eqContNuevos = {};
            this.estatus = 'POR COBRAR';
            this.formaPago = 'EFECTIVO';
            this.descuentoMonto = 0;
            this.descuentoTipo = '$';
            this.conIva = false;
            this.cantidadRecibida = 0;
            this.descripcion = '';
            this.rentaMes = new Date().getMonth() + 1;
            this.rentaAnio = new Date().getFullYear();
            this.periodoTexto = '';
            this.totalPagar = 0;
            this.descuentoCalculado = 0;
            this.iva = 0;
            this.totalFinal = 0;
        },
        async cargarRentas() {
            this.loading = true;
            try {
                const res = await axios.get(`/admin/rent-receipt/${this.clientId}/data`);
                if (res.data.ok) {
                    this.clientInfo = res.data.client;
                    this.rentas = res.data.rentas;
                    // Si solo hay una renta, cargar automaticamente
                    if (this.rentas.length === 1) {
                        this.seleccionarRenta(this.rentas[0].id);
                    }
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudieron cargar las rentas', 'error');
            } finally {
                this.loading = false;
            }
        },
        async seleccionarRenta(rentaId) {
            if (!rentaId) return;
            this.loading = true;
            try {
                const res = await axios.get(`/admin/rent-receipt/rent/${rentaId}/details`);
                if (res.data.ok) {
                    this.asignarRenta(res.data.rent);
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la renta', 'error');
            } finally {
                this.loading = false;
            }
        },
        asignarRenta(rent) {
            this.rentaSeleccionada = rent;
            // Inicializar contadores por equipo
            this.eqContNuevos = {};
            rent.rent_detail.forEach(equipo => {
                this.eqContNuevos[equipo.id] = {
                    mono: { contador: 0, diferencia: 0, paginas_extra: 0, costo_extra: 0, subtotal: 0 },
                    color: { contador: 0, diferencia: 0, paginas_extra: 0, costo_extra: 0, subtotal: 0 },
                    suma_exedentes: 0,
                    subtotal_x_equipo: 0,
                };
            });
            this.generarPeriodoTexto();
            this.calcularTotales();
        },
        recalcularContador(equipo, tipo) {
            const ec = this.eqContNuevos[equipo.id];
            if (!ec) return;

            const nuevo = ec[tipo].contador;
            const actual = tipo === 'mono' ? equipo.counter_mono : equipo.counter_color;
            const paginasIncluidas = tipo === 'mono' ? equipo.pages_included_mono : equipo.pages_included_color;
            const costoExtra = tipo === 'mono' ? equipo.extra_page_cost_mono : equipo.extra_page_cost_color;

            if (nuevo > actual) {
                const dif = nuevo - actual;
                let paginas_extra = 0;
                let subtotal = 0;
                let costo = 0;
                if (dif > paginasIncluidas) {
                    paginas_extra = dif - paginasIncluidas;
                    costo = costoExtra;
                    subtotal = paginas_extra * costoExtra;
                }
                ec[tipo].diferencia = dif;
                ec[tipo].paginas_extra = paginas_extra;
                ec[tipo].costo_extra = costo;
                ec[tipo].subtotal = subtotal;
            } else {
                ec[tipo].diferencia = 0;
                ec[tipo].paginas_extra = 0;
                ec[tipo].costo_extra = 0;
                ec[tipo].subtotal = 0;
            }

            ec.suma_exedentes = ec.mono.subtotal + ec.color.subtotal;
            ec.subtotal_x_equipo = parseFloat(equipo.rent_price) + ec.suma_exedentes;
            this.calcularTotales();
        },
        calcularTotales() {
            if (!this.rentaSeleccionada) return;

            // 1. Sumar todas las rentas + excedentes
            let tmp = 0;
            this.rentaSeleccionada.rent_detail.forEach(equipo => {
                const ec = this.eqContNuevos[equipo.id];
                if (ec) {
                    ec.suma_exedentes = ec.mono.subtotal + ec.color.subtotal;
                    ec.subtotal_x_equipo = parseFloat(equipo.rent_price) + ec.suma_exedentes;
                    tmp += ec.subtotal_x_equipo;
                }
            });
            this.totalPagar = tmp;

            // 2. Descuento
            this.descuentoCalculado = 0;
            if (this.descuentoMonto > 0) {
                if (this.descuentoTipo === '$') {
                    this.descuentoCalculado = this.descuentoMonto;
                } else {
                    this.descuentoCalculado = (this.descuentoMonto * this.totalPagar) / 100;
                }
            }
            const subConDescuento = this.totalPagar - this.descuentoCalculado;

            // 3. IVA
            this.iva = this.conIva ? subConDescuento * this.$taxDecimal : 0;

            // 4. Total
            this.totalFinal = subConDescuento + this.iva;
        },
        generarPeriodoTexto() {
            if (!this.rentaSeleccionada) return;
            const mes = this.meses.find(m => m.value === this.rentaMes);
            const mesLabel = mes ? mes.label : '';
            this.periodoTexto = `CORTE ${this.rentaSeleccionada.cutoff} DE ${mesLabel} DEL ${this.rentaAnio}`;
        },
        generarObservaciones() {
            let obs = '';
            this.rentaSeleccionada.rent_detail.forEach(equipo => {
                const ec = this.eqContNuevos[equipo.id];
                obs += `${equipo.trademark} ${equipo.model}\nNo. Serie ${equipo.serial_number}\n`;
                if (equipo.monochrome) {
                    obs += ` BK Pgs. Inc.${equipo.pages_included_mono}, Exc. $${equipo.extra_page_cost_mono}\n`;
                    obs += ` BK Cont. Ini.: ${equipo.counter_mono} Fin: ${ec.mono.contador}\n`;
                }
                if (equipo.color) {
                    obs += ` CLR Pgs. Inc.${equipo.pages_included_color}, Exc. $${equipo.extra_page_cost_color}\n`;
                    obs += ` CLR Cont. Ini.: ${equipo.counter_color} Fin: ${ec.color.contador}\n`;
                }
            });
            return obs;
        },
        generarDetail() {
            const detail = [];
            this.rentaSeleccionada.rent_detail.forEach(equipo => {
                const ec = this.eqContNuevos[equipo.id];

                // Item base: renta del equipo
                detail.push({
                    id: equipo.id,
                    type: 'rent',
                    name: `${equipo.trademark} ${equipo.model} Renta.`,
                    cost: equipo.rent_price,
                    qty: 1,
                    subtotal: equipo.rent_price,
                    discount_concept: '',
                    discount: 0,
                });

                // Excedente B/N
                if (ec.mono.costo_extra > 0) {
                    detail.push({
                        id: 0,
                        type: 'rent',
                        name: `${equipo.trademark} ${equipo.model} excedente b/n.`,
                        cost: ec.mono.costo_extra,
                        qty: ec.mono.paginas_extra,
                        subtotal: ec.mono.subtotal,
                        discount_concept: '',
                        discount: 0,
                    });
                }

                // Excedente Color
                if (ec.color.costo_extra > 0) {
                    detail.push({
                        id: 0,
                        type: 'rent',
                        name: `${equipo.trademark} ${equipo.model} excedente color.`,
                        cost: ec.color.costo_extra,
                        qty: ec.color.paginas_extra,
                        subtotal: ec.color.subtotal,
                        discount_concept: '',
                        discount: 0,
                    });
                }
            });
            return detail;
        },
        generarEqNewCounts() {
            const counts = [];
            this.rentaSeleccionada.rent_detail.forEach(equipo => {
                const ec = this.eqContNuevos[equipo.id];
                counts.push({
                    equipo_id: equipo.id,
                    equipo_new_count_monochrome: ec.mono.contador,
                    equipo_new_count_color: ec.color.contador,
                });
            });
            return counts;
        },
        async guardarRecibo() {
            const confirm = await Swal.fire({
                title: 'GENERAR RECIBO',
                text: '¿Sus datos estan correctos?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Generar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd',
            });
            if (!confirm.isConfirmed) return;

            this.guardando = true;
            try {
                const payload = {
                    receipt: {
                        client_id: this.clientId,
                        rent_id: this.rentaSeleccionada.id,
                        type: 'renta',
                        description: this.descripcion,
                        observation: this.generarObservaciones(),
                        status: this.estatus,
                        payment: this.formaPago,
                        subtotal: this.totalPagar,
                        discount: this.descuentoMonto,
                        discount_concept: this.descuentoTipo,
                        received: this.cantidadRecibida,
                        total: this.totalFinal,
                        iva: this.iva,
                        rent_mm: this.rentaMes,
                        rent_yy: this.rentaAnio,
                        rent_periodo: this.periodoTexto,
                    },
                    detail: this.generarDetail(),
                    eq_new_counts: this.generarEqNewCounts(),
                };

                const res = await axios.post('/admin/rent-receipt/store', payload);
                if (res.data.ok) {
                    Swal.fire('Recibo Generado', `Nota #${res.data.receipt.folio} creada exitosamente.`, 'success');
                    this.$emit('created', res.data.receipt);
                    this.cerrar();
                } else {
                    Swal.fire('Error', res.data.message || 'Error al generar recibo', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error al generar recibo', 'error');
            } finally {
                this.guardando = false;
            }
        },
        cerrar() {
            this.show = false;
            this.$emit('closed');
        },
        formatNumber(num) {
            if (!num && num !== 0) return '0.00';
            return parseFloat(num).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    },
};
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-container {
    background: #fff;
    border-radius: 8px;
    width: 95%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    overflow: hidden;
}
.modal-header {
    border-radius: 8px 8px 0 0;
}
.modal-footer {
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
}
</style>
