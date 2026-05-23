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
            <!-- ============ SELECTOR DE PLANTILLA PDF ============ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0" style="font-weight: 600;">
                        <i class="fa fa-file-pdf-o text-primary"></i> Plantilla del PDF
                    </h6>
                    <small class="text-muted">Elige el diseño con el que se generarán tus cotizaciones, notas de venta y facturas.</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div
                            v-for="tpl in pdfTemplates"
                            :key="tpl.key"
                            class="col-md-6 mb-3"
                        >
                            <div
                                class="tpl-card"
                                :class="{ 'tpl-card-active': form.pdf_template === tpl.key, 'tpl-card-saving': savingTpl === tpl.key }"
                                @click="seleccionarTemplate(tpl.key)"
                            >
                                <div class="tpl-thumb">
                                    <!-- Si tiene preview real (PNG), mostrarla -->
                                    <img v-if="tpl.preview_cotizacion" :src="tpl.preview_cotizacion" :alt="tpl.label">

                                    <!-- Mock CSS para j2b (no tenemos PNG) -->
                                    <div v-else class="tpl-mock">
                                        <div class="tpl-mock-row tpl-mock-head">
                                            <div class="tpl-mock-shop"></div>
                                            <div class="tpl-mock-logo"></div>
                                        </div>
                                        <div class="tpl-mock-row tpl-mock-qrrow">
                                            <div class="tpl-mock-qr"></div>
                                            <div class="tpl-mock-folio"></div>
                                        </div>
                                        <div class="tpl-mock-table">
                                            <div class="tpl-mock-line" v-for="n in 5" :key="n"></div>
                                        </div>
                                        <div class="tpl-mock-totals"></div>
                                    </div>
                                </div>
                                <div class="tpl-info">
                                    <div class="tpl-radio">
                                        <input
                                            type="radio"
                                            :id="'tpl-' + tpl.key"
                                            :value="tpl.key"
                                            v-model="form.pdf_template"
                                        >
                                        <label :for="'tpl-' + tpl.key" class="mb-0">
                                            <strong>{{ tpl.label }}</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">{{ tpl.description }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ============ /SELECTOR DE PLANTILLA PDF ============ -->

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
            savingTpl: null,
            shopName: '',
            logoUrl: null,
            qrPreviewUrl: null,
            urlSources: [],
            pdfTemplates: [],
            form: {
                show_qr: true,
                qr_url_source: 'web',
                show_logo: true,
                show_signature: false,
                pdf_template: 'j2b',
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
        seleccionarTemplate(key) {
            if (this.form.pdf_template === key || this.savingTpl) return;
            let me = this;
            let prev = me.form.pdf_template;
            me.form.pdf_template = key;
            me.savingTpl = key;

            axios.post('/admin/configurations/receipt-settings/template', { pdf_template: key })
                .then(function(response) {
                    me.savingTpl = null;
                    if (response.data.ok) {
                        let label = (me.pdfTemplates.find(t => t.key === key) || {}).label || key;
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Plantilla activada: ' + label,
                            showConfirmButton: false,
                            timer: 2200,
                            timerProgressBar: true,
                        });
                    }
                })
                .catch(function(error) {
                    me.savingTpl = null;
                    me.form.pdf_template = prev;
                    console.log(error);
                    Swal.fire('Error', 'No se pudo cambiar la plantilla', 'error');
                });
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
                    me.form.pdf_template = response.data.pdf_template || 'j2b';
                    me.pdfTemplates = response.data.pdf_templates_disponibles || [];
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

/* ============ Selector de plantillas PDF ============ */
.tpl-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    background: #fff;
    height: 100%;
}
.tpl-card:hover {
    border-color: #b8c7d6;
    transform: translateY(-1px);
}
.tpl-card-active {
    border-color: #1a4d8f;
    box-shadow: 0 4px 12px rgba(26, 77, 143, 0.15);
}
.tpl-card-saving { opacity: 0.7; pointer-events: none; }

/* Thumb con relación carta-vertical garantizada (técnica padding-top) */
.tpl-thumb {
    width: 100%;
    padding-top: 129.4%; /* 11/8.5 = 1.294 ≈ carta vertical */
    position: relative;
    background: #f8f9fa;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 8px;
}
.tpl-thumb > img,
.tpl-thumb > .tpl-mock {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    width: 100%; height: 100%;
}
.tpl-thumb > img {
    object-fit: contain;
    object-position: top center;
}

.tpl-info { padding: 4px 6px 6px 6px; }
.tpl-radio { display: flex; align-items: center; gap: 8px; }
.tpl-radio input[type="radio"] { margin: 0; cursor: pointer; }
.tpl-radio label { cursor: pointer; }

/* Mock CSS para template sin preview (j2b) */
.tpl-mock {
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #fff;
}
.tpl-mock-row { display: flex; gap: 10px; align-items: center; justify-content: space-between; }
.tpl-mock-shop { width: 60%; height: 10px; background: #495057; border-radius: 2px; }
.tpl-mock-logo { width: 42px; height: 42px; background: #cfe2ff; border-radius: 8px; flex-shrink: 0; }
.tpl-mock-qrrow { margin-top: 4px; }
.tpl-mock-qr { width: 36px; height: 36px; background: #212529; border-radius: 3px; flex-shrink: 0; }
.tpl-mock-folio { width: 55%; height: 8px; background: #adb5bd; border-radius: 2px; }
.tpl-mock-table { margin-top: 8px; display: flex; flex-direction: column; gap: 5px; }
.tpl-mock-line { height: 6px; background: #dee2e6; border-radius: 2px; }
.tpl-mock-line:nth-child(odd) { width: 95%; }
.tpl-mock-line:nth-child(even) { width: 88%; }
.tpl-mock-totals { margin-top: auto; width: 45%; height: 18px; background: #cfe2ff; align-self: flex-end; border-radius: 3px; }
</style>
