<template>
    <div v-if="show" class="modal-overlay" @click.self="close">
        <div class="modal-container modal-xl">
            <div class="modal-content-custom">
                <!-- Header -->
                <div class="modal-header-custom bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-box me-2"></i>Seleccionar Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Buscar por nombre, clave o código de barras..."
                                    v-model="buscar"
                                    @input="onSearchChange"
                                    ref="searchInput"
                                >
                                <button v-if="buscar" class="btn btn-outline-secondary" @click="clearSearch">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" v-model="filtroCategoria" @change="loadProducts(1)">
                                <option value="TODOS">Todas las categorías</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Toggle mostrar sin stock -->
                    <div v-if="showOutOfStock !== null" class="form-check mb-3">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="showOutOfStockCheck"
                            v-model="includeOutOfStock"
                            @change="loadProducts(1)"
                        >
                        <label class="form-check-label" for="showOutOfStockCheck">
                            Mostrar productos sin stock
                        </label>
                    </div>

                    <!-- Loading -->
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Buscando productos...</p>
                    </div>

                    <!-- Grid de productos -->
                    <div v-else-if="products.length > 0" class="products-grid">
                        <div
                            v-for="product in products"
                            :key="product.id"
                            class="product-card"
                            :class="{ 'out-of-stock': product.stock <= 0 && !includeOutOfStock }"
                            @click="selectProduct(product)"
                        >
                            <div class="product-image">
                                <img
                                    v-if="product.image"
                                    :src="getProductImage(product.image)"
                                    :alt="product.name"
                                    @error="handleImageError"
                                >
                                <div v-else class="no-image">
                                    <i class="fa fa-box fa-2x"></i>
                                </div>
                                <span v-if="product.stock <= 0" class="stock-badge bg-danger">Sin stock</span>
                                <span v-else class="stock-badge bg-success">{{ product.stock }}</span>
                            </div>
                            <div class="product-info">
                                <div class="product-key text-muted small">{{ product.key || 'Sin clave' }}</div>
                                <div class="product-name">{{ product.name }}</div>
                                <div class="product-price">
                                    {{ formatCurrency(getPrice(product)) }}
                                </div>
                                <div v-if="clientLevel > 1" class="product-price-type small text-muted">
                                    {{ getPriceLabel() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sin resultados -->
                    <div v-else class="text-center py-5">
                        <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No se encontraron productos</p>
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
    name: 'ModalSelectProduct',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        showOutOfStock: {
            type: Boolean,
            default: null
        },
        clientLevel: {
            type: Number,
            default: 1
        }
    },
    emits: ['close', 'select'],
    data() {
        return {
            products: [],
            categories: [],
            buscar: '',
            filtroCategoria: 'TODOS',
            includeOutOfStock: false,
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
                this.includeOutOfStock = this.showOutOfStock || false;
                this.loadCategories();
                this.loadProducts();
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
        },
        showOutOfStock(newVal) {
            this.includeOutOfStock = newVal || false;
        }
    },
    methods: {
        async loadCategories() {
            try {
                const response = await axios.get('/admin/products/categories');
                if (response.data.ok) {
                    this.categories = response.data.categories;
                }
            } catch (error) {
                console.error('Error al cargar categorías:', error);
            }
        },
        async loadProducts(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    buscar: this.buscar,
                    filtro_categoria: this.filtroCategoria,
                    filtro_activo: 'ACTIVOS',
                    page: page
                });

                const response = await axios.get(`/admin/products/get?${params}`);

                let allProducts = response.data.data || [];

                // Filtrar productos sin stock si no se deben mostrar
                if (!this.includeOutOfStock) {
                    allProducts = allProducts.filter(p => p.stock > 0);
                }

                this.products = allProducts;
                this.pagination = {
                    current_page: response.data.current_page || 1,
                    last_page: response.data.last_page || 1,
                    total: response.data.total || 0
                };
            } catch (error) {
                console.error('Error al cargar productos:', error);
                this.products = [];
            } finally {
                this.loading = false;
            }
        },
        onSearchChange() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadProducts(1);
            }, 300);
        },
        clearSearch() {
            this.buscar = '';
            this.loadProducts(1);
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadProducts(page);
            }
        },
        selectProduct(product) {
            // Validar stock si no se permite sin stock
            if (!this.includeOutOfStock && product.stock <= 0) {
                Swal.fire('Sin Stock', 'Este producto no tiene stock disponible', 'warning');
                return;
            }
            this.$emit('select', product);
            this.close();
        },
        close() {
            this.buscar = '';
            this.filtroCategoria = 'TODOS';
            this.products = [];
            this.$emit('close');
        },
        handleKeydown(event) {
            if (event.key === 'Escape') {
                this.close();
            }
        },
        getProductImage(imagePath) {
            if (!imagePath) return null;
            return `/storage/${imagePath}`;
        },
        handleImageError(event) {
            event.target.style.display = 'none';
        },
        getPrice(product) {
            switch (this.clientLevel) {
                case 2:
                    return product.wholesale > 0 ? product.wholesale : product.retail;
                case 3:
                    return product.wholesale_premium > 0 ? product.wholesale_premium : product.retail;
                default:
                    return product.retail;
            }
        },
        getPriceLabel() {
            switch (this.clientLevel) {
                case 2: return 'Precio Mayoreo';
                case 3: return 'Precio Mayoreo Premium';
                default: return 'Precio Menudeo';
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
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-content-custom {
    display: flex;
    flex-direction: column;
    height: 100%;
    max-height: 90vh;
    overflow: hidden;
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
    min-height: 0;
}

.modal-footer-custom {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    flex-shrink: 0;
}

/* Grid de productos */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
}

.product-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}

.product-card:hover {
    border-color: #198754;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.15);
    transform: translateY(-2px);
}

.product-card.out-of-stock {
    opacity: 0.6;
    cursor: not-allowed;
}

.product-image {
    position: relative;
    height: 120px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.no-image {
    color: #adb5bd;
}

.stock-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: white;
}

.product-info {
    padding: 0.75rem;
}

.product-key {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

.product-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #212529;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.4em;
}

.product-price {
    font-weight: 700;
    font-size: 1.1rem;
    color: #198754;
}

.product-price-type {
    font-size: 0.7rem;
}

/* Responsive */
@media (max-width: 576px) {
    .modal-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }

    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .product-image {
        height: 100px;
    }
}
</style>
