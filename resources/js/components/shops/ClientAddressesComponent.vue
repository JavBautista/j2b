<template>
<div>
    <!-- Modal para gestionar direcciones -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':showModal}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <i class="fa fa-map-marker"></i> Direcciones de {{ client.name }}
                    </h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Botón agregar nueva dirección -->
                    <div class="mb-3">
                        <button type="button" @click="abrirFormulario('crear')" class="btn btn-success">
                            <i class="fa fa-plus"></i> Nueva Dirección
                        </button>
                    </div>

                    <!-- Grid de Cards de Direcciones -->
                    <div v-if="addresses.length > 0">
                        <div class="row">
                            <div class="col-md-6 col-lg-4 mb-3" v-for="address in addresses" :key="address.id">
                                <div class="card address-card h-100" :class="{'inactive-card': !address.active}">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">
                                                <i class="fa fa-map-marker text-warning"></i>
                                                {{ address.name || 'Dirección sin nombre' }}
                                            </h6>
                                        </div>
                                        <div>
                                            <span v-if="address.is_primary" class="badge badge-success">Principal</span>
                                            <span v-if="address.active" class="badge badge-info ml-1">Activa</span>
                                            <span v-else class="badge badge-secondary ml-1">Inactiva</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Información de dirección -->
                                        <div class="address-info mb-3">
                                            <div class="info-item mb-2">
                                                <i class="fa fa-home text-muted"></i>
                                                <span>{{ getFullAddress(address) }}</span>
                                            </div>
                                            <div class="info-item mb-2">
                                                <i class="fa fa-map text-muted"></i>
                                                <span>{{ address.city }}, {{ address.state }}</span>
                                            </div>
                                            <div class="info-item mb-2" v-if="address.postal_code">
                                                <i class="fa fa-envelope text-muted"></i>
                                                <span>CP: {{ address.postal_code }}</span>
                                            </div>
                                            <div class="info-item mb-2" v-if="address.phone">
                                                <i class="fa fa-phone text-muted"></i>
                                                <span>{{ address.phone }}</span>
                                            </div>
                                            <div class="info-item mb-2" v-if="address.description">
                                                <i class="fa fa-info-circle text-muted"></i>
                                                <span>{{ address.description }}</span>
                                            </div>
                                        </div>

                                        <!-- Coordenadas y mapa -->
                                        <div v-if="address.latitude && address.longitude" class="coordinates-info mb-3">
                                            <small class="text-muted">
                                                <i class="fa fa-globe"></i>
                                                {{ address.latitude }}, {{ address.longitude }}
                                            </small>
                                            <button class="btn btn-outline-primary btn-sm ml-2" 
                                                    @click="openGoogleMaps(address)"
                                                    title="Ver en Google Maps">
                                                <i class="fa fa-map"></i> Ver en Mapa
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="btn-group w-100" role="group">
                                            <button v-if="address.active" 
                                                    class="btn btn-outline-info btn-sm" 
                                                    @click="abrirFormulario('editar', address)" 
                                                    title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button v-if="address.active" 
                                                    class="btn btn-outline-danger btn-sm" 
                                                    @click="desactivarDireccion(address.id)" 
                                                    title="Desactivar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <button v-else 
                                                    class="btn btn-outline-success btn-sm" 
                                                    @click="activarDireccion(address.id)" 
                                                    title="Activar">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="alert alert-info">
                        No hay direcciones registradas para este cliente.
                    </div>

                    <!-- Formulario para agregar/editar dirección -->
                    <div v-if="showForm" class="card mt-3">
                        <div class="card-header">
                            <h5>{{ formMode === 'crear' ? 'Nueva Dirección' : 'Editar Dirección' }}</h5>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="guardarDireccion">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Nombre de la ubicación</label>
                                            <input type="text" v-model="form.name" class="form-control" placeholder="Ej: Sucursal Centro">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Dirección *</label>
                                            <input type="text" v-model="form.address" class="form-control" required placeholder="Calle y número">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Número Ext.</label>
                                            <input type="text" v-model="form.num_ext" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Número Int.</label>
                                            <input type="text" v-model="form.num_int" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Colonia</label>
                                            <input type="text" v-model="form.colony" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Ciudad *</label>
                                            <input type="text" v-model="form.city" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Estado *</label>
                                            <input type="text" v-model="form.state" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Código Postal</label>
                                            <input type="text" v-model="form.postal_code" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Teléfono</label>
                                            <input type="text" v-model="form.phone" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" v-model="form.email" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea v-model="form.description" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" v-model="form.is_primary" class="form-check-input" id="isPrimary">
                                        <label class="form-check-label" for="isPrimary">
                                            Marcar como dirección principal
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success" :disabled="loading">
                                        <i v-if="loading" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-save"></i>
                                        {{ formMode === 'crear' ? 'Crear Dirección' : 'Actualizar Dirección' }}
                                    </button>
                                    <button type="button" @click="cancelarFormulario" class="btn btn-secondary ml-2">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
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
    name: 'ClientAddressesComponent',
    data() {
        return {
            showModal: false,
            showForm: false,
            formMode: 'crear', // 'crear' o 'editar'
            loading: false,
            client: {},
            addresses: [],
            form: {
                id: null,
                client_id: null,
                name: '',
                address: '',
                num_ext: '',
                num_int: '',
                colony: '',
                city: '',
                state: '',
                country: 'México',
                postal_code: '',
                latitude: null,
                longitude: null,
                description: '',
                phone: '',
                email: '',
                is_primary: false
            }
        }
    },
    methods: {
        abrirModal(client) {
            this.client = client;
            this.showModal = true;
            this.cargarDirecciones();
        },
        cerrarModal() {
            this.showModal = false;
            this.showForm = false;
            this.resetForm();
        },
        cargarDirecciones() {
            let me = this;
            axios.get('/admin/client-addresses/get', {
                params: {
                    client_id: this.client.id
                }
            }).then(function (response) {
                if (response.data.success) {
                    me.addresses = response.data.addresses;
                }
            }).catch(function (error) {
                console.log(error);
                Swal.fire('Error', 'Error al cargar las direcciones', 'error');
            });
        },
        abrirFormulario(modo, address = null) {
            this.formMode = modo;
            this.showForm = true;
            
            if (modo === 'editar' && address) {
                this.form = { ...address };
            } else {
                this.resetForm();
                this.form.client_id = this.client.id;
            }
        },
        cancelarFormulario() {
            this.showForm = false;
            this.resetForm();
        },
        resetForm() {
            this.form = {
                id: null,
                client_id: null,
                name: '',
                address: '',
                num_ext: '',
                num_int: '',
                colony: '',
                city: '',
                state: '',
                country: 'México',
                postal_code: '',
                latitude: null,
                longitude: null,
                description: '',
                phone: '',
                email: '',
                is_primary: false
            };
        },
        guardarDireccion() {
            let me = this;
            this.loading = true;

            let url = this.formMode === 'crear' ? '/admin/client-addresses/store' : '/admin/client-addresses/update';
            let method = this.formMode === 'crear' ? 'post' : 'put';

            axios[method](url, this.form)
                .then(function (response) {
                    if (response.data.success) {
                        Swal.fire('Éxito', response.data.message, 'success');
                        me.showForm = false;
                        me.resetForm();
                        me.cargarDirecciones();
                    }
                })
                .catch(function (error) {
                    console.log(error);
                    let message = 'Error al guardar la dirección';
                    if (error.response && error.response.data && error.response.data.errors) {
                        const errors = error.response.data.errors;
                        message = Object.values(errors).flat().join(', ');
                    }
                    Swal.fire('Error', message, 'error');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        desactivarDireccion(id) {
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            })

            swalWithBootstrapButtons.fire({
                title: '¿Desea desactivar esta dirección?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    let me = this;
                    axios.put('/admin/client-addresses/inactive', {
                        'id': id
                    }).then(function (response) {
                        if (response.data.success) {
                            me.cargarDirecciones();
                            swalWithBootstrapButtons.fire(
                                '¡Desactivada!',
                                'Dirección desactivada correctamente.',
                                'success'
                            )
                        }
                    }).catch(function (error) {
                        console.log(error);
                        Swal.fire('Error', 'Error al desactivar la dirección', 'error');
                    });
                }
            })
        },
        activarDireccion(id) {
            let me = this;
            axios.put('/admin/client-addresses/active', {
                'id': id
            }).then(function (response) {
                if (response.data.success) {
                    me.cargarDirecciones();
                    Swal.fire('Éxito', 'Dirección activada correctamente', 'success');
                }
            }).catch(function (error) {
                console.log(error);
                Swal.fire('Error', 'Error al activar la dirección', 'error');
            });
        },
        getFullAddress(address) {
            let parts = [address.address];
            if (address.num_ext) parts.push(`#${address.num_ext}`);
            if (address.colony) parts.push(address.colony);
            return parts.join(', ');
        },
        openGoogleMaps(address) {
            if (address.latitude && address.longitude) {
                // Crear URL de Google Maps con marcador
                const lat = address.latitude;
                const lng = address.longitude;
                const label = encodeURIComponent(address.name || 'Ubicación');
                const fullAddress = encodeURIComponent(this.getFullAddress(address));
                
                // URL que abre Google Maps con un marcador en las coordenadas específicas
                const googleMapsUrl = `https://www.google.com/maps?q=${lat},${lng}&ll=${lat},${lng}&z=16&t=m&marker=${lat},${lng}&title=${label}&address=${fullAddress}`;
                
                // Abrir en nueva pestaña
                window.open(googleMapsUrl, '_blank');
            } else {
                Swal.fire('Información', 'Esta dirección no tiene coordenadas registradas', 'info');
            }
        }
    }
}
</script>

<style scoped>
.modal-content {
    width: 100% !important;
    position: absolute !important;
}

.mostrar {
    display: list-item !important;
    opacity: 1 !important;
    position: fixed !important;
    background-color: #3c29297a !important;
    overflow: scroll;
}

/* Estilos para Cards de Direcciones */
.address-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.address-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.address-card.inactive-card {
    opacity: 0.7;
    background-color: #f8f9fa;
}

.address-card .card-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 1rem;
}

.address-card.inactive-card .card-header {
    background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
}

.address-card h6 {
    font-weight: 600;
    margin: 0;
}

.address-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: #495057;
    line-height: 1.4;
}

.info-item i {
    width: 16px;
    text-align: center;
    margin-top: 0.1rem;
    flex-shrink: 0;
}

.info-item span {
    flex: 1;
    word-break: break-word;
}

.coordinates-info {
    background-color: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px dashed #dee2e6;
    text-align: center;
}

.coordinates-info small {
    display: block;
    margin-bottom: 0.5rem;
    font-family: 'Courier New', monospace;
}

.address-card .card-footer {
    border-top: 1px solid #e9ecef;
    padding: 0.75rem 1rem;
}

.address-card .btn-group .btn {
    border-radius: 6px;
    font-size: 0.85rem;
    padding: 0.375rem 0.75rem;
    font-weight: 500;
}

.address-card .btn-group .btn:not(:last-child) {
    margin-right: 0.25rem;
}

/* Badges personalizados */
.badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
    border-radius: 6px;
}

.badge-success {
    background: linear-gradient(45deg, #28a745, #20c997);
}

.badge-info {
    background: linear-gradient(45deg, #17a2b8, #6f42c1);
}

.badge-secondary {
    background: linear-gradient(45deg, #6c757d, #adb5bd);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .address-card .btn-group .btn {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .info-item {
        font-size: 0.85rem;
    }
    
    .coordinates-info {
        padding: 0.5rem;
    }
}

/* Animación para hover en botón de mapa */
.btn-outline-primary:hover {
    background: linear-gradient(45deg, #007bff, #6f42c1);
    border-color: transparent;
    transform: translateY(-1px);
}

/* Modal más ancho para direcciones */
.modal-xl {
    max-width: 95%;
}

@media (min-width: 1200px) {
    .modal-xl {
        max-width: 1140px;
    }
}
</style>