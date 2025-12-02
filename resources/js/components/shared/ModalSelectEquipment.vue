<template>
    <div v-if="show" class="modal-overlay" @click.self="close">
        <div class="modal-container modal-lg">
            <div class="modal-content-custom">
                <!-- Header -->
                <div class="modal-header-custom bg-warning">
                    <h5 class="modal-title text-dark">
                        <i class="fa fa-print me-2"></i>Seleccionar Equipo
                    </h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Buscador -->
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Buscar por marca, modelo o número de serie..."
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
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Buscando equipos...</p>
                    </div>

                    <!-- Lista de equipos -->
                    <div v-else-if="equipments.length > 0" class="equipments-list">
                        <div
                            v-for="equipment in equipments"
                            :key="equipment.id"
                            class="equipment-item"
                            @click="selectEquipment(equipment)"
                        >
                            <div class="equipment-image">
                                <img
                                    v-if="equipment.image || (equipment.images && equipment.images.length > 0)"
                                    :src="getEquipmentImage(equipment)"
                                    :alt="equipment.model"
                                    @error="handleImageError"
                                >
                                <div v-else class="no-image">
                                    <i class="fa fa-print fa-2x"></i>
                                </div>
                            </div>
                            <div class="equipment-info">
                                <div class="equipment-trademark">{{ equipment.trademark }}</div>
                                <div class="equipment-model">{{ equipment.model }}</div>
                                <div class="equipment-serial text-muted small">
                                    <i class="fa fa-barcode me-1"></i>{{ equipment.serial_number }}
                                </div>
                                <div class="equipment-features mt-1">
                                    <span v-if="equipment.monochrome" class="badge bg-secondary me-1">
                                        <i class="fa fa-file-alt me-1"></i>Mono
                                    </span>
                                    <span v-if="equipment.color" class="badge bg-info">
                                        <i class="fa fa-palette me-1"></i>Color
                                    </span>
                                </div>
                            </div>
                            <div class="equipment-price">
                                <div class="price-label small text-muted">Precio venta</div>
                                <div class="price-value">{{ formatCurrency(equipment.sale_price || 0) }}</div>
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
                        <i class="fa fa-print fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No se encontraron equipos disponibles</p>
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
    name: 'ModalSelectEquipment',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        onlyForSale: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close', 'select'],
    data() {
        return {
            equipments: [],
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
                this.loadEquipments();
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
        async loadEquipments(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    buscar: this.buscar,
                    page: page
                });

                const response = await axios.get(`/admin/equipment/get?${params}`);

                if (response.data) {
                    let allEquipments = response.data.data || response.data;

                    // Filtrar solo equipos con precio de venta si onlyForSale
                    if (this.onlyForSale) {
                        allEquipments = allEquipments.filter(e => e.sale_price > 0);
                    }

                    this.equipments = allEquipments;
                    this.pagination = {
                        current_page: response.data.current_page || 1,
                        last_page: response.data.last_page || 1,
                        total: response.data.total || 0
                    };
                }
            } catch (error) {
                console.error('Error al cargar equipos:', error);
                this.equipments = [];
            } finally {
                this.loading = false;
            }
        },
        onSearchChange() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadEquipments(1);
            }, 300);
        },
        clearSearch() {
            this.buscar = '';
            this.loadEquipments(1);
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadEquipments(page);
            }
        },
        selectEquipment(equipment) {
            this.$emit('select', equipment);
            this.close();
        },
        close() {
            this.buscar = '';
            this.equipments = [];
            this.$emit('close');
        },
        handleKeydown(event) {
            if (event.key === 'Escape') {
                this.close();
            }
        },
        getEquipmentImage(equipment) {
            if (equipment.image) {
                return `/storage/${equipment.image}`;
            }
            if (equipment.images && equipment.images.length > 0) {
                return `/storage/${equipment.images[0].image}`;
            }
            return null;
        },
        handleImageError(event) {
            event.target.style.display = 'none';
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
    max-width: 700px;
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

/* Lista de equipos */
.equipments-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.equipment-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.equipment-item:hover {
    background-color: #fff8e1;
    border-color: #ffc107;
    transform: translateX(4px);
}

.equipment-image {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    margin-right: 1rem;
}

.equipment-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.no-image {
    color: #adb5bd;
}

.equipment-info {
    flex: 1;
    min-width: 0;
}

.equipment-trademark {
    font-weight: 600;
    font-size: 1rem;
    color: #212529;
}

.equipment-model {
    font-size: 0.9rem;
    color: #495057;
    margin-bottom: 0.25rem;
}

.equipment-serial {
    font-size: 0.8rem;
}

.equipment-features {
    display: flex;
    gap: 0.25rem;
}

.equipment-price {
    text-align: right;
    flex-shrink: 0;
    margin-left: 1rem;
}

.price-label {
    font-size: 0.75rem;
}

.price-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: #ffc107;
}

/* Responsive */
@media (max-width: 576px) {
    .modal-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }

    .equipment-item {
        flex-wrap: wrap;
    }

    .equipment-image {
        width: 60px;
        height: 60px;
    }

    .equipment-price {
        margin-left: 0;
        margin-top: 0.5rem;
        width: 100%;
        text-align: right;
    }
}
</style>
