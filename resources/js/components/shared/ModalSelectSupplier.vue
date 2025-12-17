<template>
    <div v-if="show" class="modal-overlay" @click.self="close">
        <div class="modal-container modal-lg">
            <div class="modal-content-custom">
                <!-- Header -->
                <div class="modal-header-custom bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-truck me-2"></i>Seleccionar Proveedor
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
                            placeholder="Buscar por nombre, empresa o teléfono..."
                            v-model="buscar"
                            @input="onSearchChange"
                            ref="searchInput"
                        >
                        <button v-if="buscar" class="btn btn-outline-secondary" type="button" @click="clearSearch">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <!-- Loading -->
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Buscando proveedores...</p>
                    </div>

                    <!-- Lista de proveedores -->
                    <div v-else-if="suppliers.length > 0" class="suppliers-list">
                        <div
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            class="supplier-item"
                            @click="selectSupplier(supplier)"
                        >
                            <div class="supplier-avatar">
                                <i class="fa fa-truck"></i>
                            </div>
                            <div class="supplier-info">
                                <div class="supplier-name">{{ supplier.name || supplier.company }}</div>
                                <div v-if="supplier.company && supplier.name" class="supplier-company text-muted small">
                                    <i class="fa fa-building me-1"></i>{{ supplier.company }}
                                </div>
                                <div class="supplier-details">
                                    <span v-if="supplier.phone || supplier.movil" class="me-3">
                                        <i class="fa fa-phone text-muted me-1"></i>{{ supplier.phone || supplier.movil }}
                                    </span>
                                    <span v-if="supplier.email">
                                        <i class="fa fa-envelope text-muted me-1"></i>{{ supplier.email }}
                                    </span>
                                </div>
                                <div v-if="supplier.city || supplier.state" class="supplier-location text-muted small">
                                    <i class="fa fa-map-marker-alt me-1"></i>
                                    {{ [supplier.city, supplier.state].filter(Boolean).join(', ') }}
                                </div>
                            </div>
                            <div class="supplier-status">
                                <span class="badge" :class="supplier.active ? 'bg-success' : 'bg-secondary'">
                                    {{ supplier.active ? 'Activo' : 'Inactivo' }}
                                </span>
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
                        <i class="fa fa-truck fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No se encontraron proveedores</p>
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
    name: 'ModalSelectSupplier',
    props: {
        show: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close', 'select'],
    data() {
        return {
            suppliers: [],
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
                this.loadSuppliers();
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
        async loadSuppliers(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    buscar: this.buscar,
                    page: page
                });

                const response = await axios.get(`/admin/suppliers/search?${params}`);

                if (response.data.suppliers) {
                    this.suppliers = response.data.suppliers.data || response.data.suppliers;
                    this.pagination = {
                        current_page: response.data.suppliers.current_page || 1,
                        last_page: response.data.suppliers.last_page || 1,
                        total: response.data.suppliers.total || 0
                    };
                }
            } catch (error) {
                console.error('Error al cargar proveedores:', error);
                this.suppliers = [];
            } finally {
                this.loading = false;
            }
        },
        onSearchChange() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadSuppliers(1);
            }, 300);
        },
        clearSearch() {
            this.buscar = '';
            this.loadSuppliers(1);
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadSuppliers(page);
            }
        },
        selectSupplier(supplier) {
            this.$emit('select', supplier);
            this.close();
        },
        close() {
            this.buscar = '';
            this.suppliers = [];
            this.$emit('close');
        },
        handleKeydown(event) {
            if (event.key === 'Escape') {
                this.close();
            }
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
    max-width: 600px;
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

/* Lista de proveedores */
.suppliers-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.supplier-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.supplier-item:hover {
    background-color: #d1e7dd;
    border-color: #198754;
    transform: translateX(4px);
}

.supplier-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    margin-right: 1rem;
}

.supplier-info {
    flex: 1;
    min-width: 0;
}

.supplier-name {
    font-weight: 600;
    font-size: 1rem;
    color: #212529;
    margin-bottom: 0.15rem;
}

.supplier-company {
    margin-bottom: 0.15rem;
}

.supplier-details {
    font-size: 0.85rem;
    color: #6c757d;
}

.supplier-location {
    margin-top: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.supplier-status {
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

    .supplier-item {
        flex-wrap: wrap;
    }

    .supplier-status {
        margin-left: 0;
        margin-top: 0.5rem;
        width: 100%;
        text-align: right;
    }
}
</style>
