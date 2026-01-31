<template>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fa fa-database text-success"></i> Indexar Productos y Servicios
            </h4>
            <p class="text-muted mb-0">Sincroniza tu catálogo para el asistente IA</p>
        </div>
        <a href="/admin/configurations/ai-settings" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Info Card -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
        <div class="card-body">
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <i class="fa fa-info-circle fa-2x text-primary"></i>
                </div>
                <div>
                    <h6 class="mb-1 text-primary">¿Qué es la indexación?</h6>
                    <p class="mb-0 small">
                        Para que el asistente IA pueda responder preguntas sobre tus productos y servicios,
                        necesita "conocerlos". La indexación crea una copia especial de tu catálogo optimizada
                        para búsquedas inteligentes. <strong>Debes re-indexar cada vez que agregues o modifiques productos.</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading inicial -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2 text-muted">Consultando estado de indexación...</p>
    </div>

    <!-- Contenido principal -->
    <div v-else>
        <!-- Estado del indexado -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fa fa-bar-chart text-primary me-2"></i>
                    Estado del Catálogo
                </h5>
            </div>
            <div class="card-body">
                <!-- Alert de estado -->
                <div class="alert mb-4" :class="indexStatus.is_synced ? 'alert-success' : 'alert-warning'">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-cube fa-lg me-2"></i>
                                <div>
                                    <strong>Productos</strong>
                                    <div class="small">
                                        {{ indexStatus.products?.indexed || 0 }} indexados de {{ indexStatus.products?.active || 0 }} activos
                                    </div>
                                </div>
                            </div>
                            <span v-if="indexStatus.products?.pending > 0" class="badge bg-warning text-dark mt-1">
                                <i class="fa fa-exclamation-triangle"></i> {{ indexStatus.products.pending }} pendientes
                            </span>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-wrench fa-lg me-2"></i>
                                <div>
                                    <strong>Servicios</strong>
                                    <div class="small">
                                        {{ indexStatus.services?.indexed || 0 }} indexados de {{ indexStatus.services?.active || 0 }} activos
                                    </div>
                                </div>
                            </div>
                            <span v-if="indexStatus.services?.pending > 0" class="badge bg-warning text-dark mt-1">
                                <i class="fa fa-exclamation-triangle"></i> {{ indexStatus.services.pending }} pendientes
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-clock-o fa-lg me-2"></i>
                                <div>
                                    <strong>Última sincronización</strong>
                                    <div class="small">{{ indexStatus.last_sync || 'Nunca' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de estado -->
                <div v-if="indexStatus.is_synced" class="alert alert-light border mb-4">
                    <i class="fa fa-check-circle text-success me-2"></i>
                    <strong>Todo sincronizado.</strong> Tu catálogo está actualizado para el asistente IA.
                </div>
                <div v-else class="alert alert-light border mb-4">
                    <i class="fa fa-exclamation-circle text-warning me-2"></i>
                    <strong>Catálogo desactualizado.</strong> Tienes productos o servicios pendientes de indexar.
                </div>

                <!-- Botones de acción -->
                <div class="d-flex gap-2 flex-wrap">
                    <button
                        class="btn btn-outline-primary"
                        @click="indexProducts"
                        :disabled="indexing"
                    >
                        <span v-if="indexing === 'products'" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fa fa-cube me-1"></i>
                        {{ indexing === 'products' ? 'Indexando...' : 'Indexar Productos' }}
                    </button>

                    <button
                        class="btn btn-outline-primary"
                        @click="indexServices"
                        :disabled="indexing"
                    >
                        <span v-if="indexing === 'services'" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fa fa-wrench me-1"></i>
                        {{ indexing === 'services' ? 'Indexando...' : 'Indexar Servicios' }}
                    </button>

                    <button
                        class="btn btn-success"
                        @click="indexAll"
                        :disabled="indexing"
                    >
                        <span v-if="indexing === 'all'" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fa fa-refresh me-1"></i>
                        {{ indexing === 'all' ? 'Indexando...' : 'Indexar Todo' }}
                        <small class="ms-1">(Recomendado)</small>
                    </button>

                    <button
                        class="btn btn-outline-secondary"
                        @click="loadIndexStatus"
                        :disabled="indexing"
                        title="Actualizar estado"
                    >
                        <i class="fa fa-sync"></i>
                    </button>
                </div>
            </div>
            <div class="card-footer bg-light border-top">
                <small class="text-muted">
                    <i class="fa fa-info-circle me-1"></i>
                    El proceso puede tardar unos segundos dependiendo de la cantidad de items en tu catálogo.
                    No cierres esta página mientras se indexa.
                </small>
            </div>
        </div>

        <!-- Tips -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fa fa-lightbulb-o text-warning me-2"></i>
                    Recomendaciones
                </h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2">
                        <strong>Indexa después de agregar productos:</strong>
                        Cada vez que agregues nuevos productos o servicios, vuelve aquí y haz clic en "Indexar Todo".
                    </li>
                    <li class="mb-2">
                        <strong>Productos inactivos no se indexan:</strong>
                        Solo los productos y servicios marcados como "activos" estarán disponibles para el asistente.
                    </li>
                    <li class="mb-2">
                        <strong>Información completa = Mejores respuestas:</strong>
                        Asegúrate de que tus productos tengan nombre, descripción y precio para que el asistente pueda dar información precisa.
                    </li>
                    <li>
                        <strong>Re-indexa periódicamente:</strong>
                        Si modificas precios o descripciones, re-indexa para que el asistente tenga la información actualizada.
                    </li>
                </ul>
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
            indexing: false, // false, 'products', 'services', 'all'
            indexStatus: {
                products: { indexed: 0, active: 0, pending: 0 },
                services: { indexed: 0, active: 0, pending: 0 },
                last_sync: null,
                is_synced: false
            }
        }
    },
    methods: {
        async loadIndexStatus() {
            this.loading = true;
            try {
                const response = await axios.get('/admin/configurations/ai-settings/indexing/status');
                if (response.data.ok) {
                    this.indexStatus = response.data.data;
                }
            } catch (error) {
                console.error('Error cargando estado de indexado:', error);
                this.showToast('error', 'Error al cargar el estado de indexación');
            } finally {
                this.loading = false;
            }
        },

        async indexProducts() {
            if (this.indexing) return;
            this.indexing = 'products';

            try {
                const response = await axios.post('/admin/configurations/ai-settings/indexing/products');
                if (response.data.ok) {
                    this.showToast('success', response.data.message);
                    await this.loadIndexStatus();
                } else {
                    this.showToast('error', response.data.message || 'Error al indexar');
                }
            } catch (error) {
                console.error(error);
                this.showToast('error', error.response?.data?.message || 'Error al indexar productos');
            } finally {
                this.indexing = false;
            }
        },

        async indexServices() {
            if (this.indexing) return;
            this.indexing = 'services';

            try {
                const response = await axios.post('/admin/configurations/ai-settings/indexing/services');
                if (response.data.ok) {
                    this.showToast('success', response.data.message);
                    await this.loadIndexStatus();
                } else {
                    this.showToast('error', response.data.message || 'Error al indexar');
                }
            } catch (error) {
                console.error(error);
                this.showToast('error', error.response?.data?.message || 'Error al indexar servicios');
            } finally {
                this.indexing = false;
            }
        },

        async indexAll() {
            if (this.indexing) return;
            this.indexing = 'all';

            try {
                const response = await axios.post('/admin/configurations/ai-settings/indexing/all');
                if (response.data.ok) {
                    this.showToast('success', response.data.message);
                    await this.loadIndexStatus();
                } else {
                    this.showToast('error', response.data.message || 'Error al indexar');
                }
            } catch (error) {
                console.error(error);
                this.showToast('error', error.response?.data?.message || 'Error al indexar catálogo');
            } finally {
                this.indexing = false;
            }
        },

        showToast(type, message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type === 'success' ? 'success' : 'error',
                    title: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000
                });
            } else {
                alert(message);
            }
        }
    },
    mounted() {
        this.loadIndexStatus();
    }
}
</script>
