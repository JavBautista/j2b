<template>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="font-weight: 600;">
                    <i class="fa fa-file-pdf-o text-danger"></i> Recibos PDF
                </h4>
                <p class="mb-0 text-muted">Personaliza el contenido de tus recibos impresos</p>
            </div>
            <a href="/admin/configurations" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
        </div>

        <div v-else>
            <div class="row">
                <!-- Configuración -->
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0" style="font-weight: 600;">
                                <i class="fa fa-cog text-primary"></i> Opciones del Recibo
                            </h6>
                        </div>
                        <div class="card-body">

                            <!-- Logo -->
                            <div class="mb-4 pb-3" style="border-bottom: 1px solid #eee;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Mostrar Logo</strong>
                                        <div class="text-muted" style="font-size: 12px;">El logo de tu tienda en el encabezado del recibo</div>
                                    </div>
                                    <label class="receipt-switch">
                                        <input type="checkbox" v-model="form.show_logo">
                                        <span class="receipt-switch-slider"></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Firma -->
                            <div class="mb-4 pb-3" style="border-bottom: 1px solid #eee;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Mostrar Firma</strong>
                                        <div class="text-muted" style="font-size: 12px;">Firma del representante legal al pie del recibo</div>
                                    </div>
                                    <label class="receipt-switch">
                                        <input type="checkbox" v-model="form.show_signature">
                                        <span class="receipt-switch-slider"></span>
                                    </label>
                                </div>
                            </div>

                            <!-- QR -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <strong>Mostrar Codigo QR</strong>
                                        <div class="text-muted" style="font-size: 12px;">Codigo QR con enlace a tu sitio web o red social</div>
                                    </div>
                                    <label class="receipt-switch">
                                        <input type="checkbox" v-model="form.show_qr">
                                        <span class="receipt-switch-slider"></span>
                                    </label>
                                </div>

                                <div v-if="form.show_qr">
                                    <label class="form-label small fw-bold">Enlace del QR</label>
                                    <select class="form-select" v-model="form.qr_url_source">
                                        <option v-for="src in urlSources" :key="src.key" :value="src.key" :disabled="!src.has_value">
                                            {{ src.label }} {{ src.has_value ? '- ' + src.value : '(sin configurar)' }}
                                        </option>
                                    </select>

                                    <!-- URL seleccionada -->
                                    <div v-if="selectedUrl" class="mt-2 p-2" style="background: #f8f9fa; border-radius: 6px; font-size: 12px;">
                                        <i class="fa fa-link text-primary"></i>
                                        <span class="text-muted">{{ selectedUrl }}</span>
                                    </div>

                                    <!-- Alerta si no tiene URLs -->
                                    <div v-if="!hasAnyUrl" class="mt-3 p-3" style="background: #fff3e0; border-radius: 6px; border-left: 3px solid #ff9800;">
                                        <small style="color: #e65100;">
                                            <i class="fa fa-warning"></i> No tienes URLs configuradas.
                                            <a href="/admin/shop/edit" class="fw-bold text-primary">Ir a editar tienda</a>
                                            para agregar tu sitio web o redes sociales.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Guardar -->
                            <div class="text-end mt-4">
                                <button class="btn btn-primary" @click="guardar" :disabled="saving">
                                    <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i> Guardar Configuracion
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0" style="font-weight: 600;">
                                <i class="fa fa-eye text-primary"></i> Vista Previa
                            </h6>
                        </div>
                        <div class="card-body">
                            <div style="border: 1px dashed #dee2e6; border-radius: 8px; padding: 1rem; background: #fff; min-height: 200px;">

                                <!-- Preview Logo -->
                                <div class="text-center mb-3" v-if="form.show_logo">
                                    <img v-if="logoUrl" :src="logoUrl" style="max-height: 60px; max-width: 100%; width: auto;">
                                    <div v-else style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fa fa-image fa-2x" style="color: #adb5bd;"></i>
                                    </div>
                                </div>

                                <!-- Preview contenido -->
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <strong>{{ shopName }}</strong>
                                    <div style="font-size: 11px; color: #adb5bd;">Datos de la tienda...</div>
                                </div>

                                <div style="border-top: 1px dashed #dee2e6; padding-top: 0.75rem; margin-bottom: 0.75rem;">
                                    <div style="font-size: 11px; color: #adb5bd; text-align: center;">
                                        Detalle de articulos / servicios...
                                    </div>
                                </div>

                                <!-- Preview QR -->
                                <div v-if="form.show_qr && selectedUrl" class="text-center mt-3" style="border-top: 1px dashed #dee2e6; padding-top: 0.75rem;">
                                    <img v-if="qrPreviewUrl" :src="qrPreviewUrl" style="width: 80px; height: 80px;">
                                    <div v-else style="width: 70px; height: 70px; background: #e9ecef; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fa fa-qrcode fa-2x" style="color: #6c757d;"></i>
                                    </div>
                                    <div style="font-size: 10px; color: #6c757d; margin-top: 4px;">{{ selectedSourceLabel }}</div>
                                </div>

                                <!-- Preview Firma -->
                                <div v-if="form.show_signature" class="text-center mt-3" style="border-top: 1px dashed #dee2e6; padding-top: 0.75rem;">
                                    <div style="width: 80px; border-top: 1px solid #adb5bd; margin: 0 auto; padding-top: 4px;">
                                        <span style="font-size: 10px; color: #6c757d;">Firma</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loading: true,
            saving: false,
            shopName: '',
            logoUrl: null,
            qrPreviewUrl: null,
            urlSources: [],
            form: {
                show_qr: true,
                qr_url_source: 'web',
                show_logo: true,
                show_signature: false,
            },
        };
    },
    computed: {
        selectedUrl() {
            let src = this.urlSources.find(s => s.key === this.form.qr_url_source);
            return src && src.has_value ? src.value : '';
        },
        selectedSourceLabel() {
            let src = this.urlSources.find(s => s.key === this.form.qr_url_source);
            return src ? src.label : '';
        },
        hasAnyUrl() {
            return this.urlSources.some(s => s.has_value);
        },
    },
    watch: {
        'form.qr_url_source'() {
            this.loadQrPreview();
        },
    },
    methods: {
        loadQrPreview() {
            let url = this.selectedUrl;
            if (!url) {
                this.qrPreviewUrl = null;
                return;
            }
            this.qrPreviewUrl = '/admin/configurations/receipt-settings/qr-preview?url=' + encodeURIComponent(url);
        },
        cargar() {
            let me = this;
            axios.get('/admin/configurations/receipt-settings/get').then(function(response) {
                me.loading = false;
                if (response.data.ok) {
                    me.form.show_qr = response.data.settings.show_qr;
                    me.form.qr_url_source = response.data.settings.qr_url_source;
                    me.form.show_logo = response.data.settings.show_logo;
                    me.form.show_signature = response.data.settings.show_signature;
                    me.urlSources = response.data.url_sources;
                    me.shopName = response.data.shop_name;
                    me.logoUrl = response.data.logo_url;
                    me.loadQrPreview();
                }
            }).catch(function(error) {
                me.loading = false;
                console.log(error);
            });
        },
        guardar() {
            let me = this;

            if (me.form.show_qr && !me.selectedUrl) {
                Swal.fire('Atencion', 'Selecciona un enlace valido para el QR o desactiva el QR.', 'warning');
                return;
            }

            me.saving = true;
            axios.post('/admin/configurations/receipt-settings/save', me.form).then(function(response) {
                me.saving = false;
                if (response.data.ok) {
                    Swal.fire('Guardado', response.data.message, 'success');
                }
            }).catch(function(error) {
                me.saving = false;
                console.log(error);
                Swal.fire('Error', 'No se pudo guardar la configuracion', 'error');
            });
        },
    },
    mounted() {
        this.cargar();
    },
};
</script>

<style scoped>
.receipt-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.receipt-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.receipt-switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    border-radius: 24px;
    transition: 0.3s;
}
.receipt-switch-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.3s;
}
.receipt-switch input:checked + .receipt-switch-slider {
    background-color: #00c9a7;
}
.receipt-switch input:checked + .receipt-switch-slider:before {
    transform: translateX(20px);
}
</style>
