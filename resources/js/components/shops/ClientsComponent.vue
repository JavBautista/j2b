<template>
<div>
    <div class="container-fluid">
        <!-- Tabla de clientes -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-users"></i> Clientes de {{ shop.name }}
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
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Empresa</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="client in arrayClients" :key="client.id">
                            <td v-text="client.id"></td>
                            <td v-text="client.name"></td>
                            <td v-text="client.email"></td>
                            <td v-text="client.company || '-'"></td>
                            <td v-text="client.movil || '-'"></td>
                            <td>
                                <span v-if="client.active" class="badge badge-success">Activo</span>
                                <span v-else class="badge badge-danger">Inactivo</span>
                            </td>
                            <td>
                                <template v-if="client.active">
                                    <button type="button" class="btn btn-info btn-sm" @click="actualizarAInactivo(client.id)" title="Desactivar">
                                        <i class="fa fa-toggle-on"></i>
                                    </button>
                                </template>
                                <template v-else>
                                    <button type="button" class="btn btn-secondary btn-sm" @click="actualizarAActivo(client.id)" title="Activar">
                                        <i class="fa fa-toggle-off"></i>
                                    </button>
                                </template>
                                <button class="btn btn-info btn-sm" @click="abrirModal('client','actualizar_datos', client)" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <a :href="`/admin/clients/${client.id}/contracts`" class="btn btn-primary btn-sm" title="Ver Contratos">
                                    <i class="fa fa-folder-open"></i>
                                </a>
                                <a :href="`/admin/clients/${client.id}/assign-contract`" class="btn btn-success btn-sm" title="Crear Contrato">
                                    <i class="fa fa-plus"></i>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
    export default {
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
</style>
