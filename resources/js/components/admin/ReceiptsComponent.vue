<template>
<div>
    <div class="card">
        <div class="card-header">
            <i class="fa fa-receipt"></i> Recibos de {{ client.name }}
        </div>
        <div class="card-body">
            <!-- Buscador -->
            <div class="form-group row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" v-model="buscar" class="form-control" 
                               placeholder="Buscar por folio o número..." 
                               @keyup.enter="loadReceipts(1, buscar)">
                        <button type="button" @click="loadReceipts(1, buscar)" class="btn btn-primary">
                            <i class="fa fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de recibos -->
            <div v-if="arrayReceipts.length > 0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Detalles</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="receipt in arrayReceipts" :key="receipt.id">
                                <td><strong>#{{ receipt.id }}</strong></td>
                                <td>{{ receipt.folio || 'N/A' }}</td>
                                <td>{{ formatDate(receipt.created_at) }}</td>
                                <td>
                                    <span class="badge badge-success">
                                        ${{ formatCurrency(receipt.total) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ receipt.status || 'Activo' }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ receipt.detail?.length || 0 }} artículo(s)
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-info" 
                                                @click="viewDetails(receipt)" 
                                                title="Ver Detalles">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <a v-if="receipt.folio" 
                                           :href="`/print-receipt-rent?id=${receipt.id}&name_file=${receipt.folio || 'recibo_' + receipt.id}`" 
                                           target="_blank"
                                           class="btn btn-sm btn-primary" 
                                           title="Imprimir">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <nav v-if="pagination.last_page > 1">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1, buscar)">
                                Anterior
                            </a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" 
                            :class="[page == pagination.current_page ? 'active' : '']">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page, buscar)" v-text="page"></a>
                        </li>
                        <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page+1, buscar)">
                                Siguiente
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Estado vacío -->
            <div v-else class="text-center py-5">
                <i class="fa fa-receipt fa-3x text-muted mb-3"></i>
                <p class="text-muted">Este cliente no tiene recibos registrados.</p>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del recibo -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal()">
        <div class="modal-container" @click.stop>
            <div class="modal-content-custom">
                <div class="modal-header-custom">
                    <h5 class="modal-title">
                        <i class="fa fa-receipt"></i> Detalles del Recibo #{{ selectedReceipt.id }}
                    </h5>
                    <button type="button" class="close-btn" @click="closeDetailsModal()" aria-label="Close">×</button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Folio:</strong> {{ selectedReceipt.folio || 'N/A' }}</p>
                            <p><strong>Fecha:</strong> {{ formatDate(selectedReceipt.created_at) }}</p>
                            <p><strong>Cliente:</strong> {{ selectedReceipt.client?.name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total:</strong> ${{ formatCurrency(selectedReceipt.total) }}</p>
                            <p><strong>Estado:</strong> 
                                <span class="badge badge-info">{{ selectedReceipt.status || 'Activo' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Detalles de artículos -->
                    <h6><i class="fa fa-list"></i> Artículos:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="detail in selectedReceipt.detail" :key="detail.id">
                                    <td>{{ detail.article_name || detail.description }}</td>
                                    <td>{{ detail.quantity || 1 }}</td>
                                    <td>${{ formatCurrency(detail.price) }}</td>
                                    <td>${{ formatCurrency((detail.quantity || 1) * detail.price) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagos parciales si los hay -->
                    <div v-if="selectedReceipt.partial_payments && selectedReceipt.partial_payments.length > 0">
                        <h6><i class="fa fa-money"></i> Pagos:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in selectedReceipt.partial_payments" :key="payment.id">
                                        <td>{{ formatDate(payment.created_at) }}</td>
                                        <td>${{ formatCurrency(payment.amount) }}</td>
                                        <td>{{ payment.payment_method || 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn btn-secondary" @click="closeDetailsModal()">Cerrar</button>
                    <a v-if="selectedReceipt.folio" 
                       :href="`/print-receipt-rent?id=${selectedReceipt.id}&name_file=${selectedReceipt.folio || 'recibo_' + selectedReceipt.id}`" 
                       target="_blank"
                       class="btn btn-primary">
                        <i class="fa fa-print"></i> Imprimir
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    name: 'ReceiptsComponent',
    props: ['client'],
    data() {
        return {
            arrayReceipts: [],
            pagination: {},
            offset: 3,
            buscar: '',
            loading: false,
            showDetailsModal: false,
            selectedReceipt: {}
        }
    },
    computed: {
        pagesNumber: function() {
            if (!this.pagination.to) {
                return [];
            }
            var from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }

            var to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }

            var pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;
        }
    },
    methods: {
        loadReceipts(page, buscar) {
            let me = this;
            this.loading = true;
            
            var url = '/admin/clients/receipts/get?page=' + page + '&buscar=' + buscar + '&client_id=' + this.client.id;
            
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                if (respuesta.success) {
                    me.arrayReceipts = respuesta.receipts;
                    me.pagination = respuesta.pagination;
                }
            })
            .catch(function (error) {
                console.log(error);
                Swal.fire('Error', 'Error al cargar los recibos', 'error');
            })
            .finally(function () {
                me.loading = false;
            });
        },
        cambiarPagina(page, buscar) {
            this.pagination.current_page = page;
            this.loadReceipts(page, buscar);
        },
        viewDetails(receipt) {
            console.log('Ver detalles del recibo:', receipt);
            this.selectedReceipt = { ...receipt }; // Crear copia para evitar referencias
            this.showDetailsModal = true;
            // Prevenir scroll del body cuando el modal está abierto
            document.body.style.overflow = 'hidden';
        },
        closeDetailsModal() {
            console.log('Cerrando modal');
            this.showDetailsModal = false;
            this.selectedReceipt = {};
            // Restaurar scroll del body
            document.body.style.overflow = 'auto';
        },
        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('es-MX', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        formatCurrency(amount) {
            if (!amount) return '0.00';
            return parseFloat(amount).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        handleKeydown(event) {
            if (event.key === 'Escape' && this.showDetailsModal) {
                this.closeDetailsModal();
            }
        }
    },
    mounted() {
        this.loadReceipts(1, '');
        // Agregar listener para cerrar modal con Escape
        document.addEventListener('keydown', this.handleKeydown);
    },
    beforeUnmount() {
        // Limpiar listeners al destruir componente
        document.removeEventListener('keydown', this.handleKeydown);
        document.body.style.overflow = 'auto';
    }
}
</script>

<style scoped>
.table-responsive {
    border-radius: 8px;
    overflow: hidden;
}

/* Modal personalizado */
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
    z-index: 1050;
    padding: 1rem;
}

.modal-container {
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-content-custom {
    display: flex;
    flex-direction: column;
    height: 100%;
    max-height: 90vh;
}

.modal-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: between;
    align-items: center;
    border-bottom: 1px solid #dee2e6;
}

.modal-header-custom h5 {
    margin: 0;
    font-size: 1.25rem;
    flex: 1;
}

.close-btn {
    border: none;
    background: transparent;
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.close-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
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
    gap: 0.5rem;
}

.pagination .page-link {
    border: none;
    color: #667eea;
    font-weight: 500;
    padding: 0.5rem 1rem;
    margin: 0 0.125rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.pagination .page-link:hover {
    background-color: #667eea;
    color: white;
    transform: translateY(-1px);
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
}

/* Responsive para modal */
@media (max-width: 768px) {
    .modal-overlay {
        padding: 0.5rem;
    }
    
    .modal-container {
        max-width: 100%;
        margin: 0;
    }
    
    .modal-header-custom {
        padding: 1rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer-custom {
        padding: 1rem;
    }
}
</style>