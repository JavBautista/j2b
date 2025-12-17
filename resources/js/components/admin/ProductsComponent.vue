<template>
<div>
    <div class="container-fluid">
        <!-- Card principal de productos -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-cube"></i> Productos de {{ shop.name }}</span>
                <div>
                    <!-- Toggle Vista -->
                    <div class="btn-group mr-2">
                        <button type="button" class="btn btn-sm" :class="vistaActual === 'cards' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'cards'" title="Vista Cards">
                            <i class="fa fa-th-large"></i>
                        </button>
                        <button type="button" class="btn btn-sm" :class="vistaActual === 'tabla' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'tabla'" title="Vista Tabla">
                            <i class="fa fa-list"></i>
                        </button>
                    </div>
                    <button v-if="!userLimited" type="button" @click="abrirModal('crear')" class="btn btn-primary">
                        <i class="fa fa-plus"></i>&nbsp;Nuevo Producto
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros y búsqueda -->
                <div class="form-group row mb-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <input type="text" v-model="buscar" class="form-control" placeholder="Buscar por nombre, código o código de barras..." @keyup.enter="loadProducts(1)">
                            <select class="form-control col-md-2" v-model="filtroCategoria">
                                <option value="TODOS">Todas las categorías</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <select class="form-control col-md-2" v-model="filtroActivo">
                                <option value="TODOS">Todos</option>
                                <option value="ACTIVOS">Activos</option>
                                <option value="INACTIVOS">Inactivos</option>
                            </select>
                            <button type="submit" @click="loadProducts(1)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- VISTA CARDS -->
                <div class="row" v-if="vistaActual === 'cards'">
                    <div class="col-md-4 col-lg-3 mb-4" v-for="product in arrayProducts" :key="product.id">
                        <div class="card product-card h-100" :class="{'inactive-card': !product.active}">
                            <!-- Imagen del producto -->
                            <div class="product-image-container" @click="abrirModal('ver', product)" style="cursor: pointer;">
                                <img v-if="product.image" :src="getImageUrl(product.image)" class="product-image" :alt="product.name">
                                <div v-else class="product-image-placeholder">
                                    <i class="fa fa-cube fa-3x text-muted"></i>
                                </div>
                            </div>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="product-key">{{ product.key || '#' + product.id }}</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModal('ver', product)">
                                            <i class="fa fa-eye text-info"></i> Ver
                                        </a></li>
                                        <li v-if="!userLimited"><a class="dropdown-item" href="#" @click.prevent="abrirModal('editar', product)">
                                            <i class="fa fa-edit text-primary"></i> Editar
                                        </a></li>
                                        <li v-if="!userLimited"><a class="dropdown-item" href="#" @click.prevent="abrirModalStock(product)">
                                            <i class="fa fa-cubes text-info"></i> Ajustar Stock
                                        </a></li>
                                        <li v-if="!userLimited"><a class="dropdown-item" href="#" @click.prevent="abrirModalImagenes(product)">
                                            <i class="fa fa-image text-success"></i> Gestionar Imágenes
                                        </a></li>
                                        <li v-if="!userLimited"><hr class="dropdown-divider"></li>
                                        <template v-if="!userLimited && product.active">
                                            <li><a class="dropdown-item" href="#" @click.prevent="desactivarProducto(product.id)">
                                                <i class="fa fa-toggle-off text-danger"></i> Desactivar
                                            </a></li>
                                        </template>
                                        <template v-if="!userLimited && !product.active">
                                            <li><a class="dropdown-item" href="#" @click.prevent="activarProducto(product.id)">
                                                <i class="fa fa-toggle-on text-success"></i> Activar
                                            </a></li>
                                        </template>
                                        <li v-if="!userLimited"><hr class="dropdown-divider"></li>
                                        <li v-if="!userLimited"><a class="dropdown-item text-danger" href="#" @click.prevent="confirmarEliminar(product)">
                                            <i class="fa fa-trash"></i> Eliminar
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body" @click="abrirModal('ver', product)" style="cursor: pointer;">
                                <h5 class="card-title product-name">
                                    {{ product.name }}
                                </h5>
                                <div class="product-info">
                                    <div class="info-item">
                                        <span v-if="product.active" class="badge badge-success">Activo</span>
                                        <span v-else class="badge badge-danger">Inactivo</span>
                                        <span v-if="product.category" class="badge badge-info ml-1">{{ product.category.name }}</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa fa-dollar-sign text-success"></i>
                                        <span>Nv1: <strong>${{ formatMoney(product.retail) }}</strong></span>
                                    </div>
                                    <div class="info-item" v-if="product.wholesale > 0">
                                        <i class="fa fa-dollar-sign text-info"></i>
                                        <span>Nv2: ${{ formatMoney(product.wholesale) }}</span>
                                    </div>
                                    <div class="info-item" v-if="product.wholesale_premium > 0">
                                        <i class="fa fa-dollar-sign text-warning"></i>
                                        <span>Nv3: ${{ formatMoney(product.wholesale_premium) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa fa-boxes text-muted"></i>
                                        <span>Stock: <strong :class="product.stock > 0 ? 'text-success' : 'text-danger'">{{ product.stock }}</strong></span>
                                        <span v-if="product.reserve > 0" class="text-warning ml-2">| Reserva: {{ product.reserve }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    Costo: ${{ formatMoney(product.cost) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA TABLA -->
                <div class="table-responsive" v-if="vistaActual === 'tabla'">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Img</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th class="text-right">Costo</th>
                                <th class="text-right">Precio Nv1</th>
                                <th class="text-right">Precio Nv2</th>
                                <th class="text-right">Precio Nv3</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Reserva</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in arrayProducts" :key="product.id" :class="{'table-secondary': !product.active}">
                                <td>
                                    <img v-if="product.image" :src="getImageUrl(product.image)" class="img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                    <i v-else class="fa fa-cube text-muted"></i>
                                </td>
                                <td><strong>{{ product.key || product.id }}</strong></td>
                                <td>{{ product.name }}</td>
                                <td>{{ product.category ? product.category.name : '-' }}</td>
                                <td class="text-right">${{ formatMoney(product.cost) }}</td>
                                <td class="text-right"><strong>${{ formatMoney(product.retail) }}</strong></td>
                                <td class="text-right">${{ formatMoney(product.wholesale) }}</td>
                                <td class="text-right">${{ formatMoney(product.wholesale_premium) }}</td>
                                <td class="text-center">
                                    <span :class="product.stock > 0 ? 'text-success' : 'text-danger'">{{ product.stock }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-warning">{{ product.reserve || 0 }}</span>
                                </td>
                                <td>
                                    <span v-if="product.active" class="badge badge-success">Activo</span>
                                    <span v-else class="badge badge-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info btn-sm" @click="abrirModal('ver', product)" title="Ver">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-primary btn-sm" @click="abrirModal('editar', product)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-success btn-sm" @click="abrirModalImagenes(product)" title="Imágenes">
                                            <i class="fa fa-image"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-warning btn-sm" @click="abrirModalStock(product)" title="Ajustar Stock">
                                            <i class="fa fa-cubes"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-danger btn-sm" @click="confirmarEliminar(product)" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje si no hay productos -->
                <div v-if="arrayProducts.length === 0 && !loading" class="text-center py-5">
                    <i class="fa fa-cube fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron productos con los criterios de búsqueda.</p>
                </div>

                <!-- Paginación -->
                <nav>
                    <ul class="pagination">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page==isActived ? 'active':'']">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page)" v-text="page"></a>
                        </li>
                        <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page+1)">Sig</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar/Ver -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalEditar}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ modoLectura ? 'Detalle del Producto' : (modoEdicion ? 'Editar Producto' : 'Nuevo Producto') }}</h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="errorForm" class="alert alert-danger">
                        <div v-for="error in erroresForm" :key="error">{{ error }}</div>
                    </div>
                    <form @submit.prevent="guardarProducto">
                        <p v-if="!modoLectura"><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right"><strong v-if="!modoLectura" class="text text-danger">*</strong> Nombre</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" v-model="formProduct.name" required :disabled="modoLectura">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Código</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" v-model="formProduct.key" placeholder="SKU o código interno" :disabled="modoLectura">
                            </div>
                            <label class="col-md-2 col-form-label text-md-right">Código Barras</label>
                            <div class="col-md-3">
                                <input type="text" class="form-control" v-model="formProduct.barcode" :disabled="modoLectura">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Descripción</label>
                            <div class="col-md-9">
                                <textarea class="form-control" v-model="formProduct.description" rows="2" placeholder="Descripción del producto..." :disabled="modoLectura"></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Categoría</label>
                            <div class="col-md-9">
                                <select class="form-control" v-model="formProduct.category_id" :disabled="modoLectura">
                                    <option value="">Sin categoría</option>
                                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                        </div>
                        <hr>
                        <h6 class="text-muted mb-3">Precios</h6>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right"><strong v-if="!modoLectura" class="text text-danger">*</strong> Costo Compra</label>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formProduct.cost" required :disabled="modoLectura">
                                </div>
                            </div>
                            <label class="col-md-2 col-form-label text-md-right"><strong v-if="!modoLectura" class="text text-danger">*</strong> Precio Nv1</label>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formProduct.retail" required :disabled="modoLectura">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Precio Nv2</label>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formProduct.wholesale" :disabled="modoLectura">
                                </div>
                            </div>
                            <label class="col-md-2 col-form-label text-md-right">Precio Nv3</label>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formProduct.wholesale_premium" :disabled="modoLectura">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h6 class="text-muted mb-3">Inventario</h6>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Stock</label>
                            <div class="col-md-3">
                                <input type="number" class="form-control" v-model="formProduct.stock" min="0" :disabled="modoLectura">
                            </div>
                            <label class="col-md-2 col-form-label text-md-right">Reserva</label>
                            <div class="col-md-4">
                                <input type="number" class="form-control" v-model="formProduct.reserve" min="0" :disabled="modoLectura">
                            </div>
                        </div>
                        </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">{{ modoLectura ? 'Cerrar' : 'Cancelar' }}</button>
                    <template v-if="modoLectura">
                        <button type="button" class="btn btn-primary" @click="abrirModal('editar', formProduct)">
                            <i class="fa fa-edit"></i> Editar
                        </button>
                    </template>
                    <template v-else>
                        <button type="button" class="btn btn-primary" @click="guardarProducto" :disabled="guardando">
                            <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                            <i class="fa fa-save" v-else></i>
                            {{ guardando ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajustar Stock -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalStock}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ajustar Stock</h4>
                    <button type="button" class="close" @click="modalStock = false" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body" v-if="productoStock">
                    <p><strong>Producto:</strong> {{ productoStock.name }}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Stock actual:</strong> {{ productoStock.stock }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Reserva actual:</strong> {{ productoStock.reserve || 0 }}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group row">
                        <label class="col-md-4">Nuevo stock:</label>
                        <div class="col-md-8">
                            <input type="number" class="form-control" v-model.number="nuevoStock" min="0">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-4">Nueva reserva:</label>
                        <div class="col-md-8">
                            <input type="number" class="form-control" v-model.number="nuevaReserva" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="modalStock = false">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="actualizarStock" :disabled="guardando">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestionar Imágenes -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalImagenes}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Imágenes del Producto</h4>
                    <button type="button" class="close" @click="modalImagenes = false" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body" v-if="productoImagenes">
                    <p><strong>Producto:</strong> {{ productoImagenes.name }}</p>
                    <hr>

                    <!-- Subir nueva imagen -->
                    <div class="mb-4">
                        <h6>Subir nueva imagen</h6>
                        <div class="input-group">
                            <input type="file" class="form-control" @change="seleccionarImagen" accept="image/*" ref="inputImagen">
                            <button class="btn btn-primary" @click="subirImagen" :disabled="!imagenSeleccionada || subiendoImagen">
                                <i class="fa fa-spinner fa-spin" v-if="subiendoImagen"></i>
                                <i class="fa fa-upload" v-else></i>
                                {{ subiendoImagen ? 'Subiendo...' : 'Subir' }}
                            </button>
                        </div>
                        <small class="text-muted">Formatos: JPG, PNG, GIF, WebP. Máximo 2MB.</small>
                    </div>

                    <!-- Imagen Principal -->
                    <div class="mb-4">
                        <h6>Imagen Principal</h6>
                        <div v-if="productoImagenes.image" class="position-relative d-inline-block">
                            <img :src="getImageUrl(productoImagenes.image)" class="img-thumbnail" style="max-width: 200px; max-height: 200px; cursor: pointer;" @click="verImagen(productoImagenes.image)">
                            <button class="btn btn-danger btn-sm position-absolute" style="top: 5px; right: 5px;" @click="eliminarImagenPrincipal" :disabled="eliminandoImagen">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        <div v-else class="text-muted">
                            <i class="fa fa-image fa-3x"></i>
                            <p>Sin imagen principal</p>
                        </div>
                    </div>

                    <!-- Imágenes Alternativas -->
                    <div>
                        <h6>Imágenes Alternativas ({{ productoImagenes.images ? productoImagenes.images.length : 0 }})</h6>
                        <div class="row" v-if="productoImagenes.images && productoImagenes.images.length > 0">
                            <div class="col-md-3 mb-3" v-for="(img, index) in productoImagenes.images" :key="img.id">
                                <div class="position-relative">
                                    <img :src="getImageUrl(img.image)" class="img-thumbnail" style="width: 100%; height: 120px; object-fit: cover; cursor: pointer;" @click="verGaleriaProducto(index)">
                                    <button class="btn btn-danger btn-sm position-absolute" style="top: 5px; right: 5px;" @click="eliminarImagenAlternativa(img.id)" :disabled="eliminandoImagen">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted">
                            <p>Sin imágenes alternativas</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="modalImagenes = false">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>
</template>

<script>
export default {
    props: ['shop', 'userLimited'],
    data() {
        return {
            arrayProducts: [],
            pagination: {
                'total': 0,
                'current_page': 0,
                'per_page': 0,
                'last_page': 0,
                'from': 0,
                'to': 0
            },
            offset: 3,
            loading: false,
            buscar: '',
            filtroCategoria: 'TODOS',
            filtroActivo: 'TODOS',

            // Modales
            modalEditar: false,
            modalStock: false,
            modalImagenes: false,

            // Listas
            categorias: [],

            // Form
            modoEdicion: false,
            modoLectura: false,
            formProduct: {
                id: null,
                name: '',
                key: '',
                barcode: '',
                category_id: '',
                description: '',
                cost: 0,
                retail: 0,
                wholesale: 0,
                wholesale_premium: 0,
                stock: 0,
                reserve: 0
            },

            // Stock
            productoStock: null,
            nuevoStock: 0,
            nuevaReserva: 0,

            // Imágenes
            productoImagenes: null,
            imagenSeleccionada: null,
            subiendoImagen: false,
            eliminandoImagen: false,

            // UI
            guardando: false,
            errorForm: false,
            erroresForm: [],
            vistaActual: localStorage.getItem('admin_vista') || 'cards'
        }
    },
    watch: {
        vistaActual(newVal) {
            localStorage.setItem('admin_vista', newVal);
        }
    },
    computed: {
        isActived() {
            return this.pagination.current_page;
        },
        pagesNumber() {
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
    mounted() {
        this.loadProducts(1);
        this.loadCategories();
    },
    methods: {
        loadProducts(page) {
            let me = this;
            me.loading = true;
            var url = `/admin/products/get?page=${page}&buscar=${me.buscar}&filtro_categoria=${me.filtroCategoria}&filtro_activo=${me.filtroActivo}`;
            axios.get(url).then(function(response) {
                var respuesta = response.data;
                me.arrayProducts = respuesta.data;
                me.pagination = {
                    total: respuesta.total,
                    current_page: respuesta.current_page,
                    per_page: respuesta.per_page,
                    last_page: respuesta.last_page,
                    from: respuesta.from,
                    to: respuesta.to
                };
            }).catch(function(error) {
                console.log(error);
            }).finally(function() {
                me.loading = false;
            });
        },

        loadCategories() {
            let me = this;
            axios.get('/admin/products/categories').then(function(response) {
                if (response.data.ok) {
                    me.categorias = response.data.categories;
                }
            }).catch(function(error) {
                console.log(error);
            });
        },

        cambiarPagina(page) {
            let me = this;
            me.pagination.current_page = page;
            me.loadProducts(page);
        },

        // Helpers
        formatMoney(value) {
            if (!value) return '0.00';
            return parseFloat(value).toFixed(2);
        },

        getImageUrl(path) {
            if (!path) return '';
            return `/storage/${path}`;
        },

        // Modales
        abrirModal(tipo, product = null) {
            this.cerrarModal();

            if (tipo === 'crear') {
                this.modoEdicion = false;
                this.modoLectura = false;
                this.formProduct = {
                    id: null,
                    name: '',
                    key: '',
                    barcode: '',
                    category_id: '',
                    description: '',
                    cost: 0,
                    retail: 0,
                    wholesale: 0,
                    wholesale_premium: 0,
                    stock: 0,
                    reserve: 0
                };
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            } else if (tipo === 'ver' && product) {
                this.modoEdicion = false;
                this.modoLectura = true;
                this.formProduct = { ...product };
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            } else if (tipo === 'editar' && product) {
                this.modoEdicion = true;
                this.modoLectura = false;
                this.formProduct = {
                    id: product.id,
                    name: product.name,
                    key: product.key || '',
                    barcode: product.barcode || '',
                    category_id: product.category_id || '',
                    description: product.description || '',
                    cost: product.cost,
                    retail: product.retail,
                    wholesale: product.wholesale || 0,
                    wholesale_premium: product.wholesale_premium || 0,
                    stock: product.stock || 0,
                    reserve: product.reserve || 0
                };
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            }
        },

        abrirModalStock(product) {
            this.productoStock = product;
            this.nuevoStock = product.stock || 0;
            this.nuevaReserva = product.reserve || 0;
            this.modalStock = true;
        },

        abrirModalImagenes(product) {
            this.productoImagenes = product;
            this.imagenSeleccionada = null;
            this.modalImagenes = true;
        },

        cerrarModal() {
            this.modalEditar = false;
            this.modalStock = false;
            this.modalImagenes = false;
        },

        // CRUD
        guardarProducto() {
            let me = this;
            me.guardando = true;
            me.errorForm = false;
            me.erroresForm = [];

            let url = me.modoEdicion ? '/admin/products/update' : '/admin/products/store';
            let method = me.modoEdicion ? axios.put : axios.post;

            method(url, me.formProduct).then(function(response) {
                if (response.data.ok) {
                    me.cerrarModal();
                    me.loadProducts(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                me.errorForm = true;
                if (error.response && error.response.data.errors) {
                    me.erroresForm = Object.values(error.response.data.errors).flat();
                } else {
                    me.erroresForm = ['Error al guardar el producto'];
                }
            }).finally(function() {
                me.guardando = false;
            });
        },

        actualizarStock() {
            let me = this;
            me.guardando = true;

            axios.put(`/admin/products/${me.productoStock.id}/stock`, {
                stock: me.nuevoStock,
                reserve: me.nuevaReserva
            }).then(function(response) {
                if (response.data.ok) {
                    me.modalStock = false;
                    me.loadProducts(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al actualizar stock', 'error');
            }).finally(function() {
                me.guardando = false;
            });
        },

        // Imágenes
        seleccionarImagen(event) {
            this.imagenSeleccionada = event.target.files[0];
        },

        subirImagen() {
            let me = this;
            if (!me.imagenSeleccionada) return;

            me.subiendoImagen = true;

            let formData = new FormData();
            formData.append('image', me.imagenSeleccionada);

            axios.post(`/admin/products/${me.productoImagenes.id}/upload-image`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(function(response) {
                if (response.data.ok) {
                    me.productoImagenes = response.data.product;
                    me.imagenSeleccionada = null;
                    me.$refs.inputImagen.value = '';
                    me.loadProducts(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                let msg = 'Error al subir imagen';
                if (error.response && error.response.data.message) {
                    msg = error.response.data.message;
                }
                Swal.fire('Error', msg, 'error');
            }).finally(function() {
                me.subiendoImagen = false;
            });
        },

        eliminarImagenPrincipal() {
            let me = this;
            Swal.fire({
                title: '¿Eliminar imagen principal?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminandoImagen = true;
                    axios.delete(`/admin/products/${me.productoImagenes.id}/delete-main-image`).then(function(response) {
                        if (response.data.ok) {
                            me.productoImagenes = response.data.product;
                            me.loadProducts(me.pagination.current_page || 1);
                            Swal.fire('Eliminada', response.data.message, 'success');
                        }
                    }).catch(function(error) {
                        Swal.fire('Error', 'Error al eliminar imagen', 'error');
                    }).finally(function() {
                        me.eliminandoImagen = false;
                    });
                }
            });
        },

        eliminarImagenAlternativa(imageId) {
            let me = this;
            Swal.fire({
                title: '¿Eliminar esta imagen?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminandoImagen = true;
                    axios.delete(`/admin/products/delete-alt-image/${imageId}`).then(function(response) {
                        if (response.data.ok) {
                            me.productoImagenes = response.data.product;
                            me.loadProducts(me.pagination.current_page || 1);
                            Swal.fire('Eliminada', response.data.message, 'success');
                        }
                    }).catch(function(error) {
                        Swal.fire('Error', 'Error al eliminar imagen', 'error');
                    }).finally(function() {
                        me.eliminandoImagen = false;
                    });
                }
            });
        },

        // Visor de imágenes
        verImagen(imagePath) {
            if (imagePath) {
                this.$viewImage(imagePath);
            }
        },

        verGaleriaProducto(indexAlternativa) {
            // Construir array con imagen principal + alternativas
            let imagenes = [];

            if (this.productoImagenes.image) {
                imagenes.push(this.productoImagenes.image);
            }

            if (this.productoImagenes.images && this.productoImagenes.images.length > 0) {
                this.productoImagenes.images.forEach(img => {
                    imagenes.push(img.image);
                });
            }

            // El índice debe considerar si hay imagen principal
            let startIndex = this.productoImagenes.image ? indexAlternativa + 1 : indexAlternativa;
            this.$viewImages(imagenes, startIndex);
        },

        confirmarEliminar(product) {
            let me = this;
            Swal.fire({
                title: '¿Eliminar producto?',
                text: product.name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminarProducto(product.id);
                }
            });
        },

        eliminarProducto(id) {
            let me = this;
            axios.delete(`/admin/products/${id}`).then(function(response) {
                if (response.data.ok) {
                    me.loadProducts(me.pagination.current_page || 1);
                    Swal.fire('Eliminado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al eliminar producto', 'error');
            });
        },

        activarProducto(id) {
            let me = this;
            axios.put(`/admin/products/${id}/activate`).then(function(response) {
                if (response.data.ok) {
                    me.loadProducts(me.pagination.current_page || 1);
                    Swal.fire('Activado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al activar producto', 'error');
            });
        },

        desactivarProducto(id) {
            let me = this;
            axios.put(`/admin/products/${id}/deactivate`).then(function(response) {
                if (response.data.ok) {
                    me.loadProducts(me.pagination.current_page || 1);
                    Swal.fire('Desactivado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al desactivar producto', 'error');
            });
        }
    }
}
</script>

<style>
    .modal-content{
        width: 100% !important;
        position: absolute !important;
    }
    .mostrar{
        display: list-item !important;
        opacity: 1 !important;
        position: fixed !important;
        background-color: #3c29297a !important;
        overflow: scroll;
    }

    /* Imagen del producto en cards */
    .product-image-container {
        width: 100%;
        height: 150px;
        overflow: hidden;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }

    /* Estilos para Cards de Productos */
    .product-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .product-card.inactive-card {
        opacity: 0.7;
        background-color: #f8f9fa;
    }

    .product-card .card-header {
        background: linear-gradient(135deg, #00F5A0 0%, #00D9F5 100%);
        color: #0D1117;
        border: none;
        padding: 0.75rem 1rem;
    }

    .product-card.inactive-card .card-header {
        background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
        color: white;
    }

    .product-key {
        font-weight: bold;
        font-size: 0.85rem;
    }

    .product-name {
        color: #2c3e50;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }

    .product-info {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .product-info .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #666;
    }

    .product-info .info-item i {
        width: 18px;
        text-align: center;
    }

    .product-card .card-footer {
        background: transparent;
        border-top: 1px solid #eee;
        padding: 0.5rem 1rem;
    }
</style>
