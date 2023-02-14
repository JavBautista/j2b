<template>
<div>

  <div class="container-fluid">

        <!-- Ejemplo de tabla Listado -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-align-justify"></i> Vendedores
                <button type="button" @click="abrirModal('user','registrar')" class="btn btn-primary">
                    <i class="icon-plus"></i>&nbsp;Nuevo
                </button>
                <!--<a :href="'/admin/productos/nuevo'" class="btn btn-primary"><i class="icon-plus"></i>&nbsp;Nuevo</a>
                -->
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <select class="form-control col-md-3" v-model="criterio">
                              <option value="name">Nombre</option>
                              <option value="email">Email</option>
                            </select>
                            <input type="text" v-model="buscar" class="form-control" placeholder="Texto a buscar" @keyup.enter="loadUsers(1,buscar,criterio)">
                            <button type="submit" @click="loadUsers(1,buscar,criterio)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Tienda</th>
                            <th>Status</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                          <tr v-for="user in arrayUsers" :key="user.id">
                              <td v-text="user.id"></td>
                              <td v-text="user.name"></td>
                              <td v-text="user.email"></td>
                              <td v-text="user.shop.name"></td>


                              <td>
                                  <span v-if="user.active" class="badge badge-success">Activo</span>
                                  <span v-else class="badge badge-danger">Baja</span>
                              </td>

                              <td>
                                  <template v-if="user.active">
                                      <button type="button" class="btn btn-info" @click="actualizarAInactivo(user.id)"><i class="fa fa-toggle-on"></i>
                                      </button>
                                  </template>
                                  <template v-else>
                                      <button type="button" class="btn btn-secondary" @click="actualizarAActivo(user.id)"><i class="fa fa-toggle-off"></i>
                                      </button>
                                  </template>

                                  <button class="btn btn-info btn-sm" @click="abrirModal('user','actualizar_datos', user)" title="Editar"><i class="fa fa-edit"></i></button>
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
        <!-- Fin ejemplo de tabla Listado -->
  </div>

    <!--Inicio del modal agregar/actualizar-->
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

                        <div v-show="errorUser" class="form-group row div-error">
                          <div class="container-fluid">
                            <div class="alert alert-danger text-center">
                                <div v-for="error in errorMostrarMsjUser" :key="error" v-text="error">
                                </div>
                            </div>
                          </div>
                        </div>
                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>
                        <!--tipoAccion==1 o 2: Agregar o Actualizar-->
                        <div v-if="tipoAccion==1 || tipoAccion==2">
                        <!--<div v-else>-->
                          <div class="form-group row">
                            <label for="shop_id" class="col-md-4 col-form-label text-md-right">Seleccione tienda</label>
                            <div class="col-md-6">
                              <select class="form-control" v-model="shop_id">
                                <option v-for="shop in arrayShops" :key="shop.id" :value="shop.id" v-text="shop.name"></option>
                              </select>
                            </div>
                          </div>
                          <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right"><strong class="text text-danger">*</strong> Name</label>
                            <div class="col-md-6">
                              <input type="text" class="form-control" v-model="name"  placeholder="Enter Name" required>
                            </div>
                          </div>
                          <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right"><strong class="text text-danger">*</strong> Email</label>
                            <div class="col-md-6">
                              <input type="email" class="form-control" v-model="email"  placeholder="Enter Mail" required>
                            </div>
                          </div>

                          <template v-if="tipoAccion==1">
                            <div class="form-group row">
                                <label for="password" class="col-md-4 col-form-label text-md-right">Password</label>
                                <div class="col-md-6">
                                    <input type="password" class="form-control" v-model="password" required autocomplete="new-password">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="password-confirm" class="col-md-4 col-form-label text-md-right">Confirm Password</label>
                                <div class="col-md-6">
                                    <input type="password" class="form-control" v-model="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>
                          </template>


                        </div>
                        <!--./tipoAccion==1 o 2: Agregar o Actualizar-->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"  @click="cerrarModal()">Cerrar</button>
                    <button type="button" v-if="tipoAccion==1" class="btn btn-primary" @click="registrar()">Guardar</button>
                    <button type="button" v-if="tipoAccion==2" class="btn btn-primary" @click="actualizarDatos()">Actualizar</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!--Fin del modal-->

</div>
</template>

<script>
    export default {
        data(){
            return {
              arrayUsers:[],
              arrayShops:[],
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

              shop_id:null,
              user_id:0,
              name:'',
              email:'',
              password:'',
              password_confirmation:'',

              errors:[],

              modal:0,
              tituloModal:'',
              tipoAccion:0,
              errorUser:0,
              errorMostrarMsjUser:[],
            }
        },
        computed:{
           isActived: function(){
            return this.pagination.current_page;
           },
           //Calcula los elementos de la paginacion
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
            loadUsers(page,buscar,criterio){
                let me=this;
                var url = '/superadmin/users/get?page='+page+'&buscar='+buscar+'&criterio='+criterio;
                axios.get(url).then(function (response){
                    var respuesta  = response.data;
                    me.arrayUsers = respuesta.users.data;
                    me.arrayShops = respuesta.shops;
                    me.pagination = respuesta.pagination;
                    console.log(me.arrayUsers);
                  })
                  .catch(function (error) {
                    // handle error
                    console.log(error);
                  })
                  .finally(function () {
                    // always executed
                  });
            },
            cambiarPagina(page,buscar,criterio){
                let me = this;
                me.pagination.current_page = page;
                me.loadUsers(page,buscar,criterio);
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
                  title: '¿Desea cambiar a activo?',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonText: 'Aceptar',
                  cancelButtonText: 'Cancelar',
                  reverseButtons: true
                }).then((result) => {
                  if (result.value) {

                    let me=this;
                    axios.put('/superadmin/users/active',{
                        'id': id
                    }).then(function (response){
                        me.loadUsers(me.pagination.current_page,me.buscar,me.criterio);
                        swalWithBootstrapButtons.fire(
                          '¡Activo!',
                          'Actualizacion exitosa.',
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
                  title: '¿Desea cambiar a inactivo?',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonText: 'Aceptar',
                  cancelButtonText: 'Cancelar',
                  reverseButtons: true
                }).then((result) => {
                  if (result.value) {

                    let me=this;
                    axios.put('/superadmin/users/inactive',{
                        'id': id
                    }).then(function (response){
                        me.loadUsers(me.pagination.current_page,me.buscar,me.criterio);
                        swalWithBootstrapButtons.fire(
                          '¡Inactivo!',
                          'Actualizacion exitosa.',
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
                axios.post('/superadmin/users/store',{
                  'name':me.name,
                  'email':me.email,
                  'password':me.password,
                  'shop_id':me.shop_id,
                }).then(function (response){
                  console.log(response)
                  me.cerrarModal();
                  me.loadUsers(me.pagination.current_page,me.buscar,me.criterio)
                  Swal.fire(
                    'Exito!',
                    'Agregado correctamente.',
                    'success'
                  );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrio un error al guardar, consulte al amdinistrador del sistema.',
                        'error'
                  );
                });
            },
            actualizarDatos(){
               if(this.validarDatos('actualizar')){
                    return;
                }

                let me=this;
                axios.put('/superadmin/users/update',{
                  'user_id':me.user_id,
                  'name':me.name,
                  'email':me.email,
                  'shop_id':me.shop_id,
                }).then(function (response){
                  console.log(response)
                  me.cerrarModal();
                  me.loadUsers(me.pagination.current_page,me.buscar,me.criterio)
                  Swal.fire(
                    'Exito!',
                    'Actualización correcta.',
                    'success'
                  );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrio un error al guardar, consulte al amdinistrador del sistema.',
                        'error'
                    );
                });
            },
            validarDatos(accion){
                this.errorUser=0;
                this.errorMostrarMsjUser=[];
                if(accion=='registrar'){
                  if(!this.shop_id) this.errorMostrarMsjUser.push('Debe seleecionar una tienda.');
                  if(!this.name) this.errorMostrarMsjUser.push('El nombre no puede estar vacio.');
                  if(!this.email) this.errorMostrarMsjUser.push('El email no puede estar vacio.');
                  if(!this.password) this.errorMostrarMsjUser.push('El password no puede estar vacio.');
                  if(this.password!==this.password_confirmation) this.errorMostrarMsjUser.push('Los passwords no coindide.');
                }
                if(accion=='actualizar'){
                  if(!this.shop_id) this.errorMostrarMsjUser.push('Debe seleecionar una tienda.');
                  if(!this.name) this.errorMostrarMsjUser.push('El nombre no puede estar vacio.');
                  if(!this.email) this.errorMostrarMsjUser.push('El email no puede estar vacio.');
                }
                if(this.errorMostrarMsjUser.length)
                {
                    this.errorUser=1;
                    Swal.fire({
                        title: 'Alerta',
                        text: 'Ingrese todos los campos requeridos',
                        icon: 'error',
                    });
                }
                return this.errorUser;
            },
            abrirModal(modelo, accion, data=[]){
                switch(modelo){
                    case "user":{
                        switch(accion){
                            case 'registrar':{
                                this.modal=1;
                                this.tipoAccion =1;
                                this.errorUser=0;
                                this.tituloModal='Agregar';
                                this.name='';
                                this.email ='';
                                this.password ='';
                                this.password_confirmation ='';
                                this.shop_id=null;
                                break;
                            }
                            case 'actualizar_datos':{
                                this.modal=1;
                                this.tipoAccion =2;
                                this.errorUser=0;
                                this.tituloModal='Actualizar';

                                this.user_id= data['id'];
                                this.name = data['name'];
                                this.email = data['email'];
                                this.shop_id = data['shop_id'];
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
            this.loadUsers(1,'','name');
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
