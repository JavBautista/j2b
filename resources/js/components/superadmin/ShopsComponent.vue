<template>
  <div>

  <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header con título y botón -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-shopping-cart" style="color: var(--j2b-primary);"></i> Gestion de Tiendas
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Administra todas las tiendas de la plataforma</p>
            </div>
            <button type="button" @click="abrirModal('shop','registrar')" class="j2b-btn j2b-btn-primary">
                <i class="fa fa-plus"></i> Nueva Tienda
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
                                <input type="text" v-model="buscar" class="j2b-input" placeholder="Buscar tienda..." @keyup.enter="loadShops(1,buscar,criterio,estatus)">
                            </div>
                            <select class="j2b-select" style="width: auto; min-width: 120px;" v-model="estatus">
                                <option value="">Todos</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
                            </select>
                            <button type="button" @click="loadShops(1,buscar,criterio,estatus)" class="j2b-btn j2b-btn-primary">
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
                                <th style="width: 60px;">ID</th>
                                <th>Tienda</th>
                                <th style="width: 100px;">Logo</th>
                                <th style="width: 100px;">Estado</th>
                                <th style="width: 80px;">Corte</th>
                                <th style="width: 120px;">Creacion</th>
                                <th style="width: 140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                              <tr v-for="shop in arrayShops" :key="shop.id">
                                  <td>
                                      <span class="j2b-badge j2b-badge-dark">{{ shop.id }}</span>
                                  </td>
                                  <td>
                                      <div class="d-flex align-items-center">
                                          <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 11px; width: 32px; height: 32px; flex-shrink: 0;">
                                              {{ shop.name.charAt(0).toUpperCase() }}
                                          </div>
                                          <div>
                                              <strong style="color: var(--j2b-dark);">{{ shop.name }}</strong>
                                              <small class="d-block" style="color: var(--j2b-gray-500); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                  {{ shop.description || 'Sin descripcion' }}
                                              </small>
                                          </div>
                                      </div>
                                  </td>
                                  <td>
                                    <span v-if="shop.logo">
                                      <img :src="'/storage/'+shop.logo" :alt="shop.name"
                                           style="max-width: 60px; max-height: 40px; border-radius: 4px; object-fit: contain; cursor: pointer;"
                                           @click="$viewImage(shop.logo)"
                                           title="Click para ver imagen">
                                    </span>
                                    <div v-else class="logo-placeholder">
                                        <i class="fa fa-image"></i>
                                    </div>
                                  </td>
                                  <td>
                                      <span v-if="shop.active" class="j2b-badge j2b-badge-success">
                                          <i class="fa fa-check-circle"></i> Activo
                                      </span>
                                      <span v-else class="j2b-badge j2b-badge-danger">
                                          <i class="fa fa-times-circle"></i> Inactivo
                                      </span>
                                  </td>
                                  <td>
                                    <span class="j2b-badge j2b-badge-info">Dia {{ shop.cutoff || '-' }}</span>
                                  </td>
                                  <td>
                                    <small style="color: var(--j2b-gray-500);">{{ shop.created_at | formatDate }}</small>
                                  </td>
                                  <td>
                                    <div class="d-flex gap-1">
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="abrirModal('shop','ver_datos', shop)" title="Ver">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-secondary" @click="abrirModal('shop','actualizar_datos', shop)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <div class="dropdown">
                                          <button class="j2b-btn j2b-btn-sm j2b-btn-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa fa-ellipsis-v"></i>
                                          </button>
                                          <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a v-if="shop.active" class="dropdown-item" href="#" @click="actualizarAInactivo(shop.id)">
                                                    <i class="fa fa-toggle-on text-danger"></i> Deshabilitar
                                                </a>
                                                <a v-else class="dropdown-item" href="#" @click="actualizarAActivo(shop.id)">
                                                    <i class="fa fa-toggle-off text-success"></i> Activar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                              <a class="dropdown-item" href="#" @click="abrirModal('shop','actualizar_logo', shop)">
                                                  <i class="fa fa-image"></i> Cambiar Logo
                                              </a>
                                            </li>
                                            <li>
                                              <a class="dropdown-item" href="#" @click="abrirModal('shop','actualizar_cutoff', shop)">
                                                  <i class="fa fa-calendar"></i> Cambiar Corte
                                              </a>
                                            </li>
                                          </ul>
                                        </div>
                                    </div>
                                  </td>
                              </tr>
                              <tr v-if="arrayShops.length === 0">
                                  <td colspan="7" class="text-center py-5">
                                      <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                      <p style="color: var(--j2b-gray-500);">No se encontraron tiendas</p>
                                  </td>
                              </tr>
                      </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-between align-items-center p-3" style="border-top: 1px solid var(--j2b-gray-200);">
                    <small style="color: var(--j2b-gray-500);">
                        Mostrando {{ pagination.from || 0 }} - {{ pagination.to || 0 }} de {{ pagination.total || 0 }}
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1,buscar,criterio)">
                                    <i class="fa fa-chevron-left"></i>
                                </a>
                            </li>
                            <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page==isActived ? 'active':'']">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(page,buscar,criterio)" v-text="page"></a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page+1,buscar,criterio)">
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
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modal}" role="dialog" aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-shopping-cart" style="color: var(--j2b-primary);"></i>
                        {{ tituloModal }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()" aria-label="Close">
                      <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <form v-on:submit.prevent action="" method="post" enctype="multipart/form-data">

                        <div v-show="errorShop" class="j2b-banner-alert j2b-banner-danger mb-3">
                            <i class="fa fa-exclamation-circle"></i>
                            <div>
                                <div v-for="error in errorMostrarMsjShop" :key="error" v-text="error"></div>
                            </div>
                        </div>

                        <p class="mb-3"><small style="color: var(--j2b-danger);">* Campos obligatorios</small></p>

                        <!--tipoAccion==1 o 2: Agregar o Actualizar-->
                        <div v-if="tipoAccion==1 || tipoAccion==2 || tipoAccion==3">

                          <!-- Informacion Basica -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-info-circle"></i> Informacion Basica
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Nombre de la Tienda</label>
                                        <input type="text" class="j2b-input" v-model="name" placeholder="Nombre de la tienda" v-bind:readonly="tipoAccion === 2 || tipoAccion === 3" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Propietario</label>
                                        <input type="text" class="j2b-input" v-model="owner_name" placeholder="Nombre del propietario" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                            <div class="j2b-form-group">
                                <label class="j2b-label">Descripcion</label>
                                <textarea class="j2b-input" v-model="description" rows="2" placeholder="Descripcion breve de la tienda" v-bind:readonly="tipoAccion === 3"></textarea>
                            </div>
                          </div>

                          <!-- Direccion y Contacto -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-map-marker"></i> Direccion y Contacto
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Calle</label>
                                        <input type="text" class="j2b-input" v-model="address" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Num. Ext.</label>
                                        <input type="text" class="j2b-input" v-model="number_out" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Num. Int.</label>
                                        <input type="text" class="j2b-input" v-model="number_int" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Colonia</label>
                                        <input type="text" class="j2b-input" v-model="district" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">CP</label>
                                        <input type="text" class="j2b-input" v-model="zip_code" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Ciudad</label>
                                        <input type="text" class="j2b-input" v-model="city" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Estado</label>
                                        <input type="text" class="j2b-input" v-model="state" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Telefono/Celular</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="text" class="j2b-input" v-model="phone" v-bind:readonly="tipoAccion === 3">
                                            <label class="d-flex align-items-center gap-1" style="white-space: nowrap;">
                                                <input type="checkbox" v-model="whatsapp" v-bind:disabled="tipoAccion === 3">
                                                <i class="fa fa-whatsapp" style="color: #25D366;"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Email</label>
                                        <input type="email" class="j2b-input" v-model="email" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                          </div>

                          <!-- Info Empresarial -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-building"></i> Informacion Empresarial
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Slogan</label>
                                        <input type="text" class="j2b-input" v-model="slogan" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Presentacion</label>
                                        <input type="text" class="j2b-input" v-model="presentation" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Mision</label>
                                        <input type="text" class="j2b-input" v-model="mission" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Vision</label>
                                        <input type="text" class="j2b-input" v-model="vision" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Valores</label>
                                        <input type="text" class="j2b-input" v-model="values" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                          </div>

                          <!-- Datos Bancarios -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-bank"></i> Datos Bancarios
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Banco</label>
                                        <input type="text" class="j2b-input" v-model="bank_name" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Cuenta Principal</label>
                                        <input type="text" class="j2b-input" v-model="bank_number" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label">Cuenta Secundaria</label>
                                        <input type="text" class="j2b-input" v-model="bank_number_secondary" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                          </div>

                          <!-- Redes Sociales -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-share-alt"></i> Redes Sociales
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><i class="fa fa-globe"></i> Pagina web</label>
                                        <input type="text" class="j2b-input" v-model="web" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><i class="fa fa-facebook"></i> Facebook</label>
                                        <input type="text" class="j2b-input" v-model="facebook" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><i class="fa fa-twitter"></i> Twitter</label>
                                        <input type="text" class="j2b-input" v-model="twitter" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><i class="fa fa-instagram"></i> Instagram</label>
                                        <input type="text" class="j2b-input" v-model="instagram" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><i class="fa fa-pinterest"></i> Pinterest</label>
                                        <input type="text" class="j2b-input" v-model="pinterest" v-bind:readonly="tipoAccion === 3">
                                    </div>
                                </div>
                            </div>
                            <div class="j2b-form-group">
                                <label class="j2b-label"><i class="fa fa-youtube-play"></i> Canal de video</label>
                                <input type="text" class="j2b-input" v-model="video_channel" placeholder="Youtube, Vimeo, etc." v-bind:readonly="tipoAccion === 3">
                            </div>
                          </div>

                        </div>
                        <!--./tipoAccion==1 o 2: Agregar o Actualizar-->

                        <!-- Actualizar Logo -->
                        <div v-if="tipoAccion==4">
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-image"></i> Actualizar logo de {{ name }}
                            </h6>
                            <div class="j2b-form-group">
                                <label class="j2b-label">Seleccionar imagen</label>
                                <input class="j2b-input" type="file" name="logo" @change="uploadLogo" accept="image/*">
                                <small style="color: var(--j2b-gray-500);">Formatos: JPG, PNG, GIF. Max 2MB</small>
                            </div>
                          </div>
                        </div>

                        <!-- Actualizar Cutoff -->
                        <div v-if="tipoAccion==5">
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-calendar"></i> Dia de Corte
                            </h6>
                            <div class="j2b-form-group">
                                <label class="j2b-label">Seleccionar dia del mes</label>
                                <select class="j2b-select" v-model="cutoff">
                                  <option v-for="day in 31" :key="day" :value="day">
                                    Dia {{ day }}
                                  </option>
                                </select>
                                <small style="color: var(--j2b-gray-500);">El corte se realizara este dia de cada mes</small>
                            </div>
                          </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">
                        <i class="fa fa-times"></i> Cerrar
                    </button>
                    <button type="button" v-if="tipoAccion==1" class="j2b-btn j2b-btn-primary" @click="registrarShop()">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                    <button type="button" v-if="tipoAccion==2" class="j2b-btn j2b-btn-primary" @click="actualizarDatosShop()">
                        <i class="fa fa-save"></i> Actualizar
                    </button>
                    <button type="button" v-if="tipoAccion==4" class="j2b-btn j2b-btn-primary" @click="actualizarLogoShop()">
                        <i class="fa fa-upload"></i> Subir Logo
                    </button>
                    <button type="button" v-if="tipoAccion==5" class="j2b-btn j2b-btn-primary" @click="actualizarCutoff()">
                        <i class="fa fa-save"></i> Guardar
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
            this.loadShops(1, this.buscar, this.criterio, this.estatus);
        }
    }
</script>

<style>
    /* Logo placeholder */
    .logo-placeholder {
        width: 60px;
        height: 40px;
        background: var(--j2b-gray-100);
        border: 1px dashed var(--j2b-gray-300);
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--j2b-gray-400);
        font-size: 16px;
    }

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

    /* Form sections */
    .j2b-form-section {
        background: var(--j2b-gray-100);
        border-radius: var(--j2b-radius-md);
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .j2b-form-section-title {
        color: var(--j2b-dark);
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--j2b-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .j2b-form-section-title i {
        color: var(--j2b-primary);
    }

    /* Pagination styles override */
    .pagination .page-link {
        border-color: var(--j2b-gray-300);
        color: var(--j2b-dark);
    }

    .pagination .page-item.active .page-link {
        background: var(--j2b-gradient-primary);
        border-color: var(--j2b-primary);
        color: var(--j2b-dark);
    }

    .pagination .page-link:hover {
        background: var(--j2b-gray-200);
        border-color: var(--j2b-primary);
    }

    /* Gap utility for older browsers */
    .gap-1 { gap: 0.25rem; }
    .gap-2 { gap: 0.5rem; }
</style>
