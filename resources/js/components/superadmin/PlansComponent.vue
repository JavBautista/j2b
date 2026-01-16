<template>
  <div>

  <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header con título y botón -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-list-alt" style="color: var(--j2b-primary);"></i> Gestion de Planes
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Administra los planes de suscripcion de la plataforma</p>
            </div>
            <button type="button" @click="abrirModal('plan','registrar')" class="j2b-btn j2b-btn-primary">
                <i class="fa fa-plus"></i> Nuevo Plan
            </button>
        </div>

        <!-- Card principal -->
        <div class="j2b-card">
            <!-- Filtros de búsqueda -->
            <div class="j2b-card-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex gap-2 flex-wrap">
                            <select class="j2b-select" style="width: auto; min-width: 140px;" v-model="criterio">
                              <option value="name">Nombre</option>
                              <option value="description">Descripcion</option>
                            </select>
                            <div class="j2b-input-icon" style="flex: 1; min-width: 200px;">
                                <i class="fa fa-search"></i>
                                <input type="text" v-model="buscar" class="j2b-input" placeholder="Buscar plan..." @keyup.enter="buscarPlanes()">
                            </div>
                            <button type="button" @click="buscarPlanes()" class="j2b-btn j2b-btn-primary">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <span class="j2b-badge j2b-badge-info">{{ pagination.total }} planes</span>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="j2b-card-body p-0">
                <div class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Plan</th>
                                <th style="width: 140px;">Precios</th>
                                <th style="width: 100px;">Estado</th>
                                <th style="width: 140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                              <tr v-for="plan in arrayPlans" :key="plan.id">
                                  <td>
                                      <span class="j2b-badge j2b-badge-dark">{{ plan.id }}</span>
                                  </td>
                                  <td>
                                      <div class="d-flex align-items-center">
                                          <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 11px; width: 32px; height: 32px; flex-shrink: 0;">
                                              {{ plan.name.charAt(0).toUpperCase() }}
                                          </div>
                                          <div>
                                              <strong style="color: var(--j2b-dark);">{{ plan.name }}</strong>
                                              <small class="d-block" style="color: var(--j2b-gray-500); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                  {{ plan.description || 'Sin descripcion' }}
                                              </small>
                                          </div>
                                      </div>
                                  </td>
                                  <td>
                                      <div class="d-flex flex-column">
                                          <div>
                                              <strong style="color: var(--j2b-info);">${{ plan.price }}</strong>
                                              <small style="color: var(--j2b-gray-500);">/mes</small>
                                          </div>
                                          <div v-if="plan.yearly_price">
                                              <strong style="color: var(--j2b-warning); font-size: 0.9em;">${{ plan.yearly_price }}</strong>
                                              <small style="color: var(--j2b-gray-500);">/año</small>
                                          </div>
                                          <small v-else style="color: var(--j2b-gray-400);">Sin precio anual</small>
                                      </div>
                                  </td>
                                  <td>
                                      <span v-if="plan.active" class="j2b-badge j2b-badge-success">
                                          <i class="fa fa-check-circle"></i> Activo
                                      </span>
                                      <span v-else class="j2b-badge j2b-badge-danger">
                                          <i class="fa fa-times-circle"></i> Inactivo
                                      </span>
                                  </td>
                                  <td>
                                      <div class="d-flex gap-1">
                                          <button class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="abrirModal('plan','ver_datos', plan)" title="Ver">
                                              <i class="fa fa-eye"></i>
                                          </button>
                                          <button class="j2b-btn j2b-btn-sm j2b-btn-secondary" @click="abrirModal('plan','actualizar_datos', plan)" title="Editar">
                                              <i class="fa fa-edit"></i>
                                          </button>
                                          <div class="dropdown">
                                              <button class="j2b-btn j2b-btn-sm j2b-btn-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                  <i class="fa fa-ellipsis-v"></i>
                                              </button>
                                              <ul class="dropdown-menu dropdown-menu-end">
                                                  <li>
                                                      <a v-if="plan.active" class="dropdown-item" href="#" @click.prevent="actualizarAInactivo(plan.id)">
                                                          <i class="fa fa-toggle-on text-danger"></i> Deshabilitar
                                                      </a>
                                                      <a v-else class="dropdown-item" href="#" @click.prevent="actualizarAActivo(plan.id)">
                                                          <i class="fa fa-toggle-off text-success"></i> Activar
                                                      </a>
                                                  </li>
                                              </ul>
                                          </div>
                                      </div>
                                  </td>
                              </tr>
                              <tr v-if="arrayPlans.length === 0">
                                  <td colspan="5" class="text-center py-5">
                                      <i class="fa fa-list-alt fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                      <p style="color: var(--j2b-gray-500);">No se encontraron planes</p>
                                  </td>
                              </tr>
                      </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="j2b-card-body" v-if="pagination.last_page > 1">
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1)">
                                    <i class="fa fa-chevron-left"></i>
                                </a>
                            </li>
                            <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page==isActived ? 'active':'']">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(page)" v-text="page"></a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page+1)">
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!--Inicio del modal agregar/actualizar-->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modal}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-list-alt" style="color: var(--j2b-primary);"></i>
                        {{ tituloModal }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <form v-on:submit.prevent action="" method="post" enctype="multipart/form-data">

                        <div v-show="errorPlan" class="j2b-banner-alert j2b-banner-danger mb-3">
                            <i class="fa fa-exclamation-circle"></i>
                            <div>
                                <div v-for="error in errorMostrarMsjPlan" :key="error" v-text="error"></div>
                            </div>
                        </div>

                        <p class="mb-3" v-if="tipoAccion !== 3"><small style="color: var(--j2b-danger);">* Campos obligatorios</small></p>

                        <div v-if="tipoAccion==1 || tipoAccion==2 || tipoAccion==3">
                          <!-- Información del Plan -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-info-circle"></i> Informacion del Plan
                            </h6>
                            <div class="j2b-form-group">
                                <label class="j2b-label"><span v-if="tipoAccion !== 3" style="color: var(--j2b-danger);">*</span> Nombre del Plan</label>
                                <input type="text" class="j2b-input" v-model="name" placeholder="Ej: Plan Basico" :readonly="tipoAccion === 3" required>
                            </div>
                            <div class="j2b-form-group">
                                <label class="j2b-label">Descripcion</label>
                                <textarea class="j2b-input" v-model="description" rows="3" placeholder="Descripcion del plan..." :readonly="tipoAccion === 3"></textarea>
                            </div>
                          </div>

                          <!-- Precios -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-dollar"></i> Precios de Referencia
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><i class="fa fa-calendar-o text-info"></i> Precio Mensual (MXN)</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="j2b-badge j2b-badge-dark">$</span>
                                            <input type="number" min="0" step="1" class="j2b-input" v-model="price" placeholder="350" :readonly="tipoAccion === 3">
                                            <span class="j2b-badge j2b-badge-outline">/mes</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><i class="fa fa-calendar text-warning"></i> Precio Anual (MXN)</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="j2b-badge j2b-badge-dark">$</span>
                                            <input type="number" min="0" step="1" class="j2b-input" v-model="yearly_price" placeholder="3500" :readonly="tipoAccion === 3">
                                            <span class="j2b-badge j2b-badge-outline">/año</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Vista previa -->
                            <div class="row mt-3" v-if="price || yearly_price">
                                <div class="col-12">
                                    <div class="p-3" style="background: var(--j2b-gray-100); border-radius: var(--j2b-radius-md);">
                                        <small style="color: var(--j2b-gray-500);">Vista previa de precios:</small>
                                        <div class="d-flex gap-4 mt-2">
                                            <div v-if="price">
                                                <span style="font-size: 1.3em; font-weight: 700; color: var(--j2b-info);">
                                                    ${{ price }}
                                                </span>
                                                <small style="color: var(--j2b-gray-500);">/mes</small>
                                            </div>
                                            <div v-if="yearly_price">
                                                <span style="font-size: 1.3em; font-weight: 700; color: var(--j2b-warning);">
                                                    ${{ yearly_price }}
                                                </span>
                                                <small style="color: var(--j2b-gray-500);">/año</small>
                                                <span v-if="price && yearly_price < price * 12" class="j2b-badge j2b-badge-success ml-2" style="font-size: 10px;">
                                                    {{ Math.round((1 - yearly_price / (price * 12)) * 100) }}% desc.
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="j2b-text-muted mt-2 d-block">
                                <i class="fa fa-info-circle"></i> Estos precios se asignan a tiendas nuevas como referencia. Luego pueden personalizarse por tienda.
                            </small>
                          </div>

                        </div>
                    </form>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">Cerrar</button>
                    <button type="button" v-if="tipoAccion==1" class="j2b-btn j2b-btn-primary" @click="registrar()">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                    <button type="button" v-if="tipoAccion==2" class="j2b-btn j2b-btn-primary" @click="actualizarDatos()">
                        <i class="fa fa-save"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
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
              yearly_price:'',


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
            cambiarPagina(page){
                let me = this;
                me.pagination.current_page = page;
                me.loadPlans(page, me.buscar, me.criterio);
            },
            buscarPlanes(){
                this.loadPlans(1, this.buscar, this.criterio);
            },
            loadPlans(page, buscar, criterio){
                let me=this;
                var url = '/superadmin/plans/get?page='+page+'&buscar='+buscar+'&criterio='+criterio;
                axios.get(url).then(function (response){
                    var respuesta = response.data;
                    me.arrayPlans = respuesta.plans.data;
                    me.pagination = respuesta.pagination;
                  })
                  .catch(function (error) {
                    console.log(error);
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
                    'yearly_price':me.yearly_price,
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
                    'yearly_price':me.yearly_price,
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
                                this.tituloModal='Nuevo Plan';
                                this.name='';
                                this.description='';
                                this.price='';
                                this.yearly_price='';
                                break;
                            }
                            case 'actualizar_datos':{
                                this.modal=1;
                                this.tipoAccion =2;
                                this.tituloModal='Editar Plan';
                                this.plan_id= data['id'];
                                this.name=data['name'];
                                this.description=data['description'];
                                this.price=data['price'];
                                this.yearly_price=data['yearly_price'] || '';
                                break;
                            }
                            case 'ver_datos':{
                                this.modal=1;
                                this.tipoAccion =3;
                                this.tituloModal='Detalles del Plan';
                                this.plan_id= data['id'];
                                this.name=data['name'];
                                this.description=data['description'];
                                this.price=data['price'];
                                this.yearly_price=data['yearly_price'] || '';
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
            this.loadPlans(1, this.buscar, this.criterio);
        }
    }
</script>

<style>
    /* Modal styles */
    .mostrar {
        display: block !important;
        opacity: 1 !important;
        position: fixed !important;
        background-color: rgba(26, 26, 46, 0.8) !important;
        overflow-y: auto;
        z-index: 1050;
    }

    .j2b-modal-content {
        border: none;
        border-radius: var(--j2b-radius-lg);
        box-shadow: var(--j2b-shadow-lg);
    }

    .j2b-modal-header {
        background: var(--j2b-gradient-dark);
        color: var(--j2b-white);
        border-radius: var(--j2b-radius-lg) var(--j2b-radius-lg) 0 0;
        padding: 1rem 1.5rem;
        border-bottom: none;
    }

    .j2b-modal-header .modal-title {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }

    .j2b-modal-close {
        background: rgba(255,255,255,0.1);
        border: none;
        color: var(--j2b-white);
        width: 32px;
        height: 32px;
        border-radius: var(--j2b-radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--j2b-transition-fast);
    }

    .j2b-modal-close:hover {
        background: rgba(255,255,255,0.2);
    }

    .j2b-modal-body {
        padding: 1.5rem;
        max-height: 70vh;
        overflow-y: auto;
    }

    .j2b-modal-footer {
        padding: 1rem 1.5rem;
        background: var(--j2b-gray-100);
        border-top: 1px solid var(--j2b-gray-200);
        border-radius: 0 0 var(--j2b-radius-lg) var(--j2b-radius-lg);
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }
</style>
