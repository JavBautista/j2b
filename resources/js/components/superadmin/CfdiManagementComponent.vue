<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-file-text-o" style="color: var(--j2b-primary);"></i> Facturacion CFDI
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Gestion de timbres fiscales por tienda</p>
            </div>
        </div>

        <!-- Card Timbres Globales -->
        <div class="j2b-card mb-4">
            <div class="j2b-card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0" style="font-weight: 600; color: var(--j2b-dark);">
                        <i class="fa fa-ticket" style="color: var(--j2b-primary);"></i> Timbres Globales (HUB CFDI)
                    </h6>
                    <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="loadTimbresGlobales()" :disabled="timbresLoading">
                        <i class="fa" :class="timbresLoading ? 'fa-spinner fa-spin' : 'fa-refresh'"></i> Actualizar
                    </button>
                </div>
            </div>
            <div class="j2b-card-body">
                <div v-if="timbresLoading" class="text-center py-3">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color: var(--j2b-primary);"></i>
                </div>
                <div v-else-if="timbresError" class="text-center py-3">
                    <i class="fa fa-exclamation-triangle fa-2x" style="color: var(--j2b-warning);"></i>
                    <p class="mt-2 mb-0" style="color: var(--j2b-gray-500);">{{ timbresError }}</p>
                </div>
                <div v-else class="row">
                    <div class="col-md-4 mb-2">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-number">{{ timbresGlobales.contratados ?? '-' }}</div>
                            <div class="stat-label">Contratados</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-number">{{ timbresGlobales.consumidos ?? '-' }}</div>
                            <div class="stat-label">Consumidos</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="stat-card stat-card-success">
                            <div class="stat-number">{{ timbresGlobales.disponibles ?? '-' }}</div>
                            <div class="stat-label">Disponibles</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Tabla Tiendas CFDI -->
        <div class="j2b-card">
            <div class="j2b-card-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="j2b-input-icon" style="flex: 1; min-width: 200px;">
                                <i class="fa fa-search"></i>
                                <input type="text" v-model="buscar" class="j2b-input" placeholder="Buscar tienda..." @keyup.enter="loadShops(1)">
                            </div>
                            <select class="j2b-select" style="width: auto; min-width: 160px;" v-model="estatus">
                                <option value="">Todas</option>
                                <option value="cfdi_active">CFDI Habilitado</option>
                                <option value="cfdi_inactive">CFDI No habilitado</option>
                                <option value="configured">Con emisor configurado</option>
                            </select>
                            <button type="button" @click="loadShops(1)" class="j2b-btn j2b-btn-primary">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <span class="j2b-badge j2b-badge-info">{{ pagination.total }} tiendas</span>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="j2b-card-body p-0">
                <div class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Tienda</th>
                                <th style="width: 100px;">CFDI</th>
                                <th style="width: 120px;">Timbres</th>
                                <th style="width: 140px;">Emisor</th>
                                <th style="width: 130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="shop in arrayShops" :key="shop.id">
                                <td>
                                    <span class="j2b-badge j2b-badge-dark">{{ shop.id }}</span>
                                </td>
                                <td>
                                    <strong style="color: var(--j2b-dark);">{{ shop.name }}</strong>
                                </td>
                                <td>
                                    <span v-if="shop.cfdi_enabled" class="j2b-badge j2b-badge-success" style="cursor: pointer;" @click="toggleCfdi(shop)">
                                        <i class="fa fa-check-circle"></i> Activo
                                    </span>
                                    <span v-else class="j2b-badge j2b-badge-danger" style="cursor: pointer; opacity: 0.7;" @click="toggleCfdi(shop)">
                                        <i class="fa fa-times-circle"></i> Inactivo
                                    </span>
                                </td>
                                <td>
                                    <span class="j2b-badge j2b-badge-info">
                                        {{ shop.cfdi_timbres_contratados }}
                                    </span>
                                    <span v-if="shop.cfdi_emisor" style="color: var(--j2b-gray-500); font-size: 12px;">
                                        ({{ shop.cfdi_emisor.timbres_usados }} usados)
                                    </span>
                                </td>
                                <td>
                                    <span v-if="shop.cfdi_emisor" class="j2b-badge j2b-badge-success">
                                        <i class="fa fa-check"></i> {{ shop.cfdi_emisor.rfc }}
                                    </span>
                                    <span v-else style="color: var(--j2b-gray-400); font-size: 12px;">
                                        <i class="fa fa-clock-o"></i> Pendiente
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-primary" @click="abrirModalAsignar(shop)" title="Asignar Timbres">
                                            <i class="fa fa-ticket"></i>
                                        </button>
                                        <button v-if="shop.cfdi_emisor" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="verDetalle(shop)" title="Ver Emisor">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="arrayShops.length === 0">
                                <td colspan="6" class="text-center py-5">
                                    <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                    <p style="color: var(--j2b-gray-500);">No se encontraron tiendas</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginacion -->
                <div class="d-flex justify-content-between align-items-center p-3" style="border-top: 1px solid var(--j2b-gray-200);">
                    <small style="color: var(--j2b-gray-500);">
                        Mostrando {{ pagination.from || 0 }} - {{ pagination.to || 0 }} de {{ pagination.total || 0 }}
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page - 1)">
                                    <i class="fa fa-chevron-left"></i>
                                </a>
                            </li>
                            <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page == isActived ? 'active' : '']">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(page)" v-text="page"></a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page + 1)">
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalle Emisor -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalDetalle}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-file-text-o" style="color: var(--j2b-primary);"></i>
                        Emisor de {{ shopDetalle ? shopDetalle.name : '' }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModalDetalle()" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body" v-if="shopDetalle && shopDetalle.cfdi_emisor">
                    <div class="j2b-form-section">
                        <h6 class="j2b-form-section-title">
                            <i class="fa fa-building"></i> Datos Fiscales
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="j2b-form-group">
                                    <label class="j2b-label">RFC</label>
                                    <input type="text" class="j2b-input" :value="shopDetalle.cfdi_emisor.rfc" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="j2b-form-group">
                                    <label class="j2b-label">Razon Social</label>
                                    <input type="text" class="j2b-input" :value="shopDetalle.cfdi_emisor.razon_social" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="j2b-form-group">
                                    <label class="j2b-label">Registrado HUB</label>
                                    <span v-if="shopDetalle.cfdi_emisor.is_registered" class="j2b-badge j2b-badge-success">Si</span>
                                    <span v-else class="j2b-badge j2b-badge-danger">No</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="j2b-form-group">
                                    <label class="j2b-label">Timbres Asignados</label>
                                    <strong>{{ shopDetalle.cfdi_emisor.timbres_asignados }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="j2b-form-group">
                                    <label class="j2b-label">Timbres Usados</label>
                                    <strong>{{ shopDetalle.cfdi_emisor.timbres_usados }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModalDetalle()">
                        <i class="fa fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Asignar Timbres -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalAsignar}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-ticket" style="color: var(--j2b-primary);"></i>
                        Asignar Timbres
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModalAsignar()" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body" v-if="shopAsignar">
                    <div class="j2b-form-section">
                        <div class="mb-3">
                            <strong>Tienda:</strong> {{ shopAsignar.name }}
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small style="color: var(--j2b-gray-500);">Timbres contratados actual:</small>
                                <strong class="d-block" style="font-size: 20px;">{{ shopAsignar.cfdi_timbres_contratados }}</strong>
                            </div>
                            <div class="col-md-6" v-if="shopAsignar.cfdi_emisor">
                                <small style="color: var(--j2b-gray-500);">Usados por emisor:</small>
                                <strong class="d-block" style="font-size: 20px; color: var(--j2b-warning);">{{ shopAsignar.cfdi_emisor.timbres_usados }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="j2b-form-group mt-3">
                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Cantidad de timbres a agregar</label>
                        <input type="number" class="j2b-input" v-model.number="cantidadTimbres" min="1" placeholder="Ej: 10">
                        <small style="color: var(--j2b-gray-500);">Se sumaran a los {{ shopAsignar.cfdi_timbres_contratados }} timbres actuales</small>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModalAsignar()">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="confirmarAsignarTimbres()" :disabled="asignando">
                        <i class="fa" :class="asignando ? 'fa-spinner fa-spin' : 'fa-check'"></i> Asignar
                    </button>
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
            arrayShops: [],
            pagination: {
                'total': 0,
                'current_page': 0,
                'per_page': 0,
                'last_page': 0,
                'from': 0,
                'to': 0
            },
            offset: 3,
            buscar: '',
            estatus: '',

            // Timbres globales
            timbresGlobales: {},
            timbresLoading: false,
            timbresError: null,

            // Modal detalle
            modalDetalle: 0,
            shopDetalle: null,

            // Modal asignar
            modalAsignar: 0,
            shopAsignar: null,
            cantidadTimbres: null,
            asignando: false,
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
    methods: {
        cambiarPagina(page) {
            this.pagination.current_page = page;
            this.loadShops(page);
        },
        loadShops(page) {
            let me = this;
            var url = '/superadmin/cfdi/shops?page=' + page + '&buscar=' + me.buscar + '&estatus=' + me.estatus;
            axios.get(url).then(function(response) {
                var respuesta = response.data;
                me.arrayShops = respuesta.shops.data;
                me.pagination = respuesta.pagination;
            }).catch(function(error) {
                console.log(error);
            });
        },
        loadTimbresGlobales() {
            let me = this;
            me.timbresLoading = true;
            me.timbresError = null;
            axios.get('/superadmin/cfdi/timbres-globales').then(function(response) {
                me.timbresLoading = false;
                if (response.data.ok) {
                    me.timbresGlobales = response.data.data;
                } else {
                    me.timbresError = response.data.error || 'Error desconocido';
                }
            }).catch(function(error) {
                me.timbresLoading = false;
                me.timbresError = 'No se pudo conectar con HUB CFDI';
                console.log(error);
            });
        },
        toggleCfdi(shop) {
            let me = this;
            let accion = shop.cfdi_enabled ? 'deshabilitar' : 'habilitar';

            Swal.fire({
                title: '¿' + accion.charAt(0).toUpperCase() + accion.slice(1) + ' CFDI?',
                text: shop.name,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, ' + accion,
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post('/superadmin/cfdi/toggle', {
                        shop_id: shop.id
                    }).then(function(response) {
                        if (response.data.ok) {
                            me.loadShops(me.pagination.current_page);
                            Swal.fire('Exito', response.data.message, 'success');
                        }
                    }).catch(function(error) {
                        console.log(error);
                        Swal.fire('Error', 'No se pudo actualizar', 'error');
                    });
                }
            });
        },
        verDetalle(shop) {
            this.shopDetalle = shop;
            this.modalDetalle = 1;
        },
        cerrarModalDetalle() {
            this.modalDetalle = 0;
            this.shopDetalle = null;
        },
        abrirModalAsignar(shop) {
            this.shopAsignar = shop;
            this.cantidadTimbres = null;
            this.modalAsignar = 1;
        },
        cerrarModalAsignar() {
            this.modalAsignar = 0;
            this.shopAsignar = null;
            this.cantidadTimbres = null;
        },
        confirmarAsignarTimbres() {
            if (!this.cantidadTimbres || this.cantidadTimbres < 1) {
                Swal.fire('Error', 'Ingrese una cantidad valida', 'error');
                return;
            }

            let me = this;
            me.asignando = true;

            axios.post('/superadmin/cfdi/asignar-timbres-shop', {
                shop_id: me.shopAsignar.id,
                cantidad: me.cantidadTimbres,
            }).then(function(response) {
                me.asignando = false;
                if (response.data.ok) {
                    me.cerrarModalAsignar();
                    me.loadShops(me.pagination.current_page);
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(function(error) {
                me.asignando = false;
                console.log(error);
                Swal.fire('Error', 'Ocurrio un error al asignar timbres', 'error');
            });
        },
    },
    mounted() {
        this.loadShops(1);
        this.loadTimbresGlobales();
    }
}
</script>

<style scoped>
    .mostrar {
        display: block !important;
        opacity: 1 !important;
        position: fixed !important;
        background-color: rgba(26, 26, 46, 0.8) !important;
        overflow-y: auto;
        z-index: 1050;
    }
</style>
