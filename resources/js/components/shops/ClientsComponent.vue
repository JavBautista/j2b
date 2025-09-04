<template>
<div>
    <div class="container-fluid">
        <!-- Tabla de clientes -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-users"></i> Clientes {{ shop.name }}
                <button type="button" @click="abrirModal('client','registrar')" class="btn btn-primary">
                    <i class="icon-plus"></i>&nbsp;Nuevo Cliente
                </button>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <select class="form-control col-md-3" v-model="criterio">
                                <option value="name">Nombre</option>
                                <option value="email">Email</option>
                            </select>
                            <input type="text" v-model="buscar" class="form-control" placeholder="Texto a buscar" @keyup.enter="loadClients(1,buscar,criterio,estatus)">
                            <select class="form-control col-md-3" v-model="estatus">
                                <option value="">TODOS</option>
                                <option value="active">ACTIVOS</option>
                                <option value="inactive">INACTIVOS</option>
                            </select>
                            <button type="submit" @click="loadClients(1,buscar,criterio,estatus)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>
                <!-- Grid de Cards de Clientes -->
                <div class="row">
                    <div class="col-md-4 col-lg-3 mb-4" v-for="client in arrayClients" :key="client.id">
                        <div class="card client-card h-100" :class="{'inactive-card': !client.active}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="client-id">#{{ client.id }}</span>
                                    <span v-if="client.active" class="badge badge-success ml-2">Activo</span>
                                    <span v-else class="badge badge-danger ml-2">Inactivo</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-light dropdown-toggle" 
                                            type="button" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click="abrirModal('client','actualizar_datos', client)">
                                            <i class="fa fa-edit text-info"></i> Editar Cliente
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click="abrirModalDirecciones(client)">
                                            <i class="fa fa-map-marker text-warning"></i> Gestionar Direcciones
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" :href="`/admin/clients/${client.id}/contracts`">
                                            <i class="fa fa-folder-open text-primary"></i> Ver Contratos
                                        </a></li>
                                        <li><a class="dropdown-item" :href="`/admin/clients/${client.id}/assign-contract`">
                                            <i class="fa fa-plus text-success"></i> Crear Contrato
                                        </a></li>
                                        <li><a class="dropdown-item" :href="`/admin/clients/${client.id}/receipts`">
                                            <i class="fa fa-receipt text-info"></i> Ver Recibos
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <!-- 🔥 TEMPORAL: Botón de prueba FCM -->
                                        <li><a class="dropdown-item" href="#" @click="testFCMForClient(client)">
                                            <i class="fa fa-mobile text-danger"></i> 🔥 Test FCM Push
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <template v-if="client.active">
                                            <li><a class="dropdown-item" href="#" @click="actualizarAInactivo(client.id)">
                                                <i class="fa fa-toggle-off text-danger"></i> Desactivar Cliente
                                            </a></li>
                                        </template>
                                        <template v-else>
                                            <li><a class="dropdown-item" href="#" @click="actualizarAActivo(client.id)">
                                                <i class="fa fa-toggle-on text-success"></i> Activar Cliente
                                            </a></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title client-name">
                                    <i class="fa fa-user text-primary"></i>
                                    {{ client.name }}
                                </h5>
                                <div class="client-info">
                                    <div class="info-item" v-if="client.email">
                                        <i class="fa fa-envelope text-muted"></i>
                                        <span>{{ client.email }}</span>
                                    </div>
                                    <div class="info-item" v-if="client.company">
                                        <i class="fa fa-building text-muted"></i>
                                        <span>{{ client.company }}</span>
                                    </div>
                                    <div class="info-item" v-if="client.movil">
                                        <i class="fa fa-phone text-muted"></i>
                                        <span>{{ client.movil }}</span>
                                    </div>
                                    <div class="info-item" v-if="client.address">
                                        <i class="fa fa-map-marker text-muted"></i>
                                        <span>{{ client.address }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent text-center">
                                <small class="text-muted">
                                    <i class="fa fa-clock-o"></i> 
                                    Cliente #{{ client.id }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mostrar mensaje si no hay clientes -->
                <div v-if="arrayClients.length === 0" class="text-center py-5">
                    <i class="fa fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron clientes con los criterios de búsqueda.</p>
                </div>
                <nav>
                    <ul class="pagination">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1,buscar,criterio)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page==isActived ? 'active':'']">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page,buscar,criterio)" v-text="page"></a>
                        </li>
                        <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page+1,buscar,criterio)">Sig</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Componente de direcciones -->
    <client-addresses-component ref="clientAddresses"></client-addresses-component>

    <!-- Modal agregar/actualizar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modal}" role="dialog" aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-primary modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" v-text="tituloModal"></h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form v-on:submit.prevent action="" method="post" enctype="multipart/form-data" class="form-horizontal">
                        <div v-show="errorClient" class="form-group row div-error">
                            <div class="container-fluid">
                                <div class="alert alert-danger text-center">
                                    <div v-for="error in errorMostrarMsjClient" :key="error" v-text="error"></div>
                                </div>
                            </div>
                        </div>
                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>
                        
                        <div v-if="tipoAccion==1 || tipoAccion==2">
                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right"><strong class="text text-danger">*</strong> Nombre</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="name" placeholder="Nombre completo" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="email" class="col-md-4 col-form-label text-md-right"><strong class="text text-danger">*</strong> Email</label>
                                <div class="col-md-6">
                                    <input type="email" class="form-control" v-model="email" placeholder="correo@ejemplo.com" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="company" class="col-md-4 col-form-label text-md-right">Empresa</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="company" placeholder="Nombre de la empresa">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="movil" class="col-md-4 col-form-label text-md-right">Teléfono</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="movil" placeholder="Número de teléfono">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="address" class="col-md-4 col-form-label text-md-right">Dirección</label>
                                <div class="col-md-6">
                                    <textarea class="form-control" v-model="address" placeholder="Dirección completa" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="level" class="col-md-4 col-form-label text-md-right">Nivel</label>
                                <div class="col-md-6">
                                    <select class="form-control" v-model="level">
                                        <option value="1">Básico</option>
                                        <option value="2">Premium</option>
                                        <option value="3">VIP</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                    <button type="button" v-if="tipoAccion==1" class="btn btn-primary" @click="registrar()">Guardar</button>
                    <button type="button" v-if="tipoAccion==2" class="btn btn-primary" @click="actualizarDatos()">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

</div>
</template>

<script>
import ClientAddressesComponent from './ClientAddressesComponent.vue';

export default {
        components: {
            ClientAddressesComponent
        },
        props: ['shop'],
        data(){
            return {
                arrayClients:[],
                pagination:{
                    'total':0,
                    'current_page':0,
                    'per_page':0,
                    'last_page':0,
                    'from':0,
                    'to':0
                },
                offset:3,
                criterio:'name',
                buscar:'',
                estatus:'active',

                client_id:0,
                name:'',
                email:'',
                company:'',
                movil:'',
                address:'',
                level:1,

                errors:[],
                modal:0,
                tituloModal:'',
                tipoAccion:0,
                errorClient:0,
                errorMostrarMsjClient:[],
            }
        },
        computed:{
            isActived: function(){
                return this.pagination.current_page;
            },
            pagesNumber: function(){
                if(!this.pagination.to){
                    return [];
                }
                var from = this.pagination.current_page - this.offset;
                if(from <1){
                    from=1;
                }

                var to = from + (this.offset * 2);
                if(to >= this.pagination.last_page){
                    to = this.pagination.last_page;
                }

                var pagesArray = [];
                while(from <= to ){
                    pagesArray.push(from);
                    from++;
                }
                return pagesArray;
            }
        },
        methods : {
            loadClients(page,buscar,criterio,estatus){
                let me=this;
                var url = '/admin/clients/get?page='+page+'&buscar='+buscar+'&criterio='+criterio+'&estatus='+estatus;
                axios.get(url).then(function (response){
                    var respuesta = response.data;
                    me.arrayClients = respuesta.clients.data;
                    me.pagination = respuesta.pagination;
                    console.log(me.arrayClients);
                })
                .catch(function (error) {
                    console.log(error);
                })
                .finally(function () {
                });
            },
            cambiarPagina(page,buscar,criterio,estatus){
                let me = this;
                me.pagination.current_page = page;
                me.loadClients(page,buscar,criterio,estatus);
            },
            actualizarAActivo(id){
                const swalWithBootstrapButtons = Swal.mixin({
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                })

                swalWithBootstrapButtons.fire({
                    title: '¿Desea activar este cliente?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Aceptar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        let me=this;
                        axios.put('/admin/clients/active',{
                            'id': id
                        }).then(function (response){
                            me.loadClients(me.pagination.current_page,me.buscar,me.criterio,me.estatus);
                            swalWithBootstrapButtons.fire(
                                '¡Activado!',
                                'Cliente activado exitosamente.',
                                'success'
                            )
                        }).catch(function (error){
                            console.log(error);
                        });
                    }
                })
            },
            actualizarAInactivo(id){
                const swalWithBootstrapButtons = Swal.mixin({
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                })

                swalWithBootstrapButtons.fire({
                    title: '¿Desea desactivar este cliente?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Aceptar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        let me=this;
                        axios.put('/admin/clients/inactive',{
                            'id': id
                        }).then(function (response){
                            me.loadClients(me.pagination.current_page,me.buscar,me.criterio,me.estatus);
                            swalWithBootstrapButtons.fire(
                                '¡Desactivado!',
                                'Cliente desactivado exitosamente.',
                                'success'
                            )
                        }).catch(function (error){
                            console.log(error);
                        });
                    }
                })
            },
            registrar(){
                if(this.validarDatos('registrar')){
                    return;
                }
                let me=this;
                axios.post('/admin/clients/store',{
                    'name':me.name,
                    'email':me.email,
                    'company':me.company,
                    'movil':me.movil,
                    'address':me.address,
                    'level':me.level,
                }).then(function (response){
                    console.log(response)
                    me.cerrarModal();
                    me.loadClients(me.pagination.current_page,me.buscar,me.criterio,me.estatus)
                    Swal.fire(
                        'Éxito!',
                        'Cliente agregado correctamente.',
                        'success'
                    );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrió un error al guardar, consulte al administrador del sistema.',
                        'error'
                    );
                });
            },
            actualizarDatos(){
                if(this.validarDatos('actualizar')){
                    return;
                }

                let me=this;
                axios.put('/admin/clients/update',{
                    'client_id':me.client_id,
                    'name':me.name,
                    'email':me.email,
                    'company':me.company,
                    'movil':me.movil,
                    'address':me.address,
                    'level':me.level,
                }).then(function (response){
                    console.log(response)
                    me.cerrarModal();
                    me.loadClients(me.pagination.current_page,me.buscar,me.criterio,me.estatus)
                    Swal.fire(
                        'Éxito!',
                        'Cliente actualizado correctamente.',
                        'success'
                    );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrió un error al actualizar, consulte al administrador del sistema.',
                        'error'
                    );
                });
            },
            validarDatos(accion){
                this.errorClient=0;
                this.errorMostrarMsjClient=[];
                if(accion=='registrar' || accion=='actualizar'){
                    if(!this.name) this.errorMostrarMsjClient.push('El nombre no puede estar vacío.');
                    if(!this.email) this.errorMostrarMsjClient.push('El email no puede estar vacío.');
                }
                if(this.errorMostrarMsjClient.length)
                {
                    this.errorClient=1;
                    Swal.fire({
                        title: 'Alerta',
                        text: 'Ingrese todos los campos requeridos',
                        icon: 'error',
                    });
                }
                return this.errorClient;
            },
            abrirModal(modelo, accion, data=[]){
                switch(modelo){
                    case "client":{
                        switch(accion){
                            case 'registrar':{
                                this.modal=1;
                                this.tipoAccion =1;
                                this.errorClient=0;
                                this.tituloModal='Agregar Cliente';
                                this.name='';
                                this.email ='';
                                this.company ='';
                                this.movil ='';
                                this.address ='';
                                this.level=1;
                                break;
                            }
                            case 'actualizar_datos':{
                                this.modal=1;
                                this.tipoAccion =2;
                                this.errorClient=0;
                                this.tituloModal='Actualizar Cliente';

                                this.client_id = data['id'];
                                this.name = data['name'];
                                this.email = data['email'];
                                this.company = data['company'];
                                this.movil = data['movil'];
                                this.address = data['address'];
                                this.level = data['level'];
                                break;
                            }
                        }
                    }
                }
            },
            cerrarModal(){
                this.modal=0;
                this.tituloModal='';
            },
            abrirModalDirecciones(client) {
                this.$refs.clientAddresses.abrirModal(client);
            },
            // 🔥 TEMPORAL: Método para probar FCM con cliente específico
            testFCMForClient(client) {
                if (!confirm(`¿Crear servicio de prueba FCM para ${client.name}?`)) {
                    return;
                }

                const form = new FormData();
                form.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                form.append('client_id', client.id);

                fetch('/admin/test-create-service-client', {
                    method: 'POST',
                    body: form
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '✅ FCM Test Exitoso',
                            html: data.message,
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '❌ Error FCM Test',
                            text: data.message,
                            showConfirmButton: true
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor',
                        showConfirmButton: true
                    });
                });
            },
        },
        mounted() {
            this.loadClients(1,'','name','active');
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

    .div-error{
        display: flex;
        justify-content: center;
    }

    .text-error{
        color: red !important;
        font-weight: bold;
    }

    /* Estilos para Cards de Clientes */
    .client-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .client-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .client-card.inactive-card {
        opacity: 0.7;
        background-color: #f8f9fa;
    }

    .client-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1rem;
    }

    .client-card.inactive-card .card-header {
        background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
    }

    .client-id {
        font-weight: bold;
        font-size: 0.9rem;
    }

    .client-name {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .client-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #6c757d;
    }

    .info-item i {
        width: 16px;
        text-align: center;
    }

    .info-item span {
        flex: 1;
        word-break: break-word;
    }

    .client-card .card-footer {
        border-top: 1px solid #e9ecef;
        padding: 0.75rem 1rem;
    }

    .client-card .btn-group .btn {
        border-radius: 6px;
        font-size: 0.85rem;
        padding: 0.375rem 0.5rem;
    }

    .client-card .btn-group .btn:not(:last-child) {
        margin-right: 0.25rem;
    }

    .client-card .dropdown-menu {
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
    }

    .client-card .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .client-card .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .col-md-4 {
            margin-bottom: 1rem;
        }
        
        .client-card .btn-group .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.4rem;
        }
    }

    /* Paginación moderna */
    .pagination {
        justify-content: center;
        margin-top: 2rem;
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
</style>
