<template>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fa fa-file-text-o text-primary"></i> Facturacion CFDI
            </h4>
            <p class="text-muted mb-0">Configura tus datos fiscales para emitir facturas electronicas</p>
        </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2 text-muted">Cargando configuracion...</p>
    </div>

    <div v-else>

        <!-- Guia de pasos (solo si NO esta registrado) -->
        <div v-if="!isRegistered" class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
            <div class="card-body">
                <h6 class="mb-3 text-primary"><i class="fa fa-list-ol"></i> Completa estos pasos para activar la facturacion:</h6>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge rounded-circle me-2" :class="hasFiscalData ? 'bg-success' : 'bg-secondary'" style="width:24px;height:24px;line-height:24px;text-align:center;">1</span>
                    <span :class="hasFiscalData ? 'text-success fw-bold' : ''">Captura tus datos fiscales (RFC, Razon Social, Regimen, CP)</span>
                    <i v-if="hasFiscalData" class="fa fa-check text-success ms-2"></i>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge rounded-circle me-2" :class="hasCer && hasKey ? 'bg-success' : 'bg-secondary'" style="width:24px;height:24px;line-height:24px;text-align:center;">2</span>
                    <span :class="hasCer && hasKey ? 'text-success fw-bold' : ''">Sube tu Certificado de Sello Digital (CSD)</span>
                    <i v-if="hasCer && hasKey" class="fa fa-check text-success ms-2"></i>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge rounded-circle me-2 bg-secondary" style="width:24px;height:24px;line-height:24px;text-align:center;">3</span>
                    <span>Activa tu servicio de facturacion</span>
                </div>
            </div>
        </div>

        <!-- Card 1: Estado del servicio -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="fa fa-info-circle text-info"></i> Estado del Servicio</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="fa fa-check-circle"></i> CFDI Habilitado
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">Timbres</h6>
                            <p class="mb-0">
                                <strong class="text-primary fs-5">{{ timbresContratados }}</strong> contratados
                                &nbsp;|&nbsp;
                                <strong class="text-success">{{ emisor ? emisor.timbres_asignados - emisor.timbres_usados : timbresContratados }}</strong> disponibles
                                &nbsp;|&nbsp;
                                <strong class="text-secondary">{{ emisor ? emisor.timbres_usados : 0 }}</strong> usados
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">Facturacion</h6>
                            <span v-if="isRegistered" class="badge bg-success fs-6 px-3 py-2">
                                <i class="fa fa-check"></i> Activa
                            </span>
                            <span v-else class="badge bg-warning fs-6 px-3 py-2">
                                <i class="fa fa-clock-o"></i> Pendiente de configurar
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Datos Fiscales (Paso 1) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <span class="badge bg-primary rounded-circle me-2" style="width:24px;height:24px;line-height:16px;font-size:12px;">1</span>
                    <i class="fa fa-building text-primary"></i> Datos Fiscales
                </h5>
                <button v-if="isRegistered && !editingFiscalData" class="btn btn-sm btn-outline-secondary"
                    @click="iniciarEdicionDatos">
                    <i class="fa fa-pencil"></i> Editar datos
                </button>
            </div>
            <div class="card-body">
                <form @submit.prevent="saveFiscalData">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">RFC <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" v-model="form.rfc"
                                maxlength="13" placeholder="XAXX010101000"
                                :disabled="isRegistered || editingFiscalData" required>
                            <small v-if="editingFiscalData" class="text-muted">El RFC no se puede modificar</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Razon Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.razon_social"
                                placeholder="Nombre o Razon Social"
                                :disabled="isRegistered && !editingFiscalData" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Regimen Fiscal <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.regimen_fiscal"
                                :disabled="isRegistered && !editingFiscalData" required>
                                <option value="">Seleccionar...</option>
                                <option v-for="r in regimenes" :key="r.clave" :value="r.clave">
                                    {{ r.clave }} - {{ r.descripcion }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Codigo Postal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.codigo_postal"
                                maxlength="5" placeholder="76028"
                                :disabled="isRegistered && !editingFiscalData" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Serie</label>
                            <input type="text" class="form-control" v-model="form.serie"
                                maxlength="5" placeholder="A"
                                :disabled="isRegistered && !editingFiscalData">
                        </div>
                    </div>
                    <div class="text-end" v-if="!isRegistered || editingFiscalData">
                        <button v-if="editingFiscalData" type="button" class="btn btn-secondary me-2"
                            @click="cancelarEdicionDatos">
                            <i class="fa fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                            {{ saving ? 'Guardando...' : 'Guardar datos fiscales' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 3: Archivos CSD (Paso 2) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <span class="badge bg-primary rounded-circle me-2" style="width:24px;height:24px;line-height:16px;font-size:12px;">2</span>
                    <i class="fa fa-lock text-warning"></i> Certificado de Sello Digital (CSD)
                </h5>
                <button v-if="isRegistered && !editingCsd" class="btn btn-sm btn-outline-warning"
                    @click="iniciarCambioCsd">
                    <i class="fa fa-refresh"></i> Cambiar CSD
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge me-2" :class="hasCer ? 'bg-success' : 'bg-secondary'">
                                <i class="fa" :class="hasCer ? 'fa-check' : 'fa-times'"></i>
                            </span>
                            <span>Certificado (.cer): <strong>{{ hasCer ? 'Subido' : 'Pendiente' }}</strong></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge me-2" :class="hasKey ? 'bg-success' : 'bg-secondary'">
                                <i class="fa" :class="hasKey ? 'fa-check' : 'fa-times'"></i>
                            </span>
                            <span>Llave privada (.key): <strong>{{ hasKey ? 'Subida' : 'Pendiente' }}</strong></span>
                        </div>
                    </div>
                </div>

                <div v-if="!isRegistered || editingCsd">
                    <hr>
                    <!-- Alerta de advertencia cuando se está cambiando CSD en emisor ya registrado -->
                    <div v-if="editingCsd" class="alert alert-warning mb-3">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Atencion:</strong> Al subir nuevos archivos CSD, la facturacion se desactivara temporalmente.
                        Debera re-activarla despues de subir los archivos.
                    </div>
                    <p class="text-muted small mb-3">
                        <i class="fa fa-info-circle"></i>
                        Los archivos CSD los obtiene tu contador del portal del SAT. Son necesarios para firmar tus facturas.
                        <strong>No confundir con la FIEL</strong> (e.firma) que es para tramites personales.
                    </p>
                    <form @submit.prevent="uploadCsd">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Archivo .cer <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" accept=".cer"
                                    @change="csdForm.cer_file = $event.target.files[0]" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Archivo .key <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" accept=".key"
                                    @change="csdForm.key_file = $event.target.files[0]" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Contrasena CSD <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" v-model="csdForm.password"
                                    placeholder="Contrasena del CSD" required>
                            </div>
                        </div>
                        <div class="text-end">
                            <button v-if="editingCsd" type="button" class="btn btn-secondary me-2"
                                @click="cancelarCambioCsd">
                                <i class="fa fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-warning" :disabled="uploadingCsd">
                                <i class="fa" :class="uploadingCsd ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                                {{ uploadingCsd ? 'Subiendo...' : 'Subir CSD' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Card 4: Activar Facturacion (Paso 3) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <span class="badge bg-primary rounded-circle me-2" style="width:24px;height:24px;line-height:16px;font-size:12px;">3</span>
                    <i class="fa fa-rocket text-success"></i> Activar Facturacion
                </h5>
            </div>
            <div class="card-body">
                <div v-if="isRegistered" class="text-center py-3">
                    <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-success">Facturacion activada</h5>
                    <p class="text-muted">Tu empresa esta lista para emitir facturas electronicas desde J2Biznes.</p>
                </div>

                <div v-else>
                    <h6 class="mb-3">Verifica que todo este listo:</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa fa-fw" :class="hasFiscalData ? 'fa-check-circle text-success' : 'fa-circle-o text-muted'"></i>
                            Datos fiscales completos (RFC, Razon Social, Regimen, CP)
                        </li>
                        <li class="mb-2">
                            <i class="fa fa-fw" :class="hasCer && hasKey ? 'fa-check-circle text-success' : 'fa-circle-o text-muted'"></i>
                            Archivos CSD subidos (certificado y llave privada)
                        </li>
                    </ul>

                    <div class="text-center mt-4">
                        <button class="btn btn-success btn-lg" @click="activarFacturacion"
                            :disabled="!canRegister || registering">
                            <i class="fa" :class="registering ? 'fa-spinner fa-spin' : 'fa-rocket'"></i>
                            {{ registering ? 'Activando...' : (emisor && emisor.timbres_usados > 0 ? 'Re-activar Facturacion' : 'Activar Facturacion') }}
                        </button>
                        <p v-if="!canRegister" class="text-muted mt-2 small">
                            Completa los pasos anteriores para activar la facturacion.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    name: 'CfdiConfigComponent',
    data() {
        return {
            loading: true,
            saving: false,
            uploadingCsd: false,
            registering: false,
            editingFiscalData: false,
            editingCsd: false,
            emisor: null,
            timbresContratados: 0,
            hasCer: false,
            hasKey: false,
            form: {
                rfc: '',
                razon_social: '',
                regimen_fiscal: '',
                codigo_postal: '',
                serie: 'A',
            },
            originalForm: {},
            csdForm: {
                cer_file: null,
                key_file: null,
                password: '',
            },
            regimenes: [
                { clave: '601', descripcion: 'General de Ley Personas Morales' },
                { clave: '603', descripcion: 'Personas Morales con Fines no Lucrativos' },
                { clave: '605', descripcion: 'Sueldos y Salarios' },
                { clave: '606', descripcion: 'Arrendamiento' },
                { clave: '608', descripcion: 'Demas ingresos' },
                { clave: '612', descripcion: 'Personas Fisicas con Actividades Empresariales y Profesionales' },
                { clave: '616', descripcion: 'Sin obligaciones fiscales' },
                { clave: '621', descripcion: 'Incorporacion Fiscal' },
                { clave: '625', descripcion: 'Regimen de las Actividades Empresariales con ingresos a traves de Plataformas Tecnologicas' },
                { clave: '626', descripcion: 'Regimen Simplificado de Confianza' },
            ],
        };
    },
    computed: {
        isRegistered() {
            return this.emisor && this.emisor.is_registered;
        },
        hasFiscalData() {
            return this.emisor && this.emisor.rfc && this.emisor.razon_social
                && this.emisor.regimen_fiscal && this.emisor.codigo_postal;
        },
        canRegister() {
            return this.hasFiscalData && this.hasCer && this.hasKey && !this.isRegistered;
        },
    },
    mounted() {
        this.loadData();
    },
    methods: {
        async loadData() {
            this.loading = true;
            try {
                const { data } = await axios.get('/admin/facturacion/configuracion/get');
                if (data.ok) {
                    this.emisor = data.emisor;
                    this.timbresContratados = data.timbres_contratados;
                    this.hasCer = data.has_cer;
                    this.hasKey = data.has_key;

                    if (this.emisor) {
                        this.form.rfc = this.emisor.rfc || '';
                        this.form.razon_social = this.emisor.razon_social || '';
                        this.form.regimen_fiscal = this.emisor.regimen_fiscal || '';
                        this.form.codigo_postal = this.emisor.codigo_postal || '';
                        this.form.serie = this.emisor.serie || 'A';
                    }
                }
            } catch (error) {
                console.error('Error cargando datos CFDI:', error);
                Swal.fire('Error', 'No se pudo cargar la configuracion', 'error');
            } finally {
                this.loading = false;
            }
        },

        // --- Edicion de datos fiscales (cuando ya esta registrado) ---

        async iniciarEdicionDatos() {
            const confirm = await Swal.fire({
                title: 'Editar datos fiscales',
                text: 'Los datos fiscales se usan en todas las facturas que emitas. Asegurate de que los cambios sean correctos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, editar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#3085d6',
            });

            if (!confirm.isConfirmed) return;

            // Guardar copia para poder cancelar
            this.originalForm = { ...this.form };
            this.editingFiscalData = true;
        },

        cancelarEdicionDatos() {
            // Restaurar valores originales
            this.form = { ...this.originalForm };
            this.editingFiscalData = false;
        },

        async saveFiscalData() {
            this.saving = true;
            try {
                const { data } = await axios.post('/admin/facturacion/configuracion/save', this.form);
                if (data.ok) {
                    this.emisor = data.emisor;
                    this.editingFiscalData = false;
                    Swal.fire('Guardado', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                const msg = error.response?.data?.message || 'Error al guardar datos fiscales';
                Swal.fire('Error', msg, 'error');
            } finally {
                this.saving = false;
            }
        },

        // --- Cambio de CSD (cuando ya esta registrado) ---

        async iniciarCambioCsd() {
            const confirm = await Swal.fire({
                title: 'Cambiar archivos CSD',
                html: '<p>Al cambiar los archivos CSD, <strong>la facturacion se desactivara temporalmente</strong> hasta que se re-registre el emisor con los nuevos archivos.</p><p>Escribe <strong>CAMBIAR</strong> para confirmar:</p>',
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'Escribe CAMBIAR',
                showCancelButton: true,
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                inputValidator: (value) => {
                    if (value !== 'CAMBIAR') {
                        return 'Debes escribir CAMBIAR para confirmar';
                    }
                },
            });

            if (!confirm.isConfirmed) return;

            this.editingCsd = true;
            this.csdForm = { cer_file: null, key_file: null, password: '' };
        },

        cancelarCambioCsd() {
            this.editingCsd = false;
            this.csdForm = { cer_file: null, key_file: null, password: '' };
        },

        async uploadCsd() {
            if (!this.csdForm.cer_file || !this.csdForm.key_file || !this.csdForm.password) {
                Swal.fire('Atencion', 'Selecciona ambos archivos e ingresa la contrasena', 'warning');
                return;
            }

            this.uploadingCsd = true;
            try {
                const formData = new FormData();
                formData.append('cer_file', this.csdForm.cer_file);
                formData.append('key_file', this.csdForm.key_file);
                formData.append('password', this.csdForm.password);

                const { data } = await axios.post('/admin/facturacion/configuracion/upload-csd', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (data.ok) {
                    this.hasCer = data.has_cer;
                    this.hasKey = data.has_key;
                    this.editingCsd = false;

                    // Si se necesita re-registro, actualizar emisor
                    if (data.emisor) {
                        this.emisor = data.emisor;
                    }

                    if (data.needs_reregistration) {
                        Swal.fire({
                            title: 'CSD actualizado',
                            html: 'Los archivos se subieron correctamente.<br><strong>Ahora debes re-activar la facturacion</strong> en el paso 3.',
                            icon: 'info',
                            confirmButtonText: 'Entendido',
                        });
                    } else {
                        Swal.fire('Subido', data.message, 'success');
                    }
                } else {
                    Swal.fire('Error', data.message || 'Error al subir CSD', 'error');
                }
            } catch (error) {
                const msg = error.response?.data?.message || 'Error al subir archivos CSD';
                Swal.fire('Error', msg, 'error');
            } finally {
                this.uploadingCsd = false;
            }
        },

        async activarFacturacion() {
            const isReactivation = this.emisor && this.emisor.timbres_usados > 0;

            const confirm = await Swal.fire({
                title: isReactivation ? 'Re-activar Facturacion' : 'Activar Facturacion',
                text: isReactivation
                    ? 'Se re-registrara el emisor con los nuevos archivos CSD en HUB CFDI. ¿Continuar?'
                    : 'Se activara el servicio de facturacion electronica. ¿Continuar?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: isReactivation ? 'Si, re-activar' : 'Si, activar',
                cancelButtonText: 'Cancelar',
            });

            if (!confirm.isConfirmed) return;

            this.registering = true;
            try {
                const { data } = await axios.post('/admin/facturacion/configuracion/registrar');

                if (data.ok) {
                    this.emisor = data.emisor;
                    Swal.fire(
                        isReactivation ? 'Re-activado' : 'Activado',
                        'Tu servicio de facturacion electronica esta listo.',
                        'success'
                    );
                } else {
                    Swal.fire('Error', data.message || 'Error al activar', 'error');
                }
            } catch (error) {
                const msg = error.response?.data?.message || 'Error al activar el servicio de facturacion';
                Swal.fire('Error', msg, 'error');
            } finally {
                this.registering = false;
            }
        },
    },
};
</script>
