<template>
    <div class="modal fade" tabindex="-1" :class="{'mostrar': showModal}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-file-text-o me-2"></i>Facturar Nota #{{ receiptData?.folio }}
                    </h5>
                    <button type="button" class="close text-white" @click="cerrar" :disabled="timbrando" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Loading -->
                    <div v-if="cargando" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 text-muted">Cargando datos...</p>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle me-1"></i> {{ error }}
                    </div>

                    <!-- Post-timbrado: Factura exitosa -->
                    <div v-else-if="timbradoExitoso" class="text-center py-4">
                        <div class="mb-3">
                            <i class="fa fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-success mb-3">Factura Timbrada Exitosamente</h4>
                        <div class="card border-success mx-auto" style="max-width: 400px;">
                            <div class="card-body">
                                <p class="mb-1"><strong>Serie-Folio:</strong> {{ resultadoTimbrado.serie }}-{{ resultadoTimbrado.folio }}</p>
                                <p class="mb-0 text-muted" style="font-size: 0.8rem; word-break: break-all;">
                                    <strong>UUID:</strong> {{ resultadoTimbrado.uuid }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-center gap-2">
                            <button class="btn btn-outline-primary" @click="descargar('xml')" :disabled="descargando">
                                <i class="fa fa-code me-1"></i> Descargar XML
                            </button>
                            <button class="btn btn-outline-danger" @click="descargar('pdf')" :disabled="descargando">
                                <i class="fa fa-file-pdf-o me-1"></i> Descargar PDF
                            </button>
                        </div>
                    </div>

                    <!-- Formulario de facturación -->
                    <div v-else-if="receiptData">
                        <!-- Info emisor -->
                        <div class="alert alert-light border mb-3 py-2">
                            <small class="text-muted">
                                <strong>Emisor:</strong> {{ emisorData.razon_social }} | RFC: {{ emisorData.rfc }} |
                                Timbres disponibles: <span :class="emisorData.timbres_disponibles > 0 ? 'text-success' : 'text-danger'">{{ emisorData.timbres_disponibles }}</span>
                            </small>
                        </div>

                        <!-- Sección Receptor -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fa fa-user me-1"></i> Datos del Receptor</strong>
                            </div>
                            <div class="card-body">
                                <!-- Perfiles fiscales guardados -->
                                <div v-if="receiptData.client_id" class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0">Perfiles fiscales guardados</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="abrirGestionPerfiles()">
                                            <i class="fa fa-cog me-1"></i> Gestionar perfiles
                                        </button>
                                    </div>
                                    <div v-if="perfilesFiscales.length > 0" class="list-group list-group-flush">
                                        <button v-for="perfil in perfilesFiscales" :key="perfil.id"
                                            type="button" class="list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center"
                                            @click="usarPerfil(perfil)"
                                            :class="{ 'active': perfilSeleccionado === perfil.id }">
                                            <div>
                                                <strong>{{ perfil.rfc }}</strong> - {{ perfil.razon_social }}
                                                <span v-if="perfil.is_default" class="badge bg-info ms-1">Default</span>
                                            </div>
                                            <i class="fa fa-check" v-if="perfilSeleccionado === perfil.id"></i>
                                        </button>
                                    </div>
                                    <div v-else class="text-muted small fst-italic">
                                        Este cliente aún no tiene perfiles fiscales guardados.
                                    </div>
                                </div>

                                <!-- Botón Público en General -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm"
                                        :class="esPublicoGeneral ? 'btn-success' : 'btn-outline-secondary'"
                                        @click="setPublicoGeneral">
                                        <i class="fa fa-users me-1"></i> Publico en General
                                    </button>
                                    <button v-if="perfilSeleccionado || esPublicoGeneral" type="button"
                                        class="btn btn-sm btn-outline-warning ms-1" @click="limpiarReceptor">
                                        <i class="fa fa-eraser me-1"></i> Limpiar
                                    </button>
                                </div>

                                <!-- Campos receptor -->
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">RFC *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="receptor.rfc"
                                            maxlength="13" @input="receptor.rfc = receptor.rfc.toUpperCase()"
                                            :disabled="esPublicoGeneral">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Razon Social *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="receptor.razon_social"
                                            :disabled="esPublicoGeneral">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Regimen Fiscal *</label>
                                        <select class="form-select form-select-sm" v-model="receptor.regimen_fiscal"
                                            :disabled="esPublicoGeneral">
                                            <option value="">Seleccionar...</option>
                                            <option v-for="r in catalogoRegimen" :key="r.clave" :value="r.clave">
                                                {{ r.clave }} - {{ r.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Uso CFDI *</label>
                                        <select class="form-select form-select-sm" v-model="receptor.uso_cfdi"
                                            :disabled="esPublicoGeneral">
                                            <option value="">Seleccionar...</option>
                                            <option v-for="u in usosFiltrados" :key="u.clave" :value="u.clave">
                                                {{ u.clave }} - {{ u.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Codigo Postal *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="receptor.codigo_postal"
                                            maxlength="5" :disabled="esPublicoGeneral">
                                    </div>
                                </div>

                                <!-- Checkbox guardar datos -->
                                <div class="form-check mt-2" v-if="receiptData.client_id && !esPublicoGeneral">
                                    <input class="form-check-input" type="checkbox" v-model="guardarDatosCliente" id="chkGuardar">
                                    <label class="form-check-label" for="chkGuardar">
                                        <small>Guardar datos fiscales para este cliente</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Aviso de ítems de cortesía excluidos -->
                        <div v-if="itemsCortesiaExcluidos.length > 0" class="alert alert-warning py-2 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fa fa-gift me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <strong>{{ itemsCortesiaExcluidos.length }} ítem(s) de cortesía no se incluirán en la factura</strong>
                                    <ul class="mb-0 mt-1 small">
                                        <li v-for="item in itemsCortesiaExcluidos" :key="'cort-' + item.id">
                                            {{ item.descripcion }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Aviso de descuento global prorrateado -->
                        <div v-if="prorrateo.factor > 0" class="alert alert-info py-2 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fa fa-percent me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <strong>Descuento global: ${{ formatNumber(descuentoDisplay) }}</strong>
                                    distribuido entre {{ prorrateo.facturables.length }} concepto(s)
                                    <br>
                                    <small class="text-muted">
                                        El SAT exige que el descuento se registre por concepto. El total a timbrar es exactamente
                                        <strong>${{ formatNumber(totalDisplay) }}</strong>.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Conceptos -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fa fa-list me-1"></i> Conceptos</strong>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Descripcion</th>
                                                <th style="min-width: 160px;">Clave SAT</th>
                                                <th style="min-width: 130px;">Unidad</th>
                                                <th class="text-center">Cant.</th>
                                                <th class="text-end">P. Unit.</th>
                                                <th class="text-end">Importe</th>
                                                <th v-if="prorrateo.factor > 0" class="text-end text-danger">Descuento</th>
                                                <th v-if="algunaRetencionActiva" class="text-center" style="width: 70px;" title="Marca los conceptos a los que se aplican las retenciones globales">
                                                    <i class="fa fa-minus-circle text-warning"></i> Ret.
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in conceptosSat" :key="item.detail_id">
                                                <td>
                                                    <div v-if="!item.editingDesc" class="d-flex align-items-center">
                                                        <span>{{ item.descripcion }}</span>
                                                        <i class="fa fa-pencil ms-2 text-muted" style="cursor: pointer; font-size: 0.75rem;"
                                                            @click="item.editingDesc = true" title="Editar descripción"></i>
                                                    </div>
                                                    <input v-else type="text" class="form-control form-control-sm"
                                                        v-model="item.descripcion"
                                                        @blur="item.editingDesc = false"
                                                        @keyup.enter="item.editingDesc = false"
                                                        v-focus
                                                        style="font-size: 0.85rem; min-width: 180px;">
                                                </td>
                                                <td>
                                                    <div class="position-relative">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control form-control-sm"
                                                                v-model="item.productSearch"
                                                                placeholder="Buscar..."
                                                                @input="buscarSatProduct(idx)"
                                                                @focus="handleSatFocus($event, idx, 'product')"
                                                                @blur="handleSatBlur(idx, 'product')"
                                                                style="font-size: 0.75rem;">
                                                            <button v-if="item.productSearch"
                                                                class="btn btn-outline-secondary btn-sm px-2"
                                                                type="button"
                                                                @mousedown.prevent="clearSatProduct(idx)"
                                                                title="Limpiar">
                                                                <i class="fa fa-times" style="font-size: 0.7rem;"></i>
                                                            </button>
                                                        </div>
                                                        <small v-if="item.clave_prod_serv === '01010101'" class="text-warning d-block mt-1" style="font-size: 0.7rem;">
                                                            <i class="fa fa-exclamation-triangle me-1"></i>Codigo generico
                                                        </small>
                                                        <ul v-if="item.showProductResults && satProductResults.length > 0 && activeSearchIdx === idx"
                                                            class="list-group position-absolute w-100" style="z-index: 1060; max-height: 180px; overflow-y: auto;">
                                                            <li v-for="r in satProductResults" :key="r.code"
                                                                class="list-group-item list-group-item-action py-1 px-2"
                                                                style="cursor: pointer; font-size: 0.75rem;"
                                                                @mousedown.prevent="selectSatProduct(idx, r)">
                                                                <strong>{{ r.code }}</strong> — {{ r.description }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="position-relative">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control form-control-sm"
                                                                v-model="item.unitSearch"
                                                                placeholder="Buscar..."
                                                                @input="buscarSatUnit(idx)"
                                                                @focus="handleSatFocus($event, idx, 'unit')"
                                                                @blur="handleSatBlur(idx, 'unit')"
                                                                style="font-size: 0.75rem;">
                                                            <button v-if="item.unitSearch"
                                                                class="btn btn-outline-secondary btn-sm px-2"
                                                                type="button"
                                                                @mousedown.prevent="clearSatUnit(idx)"
                                                                title="Limpiar">
                                                                <i class="fa fa-times" style="font-size: 0.7rem;"></i>
                                                            </button>
                                                        </div>
                                                        <ul v-if="item.showUnitResults && satUnitResults.length > 0 && activeSearchIdx === idx"
                                                            class="list-group position-absolute w-100" style="z-index: 1060; max-height: 180px; overflow-y: auto;">
                                                            <li v-for="r in satUnitResults" :key="r.code"
                                                                class="list-group-item list-group-item-action py-1 px-2"
                                                                style="cursor: pointer; font-size: 0.75rem;"
                                                                @mousedown.prevent="selectSatUnit(idx, r)">
                                                                <strong>{{ r.code }}</strong> — {{ r.name }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ item.qty }}</td>
                                                <td class="text-end">${{ formatNumber(item.precio) }}</td>
                                                <td class="text-end">${{ formatNumber(item.subtotal) }}</td>
                                                <td v-if="prorrateo.factor > 0" class="text-end text-danger">
                                                    −${{ formatNumber(prorrateo.facturables[idx]?.descuento || 0) }}
                                                </td>
                                                <td v-if="algunaRetencionActiva" class="text-center">
                                                    <input type="checkbox" class="form-check-input" v-model="item.aplicaRet">
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td :colspan="colspanFooter" class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end">${{ formatNumber(subtotalDisplay) }}</td>
                                            </tr>
                                            <tr v-if="prorrateo.factor > 0">
                                                <td :colspan="colspanFooter" class="text-end text-danger">
                                                    <strong>Descuento prorrateado:</strong>
                                                </td>
                                                <td class="text-end text-danger">−${{ formatNumber(descuentoDisplay) }}</td>
                                            </tr>
                                            <tr>
                                                <td :colspan="colspanFooter" class="text-end"><strong>{{ $shopTaxName || 'IVA' }} ({{ $shopTaxRate }}%):</strong></td>
                                                <td class="text-end">${{ formatNumber(ivaDisplay) }}</td>
                                            </tr>
                                            <tr v-if="retIsrAplica && prorrateo.retIsr > 0">
                                                <td :colspan="colspanFooter" class="text-end text-warning">
                                                    <strong>Retención ISR ({{ retIsrTasa }}%):</strong>
                                                </td>
                                                <td class="text-end text-warning">−${{ formatNumber(retIsrDisplay) }}</td>
                                            </tr>
                                            <tr v-if="retIvaAplica && prorrateo.retIva > 0">
                                                <td :colspan="colspanFooter" class="text-end text-warning">
                                                    <strong>Retención IVA ({{ retIvaTasa }}%):</strong>
                                                </td>
                                                <td class="text-end text-warning">−${{ formatNumber(retIvaDisplay) }}</td>
                                            </tr>
                                            <tr>
                                                <td :colspan="colspanFooter" class="text-end"><strong>Total CFDI:</strong></td>
                                                <td class="text-end"><strong>${{ formatNumber(totalDisplay) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Retenciones -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                <strong><i class="fa fa-minus-circle me-1 text-warning"></i> Retenciones</strong>
                                <small class="text-muted">Marca los conceptos en la tabla a los que aplica</small>
                            </div>
                            <div class="card-body py-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch retencion-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="chkRetIsr" v-model="retIsrAplica">
                                            <label class="form-check-label" for="chkRetIsr">
                                                <strong>Retención ISR</strong>
                                            </label>
                                        </div>
                                        <div class="input-group input-group-sm" v-if="retIsrAplica">
                                            <input type="number" class="form-control text-end" v-model.number="retIsrTasa" step="0.0001" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <small v-if="retIsrAplica" class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                            Tasas comunes — Honorarios/arrendamiento: <strong>10%</strong> · RESICO PF: <strong>1.25%</strong>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch retencion-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="chkRetIva" v-model="retIvaAplica">
                                            <label class="form-check-label" for="chkRetIva">
                                                <strong>Retención IVA</strong>
                                            </label>
                                        </div>
                                        <div class="input-group input-group-sm" v-if="retIvaAplica">
                                            <input type="number" class="form-control text-end" v-model.number="retIvaTasa" step="0.0001" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <small v-if="retIvaAplica" class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                            Tasas comunes — Honorarios/arrendamiento: <strong>10.6667%</strong> · Fletes: <strong>4%</strong>
                                        </small>
                                    </div>
                                </div>
                                <div v-if="warningsRetencion.length > 0" class="alert alert-warning py-2 mt-3 mb-0">
                                    <i class="fa fa-exclamation-triangle me-1"></i>
                                    <strong>Atención:</strong>
                                    <ul class="mb-0 mt-1 small">
                                        <li v-for="(w, i) in warningsRetencion" :key="i">{{ w }}</li>
                                    </ul>
                                </div>
                                <div v-if="algunaRetencionActiva && retTotalDisplay > 0" class="alert alert-info py-2 mt-3 mb-0 small">
                                    <i class="fa fa-info-circle me-1"></i>
                                    El receptor te transferirá <strong>${{ formatNumber(totalDisplay) }}</strong>
                                    y entregará al SAT en tu nombre <strong>${{ formatNumber(retTotalDisplay) }}</strong>
                                    (que tu contador acreditará).
                                    El saldo comercial del cliente sigue siendo
                                    <strong>${{ formatNumber(receiptData?.total || 0) }}</strong>.
                                </div>
                            </div>
                        </div>

                        <!-- Pago -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fa fa-credit-card me-1"></i> Datos de Pago</strong>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Forma de Pago *</label>
                                        <select class="form-select form-select-sm" v-model="formaPago" :disabled="metodoPago === 'PPD'">
                                            <option v-for="fp in catalogoFormaPago" :key="fp.clave" :value="fp.clave">
                                                {{ fp.clave }} - {{ fp.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Metodo de Pago</label>
                                        <input type="text" class="form-control form-control-sm" :value="metodoPagoLabel" readonly>
                                    </div>
                                </div>
                                <div v-if="metodoPago === 'PPD'" class="alert alert-warning mt-3 mb-0 py-2 small">
                                    <i class="fa fa-info-circle me-1"></i>
                                    <strong>Nota a credito (PPD).</strong>
                                    Esta nota tiene saldo pendiente, por lo que se factura como Pago en Parcialidades.
                                    La forma de pago queda fijada en <strong>99 - Por definir</strong> y cada abono que registres
                                    despues generara automaticamente su complemento de pago al SAT.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button v-if="timbradoExitoso" type="button" class="btn btn-secondary" @click="cerrar">
                        Cerrar
                    </button>
                    <template v-else>
                        <button type="button" class="btn btn-secondary" @click="cerrar" :disabled="timbrando">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" @click="timbrarFactura"
                            :disabled="timbrando || !formValido || cargando">
                            <span v-if="timbrando">
                                <span class="spinner-border spinner-border-sm me-1"></span> Timbrando...
                            </span>
                            <span v-else>
                                <i class="fa fa-file-text-o me-1"></i> Timbrar Factura
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal de gestión de perfiles fiscales (selección desde aquí) -->
        <client-fiscal-data-modal ref="fiscalDataMgmt"
            @seleccionado="onPerfilSeleccionado"
            @closed="onGestionCerrada"></client-fiscal-data-modal>

        <!-- Diálogo de abonos previos al timbrado PPD -->
        <abonos-previos-dialog ref="abonosPreviosDialog"
            :abonos="abonosPreviosPendientes"
            :cuentas-bancarias="cuentasBancariasShop"
            @submitted="onAbonosPreviosSubmitted"
            @cancelled="onAbonosPreviosCancelled"></abonos-previos-dialog>
    </div>
</template>

<script>
import ClientFiscalDataModal from '../shops/ClientFiscalDataModal.vue';
import AbonosPreviosDialog from './AbonosPreviosDialog.vue';

export default {
    name: 'CfdiInvoiceModal',
    components: { ClientFiscalDataModal, AbonosPreviosDialog },
    directives: {
        focus: { mounted(el) { el.focus(); } },
    },
    props: {
        receiptId: { type: Number, default: null },
    },
    emits: ['closed', 'invoiced'],
    data() {
        return {
            showModal: false,
            cargando: false,
            timbrando: false,
            descargando: false,
            error: null,
            receiptData: null,
            emisorData: null,
            perfilesFiscales: [],
            perfilSeleccionado: null,
            esPublicoGeneral: false,
            guardarDatosCliente: true,
            timbradoExitoso: false,
            resultadoTimbrado: null,
            receptor: {
                rfc: '',
                razon_social: '',
                regimen_fiscal: '',
                uso_cfdi: 'G03',
                codigo_postal: '',
            },
            formaPago: '01',
            metodoPago: 'PUE',
            conceptosSat: [],
            // Retenciones — tasas en porcentaje (UI). Se convierten a decimal al enviar.
            retIsrAplica: false,
            retIsrTasa: 10.0000,
            retIvaAplica: false,
            retIvaTasa: 10.6667,
            // Diálogo de abonos previos al timbrado PPD
            abonosPreviosPendientes: [],
            cuentasBancariasShop: [],
            abonosPreviosPayload: null,
            timbradoEnEspera: false,
            satProductResults: [],
            satUnitResults: [],
            activeSearchIdx: null,
            satSearchTimer: null,
            catalogoRegimen: [
                { clave: '601', nombre: 'General de Ley PM' },
                { clave: '603', nombre: 'PM Fines no Lucrativos' },
                { clave: '605', nombre: 'Sueldos y Salarios e Ingresos Asimilados a Salarios' },
                { clave: '606', nombre: 'Arrendamiento' },
                { clave: '607', nombre: 'Enajenacion o Adquisicion de Bienes' },
                { clave: '608', nombre: 'Demas ingresos' },
                { clave: '610', nombre: 'Residentes en el Extranjero sin EP en Mexico' },
                { clave: '611', nombre: 'Ingresos por Dividendos' },
                { clave: '612', nombre: 'PF con Actividades Empresariales y Profesionales' },
                { clave: '614', nombre: 'Ingresos por intereses' },
                { clave: '615', nombre: 'Ingresos por Obtencion de Premios' },
                { clave: '616', nombre: 'Sin obligaciones fiscales' },
                { clave: '620', nombre: 'Sociedades Cooperativas de Produccion' },
                { clave: '621', nombre: 'Incorporacion Fiscal' },
                { clave: '622', nombre: 'Actividades Agricolas, Ganaderas, Silvicolas y Pesqueras' },
                { clave: '623', nombre: 'Opcional para Grupos de Sociedades' },
                { clave: '624', nombre: 'Coordinados' },
                { clave: '625', nombre: 'Plataformas Tecnologicas' },
                { clave: '626', nombre: 'RESICO' },
            ],
            catalogoUsoCfdi: [
                { clave: 'G01', nombre: 'Adquisicion de mercancias' },
                { clave: 'G02', nombre: 'Devoluciones, descuentos o bonificaciones' },
                { clave: 'G03', nombre: 'Gastos en general' },
                { clave: 'I01', nombre: 'Construcciones' },
                { clave: 'I02', nombre: 'Mobiliario y equipo de oficina por inversiones' },
                { clave: 'I03', nombre: 'Equipo de transporte' },
                { clave: 'I04', nombre: 'Equipo de computo y accesorios' },
                { clave: 'I05', nombre: 'Dados, troqueles, moldes, matrices y herramental' },
                { clave: 'I06', nombre: 'Comunicaciones telefonicas' },
                { clave: 'I07', nombre: 'Comunicaciones satelitales' },
                { clave: 'I08', nombre: 'Otra maquinaria y equipo' },
                { clave: 'D01', nombre: 'Honorarios medicos, dentales y gastos hospitalarios' },
                { clave: 'D02', nombre: 'Gastos medicos por incapacidad o discapacidad' },
                { clave: 'D03', nombre: 'Gastos funerales' },
                { clave: 'D04', nombre: 'Donativos' },
                { clave: 'D05', nombre: 'Intereses reales pagados por creditos hipotecarios' },
                { clave: 'D06', nombre: 'Aportaciones voluntarias al SAR' },
                { clave: 'D07', nombre: 'Primas por seguros de gastos medicos' },
                { clave: 'D08', nombre: 'Gastos de transportacion escolar obligatoria' },
                { clave: 'D09', nombre: 'Depositos en cuentas para el ahorro / planes de pensiones' },
                { clave: 'D10', nombre: 'Pagos por servicios educativos (colegiaturas)' },
                { clave: 'S01', nombre: 'Sin efectos fiscales' },
                { clave: 'CP01', nombre: 'Pagos' },
            ],
            matrizUsosPorRegimen: {
                '601': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '603': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '605': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '606': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '607': ['CP01','S01'],
                '608': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '610': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','S01','CP01'],
                '611': ['CP01','S01'],
                '612': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '614': ['CP01','S01'],
                '615': ['CP01','S01'],
                '616': ['CP01','S01'],
                '620': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '621': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '622': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '623': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '624': ['CP01','S01'],
                '625': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '626': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
            },
            catalogoFormaPago: [
                { clave: '01', nombre: 'Efectivo' },
                { clave: '02', nombre: 'Cheque nominativo' },
                { clave: '03', nombre: 'Transferencia electronica' },
                { clave: '04', nombre: 'Tarjeta de credito' },
                { clave: '28', nombre: 'Tarjeta de debito' },
                { clave: '99', nombre: 'Por definir' },
            ],
        };
    },
    computed: {
        metodoPagoLabel() {
            return this.metodoPago === 'PPD'
                ? 'PPD - Pago en Parcialidades o Diferido'
                : 'PUE - Pago en Una sola Exhibicion';
        },
        usosFiltrados() {
            const reg = this.receptor.regimen_fiscal;
            const compatibles = this.matrizUsosPorRegimen[reg];
            if (!compatibles) return this.catalogoUsoCfdi;
            return this.catalogoUsoCfdi.filter(u => compatibles.includes(u.clave));
        },
        formValido() {
            return this.receptor.rfc &&
                   this.receptor.razon_social &&
                   this.receptor.regimen_fiscal &&
                   this.receptor.uso_cfdi &&
                   this.receptor.codigo_postal &&
                   this.formaPago &&
                   this.metodoPago;
        },
        itemsCortesiaExcluidos() {
            if (!this.receiptData) return [];
            return this.receiptData.detail.filter(item => item.is_complimentary);
        },
        // Prorrateo del descuento global (replica lógica del backend, mismo algoritmo)
        prorrateo() {
            if (!this.receiptData) {
                return { facturables: [], subtotal: 0, descuento: 0, baseIva: 0, iva: 0, total: 0, factor: 0 };
            }
            const tieneIva = this.receiptData.iva > 0;
            const divisor = this.$taxDivisor;
            const decimal = divisor - 1;

            // Lookup de aplica_retencion por detail_id desde conceptosSat (marcable por usuario)
            const aplicaRetById = {};
            this.conceptosSat.forEach(c => { aplicaRetById[c.detail_id] = !!c.aplicaRet; });

            // Pre-calcular items facturables (sin cortesías) con valores brutos sin IVA
            const facturables = this.receiptData.detail
                .filter(it => !it.is_complimentary)
                .map(it => {
                    const valorUnitario = tieneIva
                        ? Math.round(it.price * 100) / 100
                        : Math.round(it.price / divisor * 100) / 100;
                    const importeBruto = Math.round(valorUnitario * it.qty * 100) / 100;
                    return {
                        detail_id: it.id,
                        valor_unitario: valorUnitario,
                        importe: importeBruto,
                        descuento: 0,
                        base: importeBruto,
                        iva: 0,
                        aplicaRet: aplicaRetById[it.id] || false,
                        retIsr: 0,
                        retIva: 0,
                    };
                })
                .filter(f => f.importe > 0);

            const subtotal = facturables.reduce((s, f) => s + f.importe, 0);

            // Calcular monto descuento global en unidad "sin IVA"
            let descuentoGlobal = 0;
            if (this.receiptData.discount > 0 && subtotal > 0) {
                const raw = this.receiptData.discount_concept === '%'
                    ? this.receiptData.subtotal * this.receiptData.discount / 100
                    : parseFloat(this.receiptData.discount);
                descuentoGlobal = tieneIva
                    ? Math.round(raw * 100) / 100
                    : Math.round(raw / divisor * 100) / 100;
            }

            const factor = descuentoGlobal > 0 ? descuentoGlobal / subtotal : 0;

            // Prorratear
            facturables.forEach(f => {
                f.descuento = factor > 0 ? Math.round(f.importe * factor * 100) / 100 : 0;
            });

            // Ajuste de redondeo al último concepto
            if (factor > 0 && facturables.length > 0) {
                const suma = facturables.reduce((s, f) => s + f.descuento, 0);
                const diff = Math.round((descuentoGlobal - suma) * 100) / 100;
                if (Math.abs(diff) >= 0.01) {
                    const last = facturables[facturables.length - 1];
                    last.descuento = Math.round((last.descuento + diff) * 100) / 100;
                }
            }

            // Calcular base, IVA y retenciones por concepto.
            // Retenciones se aplican solo si el concepto está marcado (f.aplicaRet) Y la tasa global está activa.
            // Tasas en data() se almacenan en porcentaje; se convierten a decimal aquí.
            const retIsrDec = this.retIsrAplica && this.retIsrTasa > 0
                ? parseFloat(this.retIsrTasa) / 100
                : 0;
            const retIvaDec = this.retIvaAplica && this.retIvaTasa > 0
                ? parseFloat(this.retIvaTasa) / 100
                : 0;

            let descTotal = 0, ivaTotal = 0, retIsrTotal = 0, retIvaTotal = 0;
            facturables.forEach(f => {
                f.base = Math.round((f.importe - f.descuento) * 100) / 100;
                f.iva = Math.round(f.base * decimal * 100) / 100;
                f.retIsr = 0;
                f.retIva = 0;
                if (f.aplicaRet) {
                    if (retIsrDec > 0) {
                        const r = Math.round(f.base * retIsrDec * 100) / 100;
                        if (r >= 0.01) f.retIsr = r;
                    }
                    // IVA retenido solo si IVA trasladado > 0 (regla SAT)
                    if (retIvaDec > 0 && f.iva > 0) {
                        const r = Math.round(f.base * retIvaDec * 100) / 100;
                        if (r >= 0.01) f.retIva = r;
                    }
                }
                descTotal += f.descuento;
                ivaTotal += f.iva;
                retIsrTotal += f.retIsr;
                retIvaTotal += f.retIva;
            });

            const subtotalR = Math.round(subtotal * 100) / 100;
            const descR = Math.round(descTotal * 100) / 100;
            const baseIvaR = Math.round((subtotalR - descR) * 100) / 100;
            const ivaR = Math.round(ivaTotal * 100) / 100;
            const retIsrR = Math.round(retIsrTotal * 100) / 100;
            const retIvaR = Math.round(retIvaTotal * 100) / 100;
            const retTotalR = Math.round((retIsrR + retIvaR) * 100) / 100;
            const totalR = Math.round((baseIvaR + ivaR - retTotalR) * 100) / 100;

            return {
                facturables,
                subtotal: subtotalR,
                descuento: descR,
                baseIva: baseIvaR,
                iva: ivaR,
                retIsr: retIsrR,
                retIva: retIvaR,
                retTotal: retTotalR,
                total: totalR,
                factor,
            };
        },
        subtotalDisplay() { return this.prorrateo.subtotal; },
        descuentoDisplay() { return this.prorrateo.descuento; },
        ivaDisplay() { return this.prorrateo.iva; },
        retIsrDisplay() { return this.prorrateo.retIsr; },
        retIvaDisplay() { return this.prorrateo.retIva; },
        retTotalDisplay() { return this.prorrateo.retTotal; },
        totalDisplay() { return this.prorrateo.total; },
        // True si hay al menos una retención global activa con tasa > 0
        algunaRetencionActiva() {
            return (this.retIsrAplica && this.retIsrTasa > 0) ||
                   (this.retIvaAplica && this.retIvaTasa > 0);
        },
        // Colspan del tfoot: 5 cols base + descuento (1) + retención (1)
        colspanFooter() {
            return 5 + (this.prorrateo.factor > 0 ? 1 : 0) + (this.algunaRetencionActiva ? 1 : 0);
        },
        // Warnings suaves (regímenes/tasas inusuales). Vacío si todo OK.
        warningsRetencion() {
            const w = [];
            if (!this.algunaRetencionActiva) return w;
            const reg = this.receptor.regimen_fiscal;
            // Persona física como receptor + retención (típicamente PF→PM, no al revés)
            if (['612', '605', '606', '607', '608', '611'].includes(reg)) {
                w.push('Receptor es persona física. Las retenciones típicamente fluyen PF→PM, no al revés.');
            }
            // Receptor RESICO PM + retención IVA
            if (reg === '626' && this.retIvaAplica) {
                w.push('Receptor RESICO. La retención de IVA es inusual con este régimen.');
            }
            // Tasas fuera de rangos comunes
            if (this.retIsrAplica && this.retIsrTasa > 30) {
                w.push(`Retención ISR del ${this.retIsrTasa}% es inusual. Confirma con tu contador.`);
            }
            if (this.retIvaAplica && this.retIvaTasa > 16) {
                w.push(`Retención IVA del ${this.retIvaTasa}% es inusual. Confirma con tu contador.`);
            }
            return w;
        },
    },
    watch: {
        receiptId(newVal) {
            if (newVal) {
                this.abrir();
            }
        },
        'receptor.regimen_fiscal'(nuevoRegimen) {
            const compatibles = this.matrizUsosPorRegimen[nuevoRegimen];
            if (compatibles && this.receptor.uso_cfdi && !compatibles.includes(this.receptor.uso_cfdi)) {
                this.receptor.uso_cfdi = '';
            }
        },
    },
    methods: {
        abrir() {
            this.resetForm();
            this.showModal = true;
            this.cargarDatos();
        },
        cerrar() {
            if (this.timbradoExitoso) {
                this.$emit('invoiced');
            } else {
                this.$emit('closed');
            }
            this.showModal = false;
        },
        resetForm() {
            this.cargando = false;
            this.timbrando = false;
            this.error = null;
            this.receiptData = null;
            this.emisorData = null;
            this.perfilesFiscales = [];
            this.perfilSeleccionado = null;
            this.esPublicoGeneral = false;
            this.guardarDatosCliente = true;
            this.timbradoExitoso = false;
            this.resultadoTimbrado = null;
            this.abonosPreviosPendientes = [];
            this.abonosPreviosPayload = null;
            this.timbradoEnEspera = false;
            this.receptor = { rfc: '', razon_social: '', regimen_fiscal: '', uso_cfdi: 'G03', codigo_postal: '' };
            this.formaPago = '01';
            this.metodoPago = 'PUE';
            this.conceptosSat = [];
            this.retIsrAplica = false;
            this.retIsrTasa = 10.0000;
            this.retIvaAplica = false;
            this.retIvaTasa = 10.6667;
            this.satProductResults = [];
            this.satUnitResults = [];
            this.activeSearchIdx = null;
        },
        async cargarDatos() {
            this.cargando = true;
            this.error = null;
            try {
                const res = await axios.get(`/admin/facturacion/receipt/${this.receiptId}/data`);
                if (res.data.ok) {
                    this.receiptData = res.data.receipt;
                    this.emisorData = res.data.emisor;
                    this.metodoPago = res.data.metodo_pago_calculado || 'PUE';
                    this.perfilesFiscales = res.data.receipt.client?.fiscal_data || [];
                    // Prellenar retenciones con defaults del emisor (tasas en backend son decimales)
                    this.retIsrAplica = !!res.data.emisor.ret_isr_default_aplica;
                    if (res.data.emisor.ret_isr_default_tasa) {
                        this.retIsrTasa = parseFloat(res.data.emisor.ret_isr_default_tasa) * 100;
                    }
                    this.retIvaAplica = !!res.data.emisor.ret_iva_default_aplica;
                    if (res.data.emisor.ret_iva_default_tasa) {
                        this.retIvaTasa = parseFloat(res.data.emisor.ret_iva_default_tasa) * 100;
                    }
                    this.initConceptosSat();

                    const defaultPerfil = this.perfilesFiscales.find(p => p.is_default);
                    if (defaultPerfil) {
                        this.usarPerfil(defaultPerfil);
                    }

                    this.mapearFormaPago(this.receiptData.payment);
                    if (this.metodoPago === 'PPD') {
                        this.formaPago = '99';
                    }
                } else {
                    this.error = res.data.message;
                }
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al cargar datos';
            } finally {
                this.cargando = false;
            }
        },
        mapearFormaPago(payment) {
            if (this.metodoPago === 'PPD') {
                this.formaPago = '99';
                return;
            }
            const map = {
                'EFECTIVO': '01',
                'TRANSFERENCIA': '03',
                'TARJETA': '04',
                'CHEQUE': '02',
            };
            this.formaPago = map[payment] || '01';
        },
        usarPerfil(perfil) {
            this.perfilSeleccionado = perfil.id;
            this.esPublicoGeneral = false;
            this.receptor = {
                rfc: perfil.rfc,
                razon_social: perfil.razon_social,
                regimen_fiscal: perfil.regimen_fiscal,
                uso_cfdi: perfil.uso_cfdi,
                codigo_postal: perfil.codigo_postal,
            };
        },
        setPublicoGeneral() {
            this.esPublicoGeneral = true;
            this.perfilSeleccionado = null;
            this.receptor = {
                rfc: 'XAXX010101000',
                razon_social: 'PUBLICO EN GENERAL',
                regimen_fiscal: '616',
                uso_cfdi: 'S01',
                codigo_postal: this.emisorData?.codigo_postal || '',
            };
        },
        limpiarReceptor() {
            this.perfilSeleccionado = null;
            this.esPublicoGeneral = false;
            this.receptor = { rfc: '', razon_social: '', regimen_fiscal: '', uso_cfdi: 'G03', codigo_postal: '' };
        },
        abrirGestionPerfiles() {
            const cliente = this.receiptData?.client || { id: this.receiptData?.client_id, name: this.receiptData?.client_name };
            if (!cliente?.id) return;
            this.$refs.fiscalDataMgmt.abrirModal(cliente, true);
        },
        onPerfilSeleccionado(perfil) {
            this.usarPerfil(perfil);
            this.recargarPerfiles();
        },
        onGestionCerrada() {
            this.recargarPerfiles();
        },
        async recargarPerfiles() {
            if (!this.receiptData?.client_id) return;
            try {
                const res = await axios.get(`/admin/clients/${this.receiptData.client_id}/fiscal-data`);
                this.perfilesFiscales = res.data || [];
            } catch (e) {
                // silencioso: el modal de gestión ya muestra sus propios errores
            }
        },
        async timbrarFactura() {
            const confirm = await Swal.fire({
                title: 'Timbrar Factura',
                html: `<p>Se generara la factura CFDI para la nota <strong>#${this.receiptData.folio}</strong>.</p>
                       <p><strong>Receptor:</strong> ${this.receptor.rfc} - ${this.receptor.razon_social}</p>
                       <p><strong>Total:</strong> $${this.formatNumber(this.totalDisplay)}</p>
                       <p class="text-warning"><small>Esta accion consume 1 timbre y no se puede deshacer.</small></p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, Timbrar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd',
            });

            if (!confirm.isConfirmed) return;

            // Si va a ser PPD y hay abonos previos sin forma SAT, abrir diálogo de decisión
            if (this.metodoPago === 'PPD') {
                try {
                    const r = await axios.get(`/admin/facturacion/receipt/${this.receiptData.id}/abonos-previos-pendientes`);
                    if (r.data.ok && r.data.requiere_decision) {
                        this.abonosPreviosPendientes = r.data.abonos;
                        // Cargar cuentas bancarias activas para el bloque bancarizado del diálogo
                        try {
                            const rc = await axios.get('/admin/configuracion/cuentas-bancarias/data');
                            this.cuentasBancariasShop = (rc.data.accounts || []).filter(c => c.is_active);
                        } catch (e) { this.cuentasBancariasShop = []; }
                        this.timbradoEnEspera = true;
                        this.$refs.abonosPreviosDialog.abrir();
                        return; // El flujo continúa cuando el diálogo emite 'submitted'
                    }
                } catch (e) {
                    Swal.fire('Error', e.response?.data?.message || 'No se pudo verificar abonos previos.', 'error');
                    return;
                }
            }

            await this._ejecutarTimbrado();
        },

        async onAbonosPreviosSubmitted(payload) {
            this.abonosPreviosPayload = payload;
            this.timbradoEnEspera = false;
            await this._ejecutarTimbrado();
        },

        onAbonosPreviosCancelled() {
            this.timbradoEnEspera = false;
            this.abonosPreviosPayload = null;
        },

        async _ejecutarTimbrado() {

            // Bloquear toda la pantalla con loader mientras se hace la petición al PAC
            Swal.fire({
                title: 'Timbrando factura...',
                html: '<p class="mb-1">Conectando con el SAT a través del PAC.</p>' +
                      '<p class="mb-0 small text-muted">Por favor no cierres esta ventana.</p>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
            });

            this.timbrando = true;
            try {
                const payloadTimbrar = {
                    receipt_id: this.receiptData.id,
                    receptor_rfc: this.receptor.rfc,
                    receptor_razon_social: this.receptor.razon_social,
                    receptor_regimen_fiscal: this.receptor.regimen_fiscal,
                    receptor_uso_cfdi: this.receptor.uso_cfdi,
                    receptor_codigo_postal: this.receptor.codigo_postal,
                    forma_pago: this.formaPago,
                    metodo_pago: this.metodoPago,
                    guardar_datos_cliente: this.guardarDatosCliente,
                    client_fiscal_data_id: this.perfilSeleccionado || null,
                    conceptos_sat: this.conceptosSat.map(c => ({
                        detail_id: c.detail_id,
                        clave_prod_serv: c.clave_prod_serv,
                        clave_unidad: c.clave_unidad,
                        descripcion: c.descripcion,
                        aplica_retencion: !!c.aplicaRet,
                    })),
                    // Retenciones globales (decimal). Tasas en data() están en porcentaje.
                    ret_isr_aplica: this.retIsrAplica,
                    ret_isr_tasa: this.retIsrAplica ? parseFloat(this.retIsrTasa) / 100 : 0,
                    ret_iva_aplica: this.retIvaAplica,
                    ret_iva_tasa: this.retIvaAplica ? parseFloat(this.retIvaTasa) / 100 : 0,
                };
                if (this.abonosPreviosPayload) {
                    payloadTimbrar.abonos_previos = this.abonosPreviosPayload;
                }
                const res = await axios.post('/admin/facturacion/timbrar', payloadTimbrar);

                Swal.close();

                if (res.data.ok) {
                    this.timbradoExitoso = true;
                    this.resultadoTimbrado = res.data;
                } else {
                    Swal.fire('Error', res.data.message || 'Error al timbrar', 'error');
                }
            } catch (e) {
                Swal.close();
                // Salvaguarda: si el backend devuelve 422 con requiere_decision=true,
                // significa que la nota PPD tiene abonos previos sin forma SAT.
                // En vez de mostrar error, abrimos el diálogo (cubre caso de cache de navegador
                // o de un futuro cliente que no llamó al GET previo).
                const data = e.response?.data;
                if (e.response?.status === 422 && data?.requiere_decision && Array.isArray(data?.abonos_pendientes)) {
                    this.abonosPreviosPendientes = data.abonos_pendientes.map(a => ({ ...a, payment_method: '99' }));
                    try {
                        const rc = await axios.get('/admin/configuracion/cuentas-bancarias/data');
                        this.cuentasBancariasShop = (rc.data.accounts || []).filter(c => c.is_active);
                    } catch (ee) { this.cuentasBancariasShop = []; }
                    this.timbradoEnEspera = true;
                    this.$refs.abonosPreviosDialog.abrir();
                    return;
                }
                Swal.fire('Error', data?.message || 'Error al timbrar factura', 'error');
            } finally {
                this.timbrando = false;
            }
        },
        async descargar(formato) {
            this.descargando = true;
            try {
                const response = await axios.get(
                    `/admin/facturacion/descargar/${this.resultadoTimbrado.invoice_id}/${formato}`,
                    { responseType: 'blob' }
                );

                if (response.headers['content-type']?.includes('application/json')) {
                    const text = await response.data.text();
                    const json = JSON.parse(text);
                    if (json.url) {
                        window.open(json.url, '_blank');
                    } else if (!json.ok) {
                        Swal.fire('Error', json.message, 'error');
                    }
                    return;
                }

                const url = window.URL.createObjectURL(response.data);
                const a = document.createElement('a');
                a.href = url;
                a.download = `factura_${this.resultadoTimbrado.serie}${this.resultadoTimbrado.folio}.${formato}`;
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                Swal.fire('Error', 'No se pudo descargar el archivo', 'error');
            } finally {
                this.descargando = false;
            }
        },
        initConceptosSat() {
            if (!this.receiptData) return;
            // Índice por detail_id del prorrateo para jalar importe/descuento/base correctos
            const byId = {};
            this.prorrateo.facturables.forEach(f => { byId[f.detail_id] = f; });

            this.conceptosSat = this.receiptData.detail
                .filter(item => !item.is_complimentary)
                .map(item => {
                    const claveProd = item.product?.sat_product_code || '01010101';
                    const claveUnidad = item.product?.sat_unit_code || 'E48';
                    const f = byId[item.id] || { valor_unitario: 0, importe: 0, descuento: 0, base: 0 };
                    return {
                        detail_id: item.id,
                        descripcion: item.descripcion,
                        qty: item.qty,
                        precio: f.valor_unitario,
                        subtotal: f.importe,
                        clave_prod_serv: claveProd,
                        clave_unidad: claveUnidad,
                        // aplicaRet por concepto. Default desde el flag del producto.
                        aplicaRet: !!item.product?.aplica_retencion_default,
                        editingDesc: false,
                        productSearch: claveProd,
                        unitSearch: claveUnidad,
                        showProductResults: false,
                        showUnitResults: false,
                    };
                });
        },
        handleSatFocus(event, idx, type) {
            if (type === 'product') {
                this.conceptosSat[idx].showProductResults = true;
            } else {
                this.conceptosSat[idx].showUnitResults = true;
            }
            this.$nextTick(() => event.target.select());
        },
        handleSatBlur(idx, type) {
            // delay para dar tiempo al mousedown del dropdown
            setTimeout(() => {
                if (type === 'product') {
                    this.conceptosSat[idx].showProductResults = false;
                } else {
                    this.conceptosSat[idx].showUnitResults = false;
                }
            }, 150);
        },
        clearSatProduct(idx) {
            this.conceptosSat[idx].productSearch = '';
            this.conceptosSat[idx].clave_prod_serv = '01010101';
            this.conceptosSat[idx].showProductResults = false;
            this.satProductResults = [];
        },
        clearSatUnit(idx) {
            this.conceptosSat[idx].unitSearch = '';
            this.conceptosSat[idx].clave_unidad = 'E48';
            this.conceptosSat[idx].showUnitResults = false;
            this.satUnitResults = [];
        },
        buscarSatProduct(idx) {
            clearTimeout(this.satSearchTimer);
            this.activeSearchIdx = idx;
            let q = this.conceptosSat[idx].productSearch;
            if (q.length < 2) { this.satProductResults = []; return; }
            this.satSearchTimer = setTimeout(() => {
                axios.get('/admin/sat/product-codes', { params: { q } }).then(res => {
                    this.satProductResults = res.data;
                    this.conceptosSat[idx].showProductResults = true;
                });
            }, 300);
        },
        selectSatProduct(idx, item) {
            this.conceptosSat[idx].clave_prod_serv = item.code;
            this.conceptosSat[idx].productSearch = `${item.code} — ${item.description}`;
            this.conceptosSat[idx].showProductResults = false;
            this.satProductResults = [];
        },
        buscarSatUnit(idx) {
            clearTimeout(this.satSearchTimer);
            this.activeSearchIdx = idx;
            let q = this.conceptosSat[idx].unitSearch;
            if (q.length < 1) { this.satUnitResults = []; return; }
            this.satSearchTimer = setTimeout(() => {
                axios.get('/admin/sat/unit-codes', { params: { q } }).then(res => {
                    this.satUnitResults = res.data;
                    this.conceptosSat[idx].showUnitResults = true;
                });
            }, 300);
        },
        selectSatUnit(idx, item) {
            this.conceptosSat[idx].clave_unidad = item.code;
            this.conceptosSat[idx].unitSearch = `${item.code} — ${item.name}`;
            this.conceptosSat[idx].showUnitResults = false;
            this.satUnitResults = [];
        },
        formatNumber(num) {
            if (!num) return '0.00';
            return parseFloat(num).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
    },
};
</script>

<style scoped>
.mostrar {
    display: list-item !important;
    opacity: 1 !important;
}
/* Toggle de retenciones más grande y con buen contraste */
.retencion-switch .form-check-input {
    width: 2.8em;
    height: 1.4em;
    margin-top: 0.1em;
    cursor: pointer;
    background-color: #ced4da;
    border-color: #adb5bd;
    box-shadow: none;
}
.retencion-switch .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.retencion-switch .form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}
.retencion-switch .form-check-label {
    cursor: pointer;
    margin-left: 0.4em;
    padding-top: 0.15em;
    font-size: 0.95rem;
}
</style>
