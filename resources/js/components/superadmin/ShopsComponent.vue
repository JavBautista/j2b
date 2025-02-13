<template>
  <div>

  <div class="container-fluid">

        <!-- Ejemplo de tabla Listado -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-align-justify"></i> Tiendas
                <button type="button" @click="abrirModal('shop','registrar')" class="btn btn-primary">
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
                            <input type="text" v-model="buscar" class="form-control" placeholder="Texto a buscar" @keyup.enter="loadShops(1,buscar,criterio,estatus)">

                            <select class="form-control col-md-3" v-model="estatus">
                                <option value="">TODOS</option>
                                <option value="active">ACTIVOS</option>
                                <option value="inactive">BAJAS</option>
                            </select>
                            <button type="submit" @click="loadShops(1,buscar,criterio,estatus)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Nombre</th>
                            <th width="25%">Descripcion</th>
                            <th width="20%">Logo</th>
                            <th width="5%">Status</th>
                            <th width="5%">Corte</th>
                            <th width="5%">Creación</th>
                            <th width="5%">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                          <tr v-for="shop in arrayShops" :key="shop.id">
                              <td v-text="shop.id"></td>
                              <td v-text="shop.name"></td>
                              <td v-text="shop.description"></td>
                              <td class="text-muted small">
                                <span v-if="shop.logo">

                                  <img :src="'/storage/'+shop.logo" :alt="shop.name" class="card-img" width="50%" >
                                </span>
                                <span v-else>
                                  Sin Imagen
                                </span>
                              </td>
                              <td>
                                  <span v-if="shop.active" class="badge badge-success">Activo</span>
                                  <span v-else class="badge badge-danger">Baja</span>
                              </td>
                              <td>
                                {{ shop.cutoff }}
                              </td>
                              <td>
                                {{ shop.created_at | formatDate }}
                              </td>
                              <td>
                                <div class="dropdown">
                                  <a class="btn btn-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    ...
                                  </a>

                                  <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" @click="abrirModal('shop','ver_datos', shop)">  <i class="fa fa-eye"></i>Ver</a></li>
                                  
                                    <li>
                                      <a class="dropdown-item" href="#" @click="abrirModal('shop','actualizar_datos', shop)"><i class="fa fa-edit"></i>Editar</a>
                                    </li>
                                    <li>
                                        <a v-if="shop.active" class="dropdown-item" href="#" @click="actualizarAInactivo(shop.id)"><i class="fa fa fa-toggle-on"></i>Deshabilitar</a>
                                        <a v-else class="dropdown-item" href="#" @click="actualizarAActivo(shop.id)"><i class="fa fa fa-toggle-off"></i>Activar</a>
                                    </li>
                                    <li>
                                      <a class="dropdown-item" href="#" @click="abrirModal('shop','actualizar_logo', shop)"><i class="fa fa-image"></i>Act. Logo</a>
                                    </li>
                                    <li>
                                      <a class="dropdown-item" href="#" @click="abrirModal('shop','actualizar_cutoff', shop)"><i class="fa fa-calendar"></i>Act. Corte</a>
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

                        <div v-show="errorShop" class="form-group row div-error">
                          <div class="container-fluid">
                            <div class="alert alert-danger text-center">
                                <div v-for="error in errorMostrarMsjShop" :key="error" v-text="error">
                                </div>
                            </div>
                          </div>
                        </div>

                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>
                        <!--tipoAccion==1 o 2: Agregar o Actualizar-->
                        <div v-if="tipoAccion==1 || tipoAccion==2 || tipoAccion==3">
                          <div class="form-group">
                            <strong class="text text-danger">*</strong><label for="name">Nombre de la Tienda</label>
                            <input type="text" class="form-control" v-model="name"  placeholder="Enter Name" v-bind:readonly="tipoAccion === 2 || tipoAccion === 3" required>
                          </div>

                          <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea class="form-control" v-model="description"  rows="3" v-bind:readonly="tipoAccion === 3"></textarea>
                          </div>
                          <div class="form-group">
                            <label for="owner_name">Nombre del Propietario</label>
                            <input type="text" class="form-control" v-model="owner_name" v-bind:readonly="tipoAccion === 3">
                          </div>
                          <hr>
                          <h3>Dirección y contacto</h3>


                          <div class="form-group">
                            <label for="address">Calle</label>
                            <input type="text" class="form-control" v-model="address" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="number_out">Num. Ext.</label>
                            <input type="text" class="form-control" v-model="number_out" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="number_int">Num. Int.</label>
                            <input type="text" class="form-control" v-model="number_int" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="district">Colonia / Distrito</label>
                            <input type="text" class="form-control" v-model="district" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="zip_code">CP</label>
                            <input type="text" class="form-control" v-model="zip_code" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="city">Ciudad</label>
                            <input type="text" class="form-control" v-model="city" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="state">Estado</label>
                            <input type="text" class="form-control" v-model="state" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="whatsapp" id="chk-whatsapp" v-bind:disabled="tipoAccion === 3">
                            <label class="form-check-label" for="chk-whatsapp">whatsapp</label>
                          </div>

                          <div class="form-group">
                            <label for="phone">Teléfono/Celular</label>
                            <input type="text" class="form-control" v-model="phone" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="email">Email</label>
                            <input type="text" class="form-control" v-model="email" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <hr>
                          <h3>Información empresarial y de presentación</h3>

                          <div class="form-group">
                            <label for="slogan">Slogan</label>
                            <input type="text" class="form-control" v-model="slogan" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="presentation">Presentation</label>
                            <input type="text" class="form-control" v-model="presentation" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="mission">Misión</label>
                            <input type="text" class="form-control" v-model="mission" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="vision">Visión</label>
                            <input type="text" class="form-control" v-model="vision" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="values">Valores</label>
                            <input type="text" class="form-control" v-model="values" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <hr>
                          <h3>Datos de banco para los recibos y notas</h3>

                          <div class="form-group">
                            <label for="bank_name">Nombre de su Banco</label>
                            <input type="text" class="form-control" v-model="bank_name" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="bank_number">Número de cuenta o banco</label>
                            <input type="text" class="form-control" v-model="bank_number" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="bank_number_secondary">Número de cuenta o banco secundario</label>
                            <input type="text" class="form-control" v-model="bank_number_secondary" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <hr>
                          <h3>Redes sociales</h3>
                          <div class="form-group">
                            <label for="web">Página web</label>
                            <input type="text" class="form-control" v-model="web" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="facebook">Facebook</label>
                            <input type="text" class="form-control" v-model="facebook" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="twitter">Twitter</label>
                            <input type="text" class="form-control" v-model="twitter" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="instagram">Instagram</label>
                            <input type="text" class="form-control" v-model="instagram" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="pinterest">Pinterest</label>
                            <input type="text" class="form-control" v-model="pinterest" v-bind:readonly="tipoAccion === 3">
                          </div>

                          <div class="form-group">
                            <label for="video_channel">Canal de video <span class="text-muted small">(Youtube, Vimeo, otro, etc.)</span> </label>
                            <input type="text" class="form-control" v-model="video_channel" v-bind:readonly="tipoAccion === 3">
                          </div>


                        </div>
                        <!--./tipoAccion==1 o 2: Agregar o Actualizar-->
                        <div v-if="tipoAccion==4">
                          <h3>Actualizar logo de {{name}}</h3>
                          <div class="form-group">
                            <label>Logotipo</label>
                            <input class="form-control"  type="file" name="logo" @change="uploadLogo">
                          </div>
                        </div>

                        <!--tipoAccion==5 Cutoff-->
                        <div v-if="tipoAccion==5">
                          <div class="form-group">
                            <label for="cutoff">Día de Corte</label>
                            <select class="form-select" v-model="cutoff">
                              <option v-for="day in 31" :key="day" :value="day">
                                {{ day }}
                              </option>
                            </select>
                          </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"  @click="cerrarModal()">Cerrar</button>
                    <button type="button" v-if="tipoAccion==1" class="btn btn-primary" @click="registrarShop()">Guardar</button>
                    <button type="button" v-if="tipoAccion==2" class="btn btn-primary" @click="actualizarDatosShop()">Actualizar</button>
                    <button type="button" v-if="tipoAccion==4" class="btn btn-primary" @click="actualizarLogoShop()">Actualizar Logo</button>
                    <button type="button" v-if="tipoAccion==5" class="btn btn-primary" @click="actualizarCutoff()">Actualizar</button>
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
              estatus:'active',

              shop_id:0,
              name:'',
              description:'',
              zip_code:'',
              address:'',
              number_out:'',
              number_int:'',
              district:'',
              city:'',
              state:'',
              whatsapp:0,
              phone:'',
              email:'',
              bank_name:'',
              bank_number:'',
              web:'',
              facebook:'',
              twitter:'',
              instagram:'',
              pinterest:'',
              video_channel:'',
              slogan:'',
              presentation:'',
              mission:'',
              vision:'',
              values:'',
              bank_number_secondary:'',
              owner_name:'',
              logo:null,
              cutoff:0,


              errors:[],

              modal:0,
              tituloModal:'',
              tipoAccion:0,
              errorShop:0,
              errorMostrarMsjShop:[],
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
            uploadLogo(event) {
                this.logo = event.target.files[0];
                console.log(this.logo)
            },
            cambiarPagina(page,buscar,criterio,estatus){
                let me = this;
                me.pagination.current_page = page;
                me.loadShops(page,buscar,criterio,estatus);
            },
            loadShops(page,buscar,criterio,estatus){
                let me=this;
                var url = '/superadmin/shops/get?page='+page+'&buscar='+buscar+'&criterio='+criterio+'&estatus='+estatus;
                axios.get(url).then(function (response){
                    var respuesta  = response.data;
                    me.arrayShops = respuesta.shops.data;
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
                    axios.put('/superadmin/shops/active',{
                        'id': id
                    }).then(function (response){
                        me.loadShops(me.pagination.current_page,me.buscar,me.criterio, me.estatus);
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
                    axios.put('/superadmin/shops/deactive',{
                        'id': id
                    }).then(function (response){
                        me.loadShops(me.pagination.current_page,me.buscar,me.criterio,me.estatus);
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
            registrarShop(){
                if(this.validarDatosShop('registrar')){
                    return;
                }
                let me=this;
                axios.post('/superadmin/shops/store',{
                    'name':me.name,
                    'description':me.description,
                    'zip_code':me.zip_code,
                    'address':me.address,
                    'number_out':me.number_out,
                    'number_int':me.number_int,
                    'district':me.district,
                    'city':me.city,
                    'state':me.state,
                    'whatsapp':me.whatsapp,
                    'phone':me.phone,
                    'email':me.email,
                    'bank_name':me.bank_name,
                    'bank_number':me.bank_number,
                    'web':me.web,
                    'facebook':me.facebook,
                    'twitter':me.twitter,
                    'instagram':me.instagram,
                    'pinterest':me.pinterest,
                    'video_channel':me.video_channel,
                    'slogan':me.slogan,
                    'presentation':me.presentation,
                    'mission':me.mission,
                    'vision':me.vision,
                    'values':me.values,
                    'bank_number_secondary':me.bank_number_secondary,
                    'owner_name':me.owner_name,
                }).then(function (response){
                  console.log(response)
                  me.cerrarModal();
                  me.loadShops(me.pagination.current_page,me.buscar,me.criterio,me.estatus)
                  Swal.fire(
                    'Exito!',
                    'La tienda fue agregada correctamente.',
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
            actualizarDatosShop(){
                if(this.validarDatosShop('actualizar_info')){
                    return;
                }
                let me=this;
                axios.put('/superadmin/shops/update',{
                    'id':me.shop_id,
                    'description':me.description,
                    'zip_code':me.zip_code,
                    'address':me.address,
                    'number_out':me.number_out,
                    'number_int':me.number_int,
                    'district':me.district,
                    'city':me.city,
                    'state':me.state,
                    'whatsapp':me.whatsapp,
                    'phone':me.phone,
                    'email':me.email,
                    'bank_name':me.bank_name,
                    'bank_number':me.bank_number,
                    'web':me.web,
                    'facebook':me.facebook,
                    'twitter':me.twitter,
                    'instagram':me.instagram,
                    'pinterest':me.pinterest,
                    'video_channel':me.video_channel,
                    'slogan':me.slogan,
                    'presentation':me.presentation,
                    'mission':me.mission,
                    'vision':me.vision,
                    'values':me.values,
                    'bank_number_secondary':me.bank_number_secondary,
                    'owner_name':me.owner_name,
                }).then(function (response){
                  console.log(response)
                  me.cerrarModal();
                  me.loadShops(me.pagination.current_page,me.buscar,me.criterio,me.estatus)
                  Swal.fire(
                    'Exito!',
                    'La tienda fue actualizada correctamente.',
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
            actualizarLogoShop(){
                let me=this;
                const formData = new FormData();
                formData.append('shop_id', this.shop_id);

                if(!this.logo){
                    Swal.fire({
                      title: 'Error',
                      text: 'Por favor seleccionar una imagen.',
                      icon: 'error',
                    });
                    return;
                }

                let logo = this.logo;
                let logoType = logo.type;
                if(logoType.indexOf('image/') === -1){
                    Swal.fire({
                      title: 'Error',
                      text: 'El archivo seleccionado no es una imagen',
                      icon: 'error',
                    });
                    return;
                }
                formData.append('logo', this.logo);

                Swal.fire({
                    title: 'Cargando...',
                    onBeforeOpen: () => {
                      Swal.showLoading()
                    },
                    allowOutsideClick: false,
                });

                axios.post('/superadmin/shops/upload-logo',formData)
                .then(function (response){
                    console.log(response);
                    Swal.close();
                    me.cerrarModal();
                    me.loadShops(me.pagination.current_page,me.buscar,me.criterio,me.estatus)
                    Swal.fire({
                        title: 'Exitoso',
                        text: 'El video ha sido guardado exitosamente',
                        icon: 'success',
                    });
                }).catch(function (error){
                    console.log(error);
                    Swal.close();
                    Swal.fire({
                        title: 'Error',
                        text: 'Ha ocurrido un error al guardar el video',
                        icon: 'error',
                      });
                });
            },
            actualizarCutoff(){
                let me=this;
                axios.put('/superadmin/shops/update-cutoff',{
                    'id':me.shop_id,
                    'cutoff':me.cutoff,
                }).then(function (response){
                  console.log(response)
                  me.cerrarModal();
                  me.loadShops(me.pagination.current_page,me.buscar,me.criterio,me.estatus)
                  Swal.fire(
                    'Exito!',
                    'La tienda fue actualizada correctamente.',
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
            validarDatosShop(action){
                this.errorShop=0;
                this.errorMostrarMsjShop=[];

                if(!this.name) this.errorMostrarMsjShop.push('El nombre no puede estar vacio.');

                if(this.errorMostrarMsjShop.length){
                    this.errorShop=1;
                    Swal.fire({
                        title: 'Alerta',
                        text: 'El nombre no puede estar vacio',
                        icon: 'error',
                    });
                }

                return this.errorShop;
            },
            abrirModal(modelo, accion, data=[]){
                switch(modelo){
                    case "shop":{
                        switch(accion){
                            case 'registrar':{
                                this.modal=1;
                                this.tipoAccion =1;
                                this.tituloModal='Agregar';
                                this.name='';
                                this.description='';
                                this.zip_code='';
                                this.address='';
                                this.number_out='';
                                this.number_int='';
                                this.district='';
                                this.city='';
                                this.state='';
                                this.whatsapp=0;
                                this.phone='';
                                this.email='';
                                this.bank_name='';
                                this.bank_number='';
                                this.web='';
                                this.facebook='';
                                this.twitter='';
                                this.instagram='';
                                this.pinterest='';
                                this.video_channel='';
                                this.slogan='';
                                this.presentation='';
                                this.mission='';
                                this.vision='';
                                this.values='';
                                this.bank_number_secondary='';
                                this.owner_name='';
                                break;
                            }
                            case 'actualizar_datos':{
                                this.modal=1;
                                this.tipoAccion =2;
                                this.tituloModal='Actualizar Datos';

                                this.shop_id= data['id'];
                                this.name=data['name'];
                                this.description=data['description'];
                                this.zip_code=data['zip_code'];
                                this.address=data['address'];
                                this.number_out=data['number_out'];
                                this.number_int=data['number_int'];
                                this.district=data['district'];
                                this.city=data['city'];
                                this.state=data['state'];
                                this.whatsapp=data['whatsapp'];
                                this.phone=data['phone'];
                                this.email=data['email'];
                                this.bank_name=data['bank_name'];
                                this.bank_number=data['bank_number'];
                                this.web=data['web'];
                                this.facebook=data['facebook'];
                                this.twitter=data['twitter'];
                                this.instagram=data['instagram'];
                                this.pinterest=data['pinterest'];
                                this.video_channel=data['video_channel'];
                                this.slogan=data['slogan'];
                                this.presentation=data['presentation'];
                                this.mission=data['mission'];
                                this.vision=data['vision'];
                                this.values=data['values'];
                                this.bank_number_secondary=data['bank_number_secondary'];
                                this.owner_name=data['owner_name'];
                                break;
                            }
                            case 'ver_datos':{
                                this.modal=1;
                                this.tipoAccion =3;
                                this.tituloModal='Ver Datos';

                                this.shop_id= data['id'];
                                this.name=data['name'];
                                this.description=data['description'];
                                this.zip_code=data['zip_code'];
                                this.address=data['address'];
                                this.number_out=data['number_out'];
                                this.number_int=data['number_int'];
                                this.district=data['district'];
                                this.city=data['city'];
                                this.state=data['state'];
                                this.whatsapp=data['whatsapp'];
                                this.phone=data['phone'];
                                this.email=data['email'];
                                this.bank_name=data['bank_name'];
                                this.bank_number=data['bank_number'];
                                this.web=data['web'];
                                this.facebook=data['facebook'];
                                this.twitter=data['twitter'];
                                this.instagram=data['instagram'];
                                this.pinterest=data['pinterest'];
                                this.video_channel=data['video_channel'];
                                this.slogan=data['slogan'];
                                this.presentation=data['presentation'];
                                this.mission=data['mission'];
                                this.vision=data['vision'];
                                this.values=data['values'];
                                this.bank_number_secondary=data['bank_number_secondary'];
                                this.owner_name=data['owner_name'];
                                break;
                            }
                            case 'actualizar_logo':{
                                this.modal=1;
                                this.tipoAccion =4;
                                this.tituloModal='Ver Datos';

                                this.shop_id= data['id'];
                                this.name=data['name'];
                                this.logo=null;
                                break;
                            }
                          case 'actualizar_cutoff':{
                                this.modal=1;
                                this.tipoAccion =5;
                                this.tituloModal='Act. Día de corte';
                                this.shop_id= data['id'];
                                this.cutoff=data['cutoff'];
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
            this.loadShops(1,'','nombre','active');
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
