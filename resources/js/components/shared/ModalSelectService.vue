<template>
    <div v-if="show" class="modal-overlay" @click.self="close">
        <div class="modal-container">
            <div class="modal-content-custom">
                <!-- Header -->
                <div class="modal-header-custom bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-concierge-bell me-2"></i>Seleccionar Servicio
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Buscador -->
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Buscar servicio por nombre..."
                            v-model="buscar"
                            @input="onSearchChange"
                            ref="searchInput"
                        >
                        <button v-if="buscar" class="btn btn-outline-secondary" @click="clearSearch">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <!-- Loading -->
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Buscando servicios...</p>
                    </div>

                    <!-- Lista de servicios -->
                    <div v-else-if="services.length > 0" class="services-list">
                        <div
                            v-for="service in services"
                            :key="service.id"
                            class="service-item"
                            @click="selectService(service)"
                        >
                            <div class="service-icon">
                                <i class="fa fa-cog"></i>
                            </div>
                            <div class="service-info">
                                <div class="service-name">{{ service.name }}</div>
                                <div v-if="service.description" class="service-description text-muted small">
                                    {{ service.description }}
                                </div>
                            </div>
                            <div class="service-price">
                                {{ formatCurrency(service.price) }}
                            </div>
                        </div>

                        <!-- Paginación -->
                        <div v-if="pagination.last_page > 1" class="pagination-container mt-3">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
                                            <i class="fa fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            {{ pagination.current_page }} / {{ pagination.last_page }}
                                        </span>
                                    </li>
                                    <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
                                            <i class="fa fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <!-- Sin resultados -->
                    <div v-else class="text-center py-5">
                        <i class="fa fa-concierge-bell fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No se encontraron servicios</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer-custom">
                    <button type="button" class="btn btn-secondary" @click="close">
                        <i class="fa fa-times me-1"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ModalSelectService',
    props: {
        show: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close', 'select'],
    data() {
        return {
            services: [],
            buscar: '',
            loading: false,
            pagination: {
                current_page: 1,
                last_page: 1,
                total: 0
            },
            searchTimeout: null
        }
    },
    watch: {
        show(newVal) {
            if (newVal) {
                this.loadServices();
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
                document.body.style.overflow = 'hidden';
                document.addEventListener('keydown', this.handleKeydown);
            } else {
                document.body.style.overflow = 'auto';
                document.removeEventListener('keydown', this.handleKeydown);
            }
        }
    },
    methods: {
        async loadServices(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    buscar: this.buscar,
                    page: page
                });

                const response = await axios.get(`/admin/services/get?${params}`);

                if (response.data) {
                    this.services = response.data.data || response.data;
                    this.pagination = {
                        current_page: response.data.current_page || 1,
                        last_page: response.data.last_page || 1,
                        total: response.data.total || 0
                    };
                }
            } catch (error) {
                console.error('Error al cargar servicios:', error);
                this.services = [];
            } finally {
                this.loading = false;
            }
        },
        onSearchChange() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadServices(1);
            }, 300);
        },
        clearSearch() {
            this.buscar = '';
            this.loadServices(1);
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadServices(page);
            }
        },
        selectService(service) {
            this.$emit('select', service);
            this.close();
        },
        close() {
            this.buscar = '';
            this.services = [];
            this.$emit('close');
        },
        handleKeydown(event) {
            if (event.key === 'Escape') {
                this.close();
            }
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(amount || 0);
        }
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.handleKeydown);
        document.body.style.overflow = 'auto';
    }
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1055;
    padding: 1rem;
}

.modal-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 550px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

.modal-content-custom {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.modal-header-custom {
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.modal-header-custom h5 {
    margin: 0;
    font-size: 1.1rem;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.modal-footer-custom {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    flex-shrink: 0;
}

/* Lista de servicios */
.services-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.service-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.service-item:hover {
    background-color: #e0f7fa;
    border-color: #0dcaf0;
    transform: translateX(4px);
}

.service-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    margin-right: 1rem;
}

.service-info {
    flex: 1;
    min-width: 0;
}

.service-name {
    font-weight: 600;
    font-size: 1rem;
    color: #212529;
    margin-bottom: 0.25rem;
}

.service-description {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.service-price {
    font-weight: 700;
    font-size: 1.1rem;
    color: #0dcaf0;
    flex-shrink: 0;
    margin-left: 1rem;
}

/* Responsive */
@media (max-width: 576px) {
    .modal-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }

    .service-item {
        flex-wrap: wrap;
    }

    .service-price {
        margin-left: 0;
        margin-top: 0.5rem;
        width: 100%;
        text-align: right;
    }
}
</style>
