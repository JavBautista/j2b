<template>
    <div v-if="show" class="modal-overlay" @click.self="close">
        <div class="modal-container modal-lg">
            <div class="modal-content-custom">
                <!-- Header -->
                <div class="modal-header-custom bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-user-check me-2"></i>Seleccionar Cliente
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
                            placeholder="Buscar por nombre o teléfono..."
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
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Buscando clientes...</p>
                    </div>

                    <!-- Lista de clientes -->
                    <div v-else-if="clients.length > 0" class="clients-list">
                        <div
                            v-for="client in clients"
                            :key="client.id"
                            class="client-item"
                            @click="selectClient(client)"
                        >
                            <div class="client-avatar">
                                <i class="fa fa-user"></i>
                            </div>
                            <div class="client-info">
                                <div class="client-name">{{ client.name }}</div>
                                <div class="client-details">
                                    <span v-if="client.movil" class="me-3">
                                        <i class="fa fa-phone text-muted me-1"></i>{{ client.movil }}
                                    </span>
                                    <span v-if="client.email" class="me-3">
                                        <i class="fa fa-envelope text-muted me-1"></i>{{ client.email }}
                                    </span>
                                </div>
                                <div v-if="client.address" class="client-address text-muted small">
                                    <i class="fa fa-map-marker-alt me-1"></i>{{ client.address }}
                                </div>
                            </div>
                            <div class="client-level">
                                <span class="badge" :class="getLevelBadgeClass(client.level)">
                                    Nivel {{ client.level || 1 }}
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
                        <i class="fa fa-user-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No se encontraron clientes</p>
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
    name: 'ModalSelectClient',
    props: {
        show: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close', 'select'],
    data() {
        return {
            clients: [],
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
                this.loadClients();
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
        async loadClients(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    buscar: this.buscar,
                    criterio: 'name',
                    estatus: 'active',
                    page: page
                });

                const response = await axios.get(`/admin/clients/get?${params}`);

                if (response.data.clients) {
                    this.clients = response.data.clients.data || response.data.clients;
                    this.pagination = response.data.pagination || {
                        current_page: response.data.clients.current_page || 1,
                        last_page: response.data.clients.last_page || 1,
                        total: response.data.clients.total || 0
                    };
                }
            } catch (error) {
                console.error('Error al cargar clientes:', error);
                this.clients = [];
            } finally {
                this.loading = false;
            }
        },
        onSearchChange() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadClients(1);
            }, 300);
        },
        clearSearch() {
            this.buscar = '';
            this.loadClients(1);
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadClients(page);
            }
        },
        selectClient(client) {
            this.$emit('select', client);
            this.close();
        },
        close() {
            this.buscar = '';
            this.clients = [];
            this.$emit('close');
        },
        handleKeydown(event) {
            if (event.key === 'Escape') {
                this.close();
            }
        },
        getLevelBadgeClass(level) {
            switch (level) {
                case 2: return 'bg-info';
                case 3: return 'bg-warning text-dark';
                default: return 'bg-secondary';
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

/* Lista de clientes */
.clients-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.client-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.client-item:hover {
    background-color: #e7f1ff;
    border-color: #0d6efd;
    transform: translateX(4px);
}

.client-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    margin-right: 1rem;
}

.client-info {
    flex: 1;
    min-width: 0;
}

.client-name {
    font-weight: 600;
    font-size: 1rem;
    color: #212529;
    margin-bottom: 0.25rem;
}

.client-details {
    font-size: 0.85rem;
    color: #6c757d;
}

.client-address {
    margin-top: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.client-level {
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

    .client-item {
        flex-wrap: wrap;
    }

    .client-level {
        margin-left: 0;
        margin-top: 0.5rem;
        width: 100%;
        text-align: right;
    }
}
</style>
