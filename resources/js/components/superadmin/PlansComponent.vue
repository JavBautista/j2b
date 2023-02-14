<template>
  <div>

  <div class="container-fluid">

        <!-- Ejemplo de tabla Listado -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-align-justify"></i> Planes
                <button type="button" @click="abrirModal('plan','registrar')" class="btn btn-primary">
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
                              <option value="description">Descripción</option>
                            </select>
                            <input type="text" v-model="buscar" class="form-control" placeholder="Texto a buscar" @keyup.enter="loadPlans(1,buscar,criterio)">
                            <button type="submit" @click="loadPlans(1,buscar,criterio)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Status</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                          <tr v-for="plan in arrayPlans" :key="plan.id">
                              <td v-text="plan.id"></td>
                              <td v-text="plan.name"></td>
                              <td v-text="plan.description"></td>
                              <td v-text="plan.price"></td>
                              <td>
                                  <span v-if="plan.active" class="badge badge-success">Activo</span>
                                  <span v-else class="badge badge-danger">Baja</span>
                              </td>
                              <td>
                                    <div class="dropdown">
                                      <a class="btn btn-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        ...
                                      </a>

                                      <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" @click="abrirModal('plan','ver_datos', plan)"><i class="fa fa-eye"></i>Ver</a></li>
                                        <li>
                                        <li><a class="dropdown-item" href="#" @click="abrirModal('plan','actualizar_datos', plan)"><i class="fa fa-edit"></i>Editar</a></li>
                                        <li>
                                            <a v-if="plan.active" class="dropdown-item" href="#" @click="actualizarAInactivo(plan.id)"><i class="fa fa fa-toggle-on"></i>Deshabilitar</a>
                                            <a v-else class="dropdown-item" href="#" @click="actualizarAActivo(plan.id)"><i class="fa fa fa-toggle-off"></i>Activar</a>
                                        </li>

                                      </ul>
                                    </div>

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

                        <div v-show="errorPlan" class="form-group row div-error">
                          <div class="container-fluid">
                            <div class="alert alert-danger text-center">
                                <div v-for="error in errorMostrarMsjPlan" :key="error" v-text="error">
                                </div>
                            </div>
                          </div>
                        </div>

                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>
                        <!--tipoAccion==1 o 2: Agregar o Actualizar-->
                        <div v-if="tipoAccion==1 || tipoAccion==2 || tipoAccion==3">
                          <div class="form-group">
                            <strong class="text text-danger">*</strong><label for="name">Nombre del plan</label>
                            <input type="text" class="form-control" v-model="name"  placeholder="Enter Name" v-bind:readonly="tipoAccion === 3" required>
                          </div>

                          <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea class="form-control" v-model="description"  rows="3" v-bind:readonly="tipoAccion === 3"></textarea>
                          </div>
                          <div class="form-group">
                            <label for="price">Precio</label>
                            <input type="number" min="0" step="1" class="form-control" v-model="price" v-bind:readonly="tipoAccion === 3">
                          </div>



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
              arrayPlans:[],
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

              plan_id:0,
              name:'',
              description:'',
              price:'',


              errors:[],

              modal:0,
              tituloModal:'',
              tipoAccion:0,
              errorPlan:0,
              errorMostrarMsjPlan:[],
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
            cambiarPagina(page,buscar,criterio){
                let me = this;
                me.pagination.current_page = page;
                me.loadPlans(page,buscar,criterio);
            },
            loadPlans(page,buscar,criterio){
                let me=this;
                var url = '/superadmin/plans/get?page='+page+'&buscar='+buscar+'&criterio='+criterio;
                axios.get(url).then(function (response){
                    var respuesta  = response.data;
                    me.arrayPlans = respuesta.plans.data;
                    me.pagination = respuesta.pagination;
                  })
                  .catch(function (error) {
                    // handle error
                    console.log(error);
                  })
                  .finally(function () {
                    // always executed
                  });
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
                    axios.put('/superadmin/plans/active',{
                        'id': id
                    }).then(function (response){
                        me.loadPlans(me.pagination.current_page,me.buscar,me.criterio);
                        swalWithBootstrapButtons.fire(
                          '¡Activo!',
                          'Actualización exitosa.',
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
                    axios.put('/superadmin/plans/deactive',{
                        'id': id
                    }).then(function (response){
                        me.loadPlans(me.pagination.current_page,me.buscar,me.criterio);
                        swalWithBootstrapButtons.fire(
                          '¡Inactivo!',
                          'Actualización exitosa.',
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
                axios.post('/superadmin/plans/store',{
                    'name':me.name,
                    'description':me.description,
                    'price':me.price,
                }).then(function (response){
                  console.log(response)
                  me.cerrarModal();
                  me.loadPlans(me.pagination.current_page,me.buscar,me.criterio)
                  Swal.fire(
                    'Exito!',
                    'La tienda fue agregada correctamente.',
                    'success'
                  );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrio un error al guardar la tienda, consulte al administrador del sistema.',
                        'error'
                  );
                });
            },
            actualizarDatos(){
                if(this.validarDatos('actualizar_info')){
                    return;
                }
                let me=this;
                axios.put('/superadmin/plans/update',{
                    'id':me.plan_id,
                    'name':me.name,
                    'description':me.description,
                    'price':me.price,
                }).then(function (response){
                  console.log(response)
                  me.cerrarModal();
                  me.loadPlans(me.pagination.current_page,me.buscar,me.criterio)
                  Swal.fire(
                    'Exito!',
                    'Actualizado correctamente.',
                    'success'
                  );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrio un error al guardar, consulte al administrador del sistema.',
                        'error'
                    );
                });
            },
            validarDatos(action){
                this.errorPlan=0;
                this.errorMostrarMsjPlan=[];

                if(!this.name) this.errorMostrarMsjPlan.push('El nombre no puede estar vacio.');

                if(this.errorMostrarMsjPlan.length){
                    this.errorPlan=1;
                    Swal.fire({
                        title: 'Alerta',
                        text: 'El nombre no puede estar vacio',
                        icon: 'error',
                    });
                }

                return this.errorPlan;
            },
            abrirModal(modelo, accion, data=[]){
                switch(modelo){
                    case "plan":{
                        switch(accion){
                            case 'registrar':{
                                this.modal=1;
                                this.tipoAccion =1;
                                this.tituloModal='Agregar';
                                this.name='';
                                this.description='';
                                this.price='';
                                break;
                            }
                            case 'actualizar_datos':{
                                this.modal=1;
                                this.tipoAccion =2;
                                this.tituloModal='Actualizar Datos';

                                this.plan_id= data['id'];
                                this.name=data['name'];
                                this.description=data['description'];
                                this.price=data['price'];
                                break;
                            }
                            case 'ver_datos':{
                                this.modal=1;
                                this.tipoAccion =3;
                                this.tituloModal='Ver Datos';

                                this.plan_id= data['id'];
                                this.name=data['name'];
                                this.description=data['description'];
                                this.price=data['price'];
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
            this.loadPlans(1,'','nombre');
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
