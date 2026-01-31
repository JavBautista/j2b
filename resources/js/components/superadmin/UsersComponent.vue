<template>
<div>

  <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header con título y botón -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-users" style="color: var(--j2b-primary);"></i> Gestion de Usuarios
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Administra los usuarios de todas las tiendas</p>
            </div>
            <button type="button" @click="abrirModal('user','registrar')" class="j2b-btn j2b-btn-primary">
                <i class="fa fa-plus"></i> Nuevo Usuario
            </button>
        </div>

        <!-- Card principal -->
        <div class="j2b-card">
            <!-- Filtros de búsqueda -->
            <div class="j2b-card-header">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="d-flex gap-2 flex-wrap">
                            <select class="j2b-select" style="width: auto; min-width: 120px;" v-model="criterio">
                              <option value="name">Nombre</option>
                              <option value="email">Email</option>
                            </select>
                            <div class="j2b-input-icon" style="flex: 1; min-width: 200px;">
                                <i class="fa fa-search"></i>
                                <input type="text" v-model="buscar" class="j2b-input" placeholder="Buscar usuario..." @keyup.enter="buscarUsuarios()">
                            </div>
                            <select class="j2b-select" style="width: auto; min-width: 120px;" v-model="estatus">
                                <option value="">Todos</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
                            </select>
                            <select class="j2b-select" style="width: auto; min-width: 140px;" v-model="shop">
                                <option value="">Todas las tiendas</option>
                                <option v-for="s in arrayShops" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <select class="j2b-select" style="width: auto; min-width: 120px;" v-model="rol">
                                <option value="">Todos los roles</option>
                                <option v-for="r in arrayRoles" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                            <button type="button" @click="buscarUsuarios()" class="j2b-btn j2b-btn-primary">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3 text-right">
                        <span class="j2b-badge j2b-badge-info">{{ pagination.total }} usuarios</span>
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
                                <th>Usuario</th>
                                <th>Tienda</th>
                                <th style="width: 120px;">Rol</th>
                                <th style="width: 100px;">Acceso</th>
                                <th style="width: 60px;">IA</th>
                                <th style="width: 100px;">Estado</th>
                                <th style="width: 160px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                              <tr v-for="user in arrayUsers" :key="user.id">
                                  <td>
                                      <span class="j2b-badge j2b-badge-dark">{{ user.id }}</span>
                                  </td>
                                  <td>
                                      <div class="d-flex align-items-center">
                                          <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 11px; width: 32px; height: 32px; flex-shrink: 0;">
                                              {{ user.name.charAt(0).toUpperCase() }}
                                          </div>
                                          <div>
                                              <strong style="color: var(--j2b-dark);">{{ user.name }}</strong>
                                              <small class="d-block" style="color: var(--j2b-gray-500);">
                                                  {{ user.email }}
                                              </small>
                                          </div>
                                      </div>
                                  </td>
                                  <td>
                                      <span v-if="user.shop" class="j2b-badge j2b-badge-outline">
                                          <i class="fa fa-store"></i> {{ user.shop.name }}
                                      </span>
                                      <span v-else class="j2b-text-muted">-</span>
                                  </td>
                                  <td>
                                      <span v-if="user.roles && user.roles.length" class="j2b-badge j2b-badge-info">
                                          {{ user.roles[0].name }}
                                      </span>
                                      <span v-else class="j2b-badge j2b-badge-outline">Sin rol</span>
                                  </td>
                                  <td>
                                      <template v-if="user.roles && user.roles.length && user.roles[0].name.toLowerCase() === 'admin'">
                                          <span v-if="!user.limited" class="j2b-badge j2b-badge-success">
                                              <i class="fa fa-crown"></i> Full
                                          </span>
                                          <span v-else class="j2b-badge j2b-badge-warning">
                                              <i class="fa fa-lock"></i> Limitado
                                          </span>
                                      </template>
                                      <span v-else class="j2b-text-muted">-</span>
                                  </td>
                                  <td>
                                      <button v-if="user.id !== 1" class="j2b-btn j2b-btn-sm" :style="user.can_use_ai ? 'background-color: #10b981; border-color: #10b981; color: #fff;' : 'background-color: #e5e7eb; border-color: #e5e7eb; color: #9ca3af;'" @click="toggleAI(user)" :title="user.can_use_ai ? 'IA Habilitada - Click para deshabilitar' : 'IA Deshabilitada - Click para habilitar'">
                                          <i class="fa fa-bolt"></i>
                                      </button>
                                      <span v-else class="j2b-text-muted">-</span>
                                  </td>
                                  <td>
                                      <span v-if="user.active" class="j2b-badge j2b-badge-success">
                                          <i class="fa fa-check-circle"></i> Activo
                                      </span>
                                      <span v-else class="j2b-badge j2b-badge-danger">
                                          <i class="fa fa-times-circle"></i> Inactivo
                                      </span>
                                  </td>
                                  <td>
                                      <!-- Usuario protegido (id=1) -->
                                      <div v-if="user.id === 1" class="d-flex align-items-center">
                                          <span class="j2b-badge j2b-badge-dark">
                                              <i class="fa fa-shield"></i> Protegido
                                          </span>
                                      </div>
                                      <!-- Usuarios normales -->
                                      <div v-else class="d-flex gap-1">
                                          <button class="j2b-btn j2b-btn-sm j2b-btn-secondary" @click="abrirModal('user','actualizar_datos', user)" title="Editar">
                                              <i class="fa fa-edit"></i>
                                          </button>
                                          <button class="j2b-btn j2b-btn-sm j2b-btn-info" @click="abrirModalEmail(user)" title="Cambiar Email">
                                              <i class="fa fa-envelope"></i>
                                          </button>
                                          <button class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="resetearPassword(user.id)" title="Resetear Contraseña">
                                              <i class="fa fa-key"></i>
                                          </button>
                                          <div class="dropdown">
                                              <button class="j2b-btn j2b-btn-sm j2b-btn-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                  <i class="fa fa-ellipsis-v"></i>
                                              </button>
                                              <ul class="dropdown-menu dropdown-menu-end">
                                                  <li>
                                                      <a v-if="user.active" class="dropdown-item" href="#" @click.prevent="actualizarAInactivo(user.id)">
                                                          <i class="fa fa-toggle-on text-danger"></i> Deshabilitar
                                                      </a>
                                                      <a v-else class="dropdown-item" href="#" @click.prevent="actualizarAActivo(user.id)">
                                                          <i class="fa fa-toggle-off text-success"></i> Activar
                                                      </a>
                                                  </li>
                                              </ul>
                                          </div>
                                      </div>
                                  </td>
                              </tr>
                              <tr v-if="arrayUsers.length === 0">
                                  <td colspan="8" class="text-center py-5">
                                      <i class="fa fa-users fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                      <p style="color: var(--j2b-gray-500);">No se encontraron usuarios</p>
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
                        <i class="fa fa-user" style="color: var(--j2b-primary);"></i>
                        {{ tituloModal }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <form v-on:submit.prevent action="" method="post" enctype="multipart/form-data">

                        <div v-show="errorUser" class="j2b-banner-alert j2b-banner-danger mb-3">
                            <i class="fa fa-exclamation-circle"></i>
                            <div>
                                <div v-for="error in errorMostrarMsjUser" :key="error" v-text="error"></div>
                            </div>
                        </div>

                        <p class="mb-3"><small style="color: var(--j2b-danger);">* Campos obligatorios</small></p>

                        <div v-if="tipoAccion==1 || tipoAccion==2">

                          <!-- Informacion del Usuario -->
                          <div class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-user"></i> Informacion del Usuario
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Nombre</label>
                                        <input type="text" class="j2b-input" v-model="name" placeholder="Nombre completo" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Email</label>
                                        <input type="email" class="j2b-input" v-model="email" placeholder="correo@ejemplo.com" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Tienda</label>
                                        <select class="j2b-select" v-model="shop_id">
                                            <option :value="null">Seleccione una tienda...</option>
                                            <option v-for="shop in arrayShops" :key="shop.id" :value="shop.id">{{ shop.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Rol</label>
                                        <select class="j2b-select" v-model="role_id">
                                            <option :value="null">Seleccione un rol...</option>
                                            <option v-for="r in arrayRoles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                          </div>

                          <!-- Contraseña (solo al crear) -->
                          <div v-if="tipoAccion==1" class="j2b-form-section">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-lock"></i> Contraseña
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Contraseña</label>
                                        <input type="password" class="j2b-input" v-model="password" required autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="j2b-form-group">
                                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Confirmar Contraseña</label>
                                        <input type="password" class="j2b-input" v-model="password_confirmation" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
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

    <!--Modal Cambiar Email-->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalEmail}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-envelope" style="color: var(--j2b-primary);"></i>
                        Cambiar Email
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModalEmail()" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <div class="j2b-banner-alert j2b-banner-info mb-3">
                        <i class="fa fa-user"></i>
                        <div>
                            <strong>Usuario:</strong> {{ emailUserName }}
                        </div>
                    </div>

                    <div class="j2b-form-group mb-3">
                        <label class="j2b-label">Email Actual</label>
                        <input type="email" class="j2b-input" :value="emailActual" disabled style="background-color: var(--j2b-gray-100);">
                    </div>

                    <div class="j2b-form-group mb-3">
                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Nuevo Email</label>
                        <input type="email" class="j2b-input" v-model="emailNuevo" placeholder="nuevo@email.com" required>
                    </div>

                    <div class="j2b-form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="checkVerificado" v-model="emailMarcarVerificado">
                            <label class="form-check-label" for="checkVerificado">
                                <i class="fa fa-check-circle text-success"></i> Marcar como verificado
                            </label>
                        </div>
                        <small class="text-muted">Si no se marca, el usuario deberá confirmar su email.</small>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModalEmail()">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="actualizarEmail()">
                        <i class="fa fa-save"></i> Guardar Email
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--Fin Modal Email-->

</div>
</template>

<script>
    export default {
        data(){
            return {
              arrayUsers:[],
              arrayShops:[],
              arrayRoles:[],
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
              shop:'',
              rol:'',

              shop_id:null,
              role_id:null,
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

              // Modal Email
              modalEmail: 0,
              emailUserId: 0,
              emailUserName: '',
              emailActual: '',
              emailNuevo: '',
              emailMarcarVerificado: true,
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
            loadUsers(page, buscar, criterio, estatus, shop, rol){
                let me=this;
                var url = '/superadmin/users/get?page='+page+'&buscar='+buscar+'&criterio='+criterio+'&estatus='+estatus+'&shop='+shop+'&rol='+rol;
                axios.get(url).then(function (response){
                    var respuesta  = response.data;
                    me.arrayUsers = respuesta.users.data;
                    me.arrayShops = respuesta.shops;
                    me.arrayRoles = respuesta.roles;
                    me.pagination = respuesta.pagination;
                  })
                  .catch(function (error) {
                    console.log(error);
                  });
            },
            buscarUsuarios(){
                this.loadUsers(1, this.buscar, this.criterio, this.estatus, this.shop, this.rol);
            },
            cambiarPagina(page){
                let me = this;
                me.pagination.current_page = page;
                me.loadUsers(page, me.buscar, me.criterio, me.estatus, me.shop, me.rol);
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
                        me.loadUsers(me.pagination.current_page, me.buscar, me.criterio, me.estatus, me.shop, me.rol);
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
                        me.loadUsers(me.pagination.current_page, me.buscar, me.criterio, me.estatus, me.shop, me.rol);
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
            toggleAI(user){
                let me = this;
                axios.put('/superadmin/users/toggle-ai', {
                    'id': user.id
                }).then(function (response){
                    // Actualizar localmente sin recargar toda la lista
                    user.can_use_ai = response.data.can_use_ai;
                    Swal.fire({
                        icon: 'success',
                        title: response.data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }).catch(function (error){
                    console.log(error);
                    me.mostrarError(error);
                });
            },
            generarPasswordAleatorio(){
                // Generar contraseña aleatoria de 12 caracteres
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@#$%&*';
                let password = '';
                for (let i = 0; i < 12; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return password;
            },
            copiarAlPortapapeles(texto, btnElement){
                // Copiar al portapapeles
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(texto).then(() => {
                        // Cambiar ícono del botón temporalmente
                        const iconoOriginal = btnElement.innerHTML;
                        btnElement.innerHTML = '<i class="fa fa-check"></i>';
                        btnElement.classList.add('btn-success');
                        btnElement.classList.remove('btn-secondary');

                        setTimeout(() => {
                            btnElement.innerHTML = iconoOriginal;
                            btnElement.classList.remove('btn-success');
                            btnElement.classList.add('btn-secondary');
                        }, 1500);
                    });
                } else {
                    // Fallback para navegadores antiguos
                    const textarea = document.createElement('textarea');
                    textarea.value = texto;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);

                    // Cambiar ícono del botón temporalmente
                    const iconoOriginal = btnElement.innerHTML;
                    btnElement.innerHTML = '<i class="fa fa-check"></i>';
                    btnElement.classList.add('btn-success');
                    btnElement.classList.remove('btn-secondary');

                    setTimeout(() => {
                        btnElement.innerHTML = iconoOriginal;
                        btnElement.classList.remove('btn-success');
                        btnElement.classList.add('btn-secondary');
                    }, 1500);
                }
            },
            resetearPassword(id){
                Swal.fire({
                    title: 'Resetear Contraseña',
                    html: `
                        <div class="form-group text-left">
                            <label for="swal-password" class="font-weight-bold">Nueva Contraseña</label>
                            <div class="input-group">
                                <input type="text" id="swal-password" class="form-control" placeholder="Mínimo 8 caracteres">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" id="btn-generar" title="Generar contraseña">
                                        <i class="fa fa-random"></i>
                                    </button>
                                    <button class="btn btn-secondary" type="button" id="btn-copiar" title="Copiar">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-left mt-3">
                            <label for="swal-password-confirm" class="font-weight-bold">Confirmar Contraseña</label>
                            <input type="text" id="swal-password-confirm" class="form-control">
                        </div>
                        <p class="text-muted small mt-2">
                            <i class="fa fa-info-circle"></i> Usa el botón <i class="fa fa-random"></i> para generar una contraseña aleatoria segura
                        </p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar Contraseña',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    width: '600px',
                    didOpen: () => {
                        // Agregar eventos a los botones
                        const btnGenerar = document.getElementById('btn-generar');
                        const btnCopiar = document.getElementById('btn-copiar');
                        const inputPassword = document.getElementById('swal-password');
                        const inputConfirm = document.getElementById('swal-password-confirm');

                        btnGenerar.addEventListener('click', () => {
                            const password = this.generarPasswordAleatorio();
                            inputPassword.value = password;
                            inputConfirm.value = password;
                        });

                        btnCopiar.addEventListener('click', () => {
                            const password = inputPassword.value;
                            if (password) {
                                this.copiarAlPortapapeles(password, btnCopiar);
                            } else {
                                Swal.showValidationMessage('Genera o escribe una contraseña primero');
                            }
                        });
                    },
                    preConfirm: () => {
                        const password = document.getElementById('swal-password').value;
                        const passwordConfirm = document.getElementById('swal-password-confirm').value;

                        if (!password) {
                            Swal.showValidationMessage('Ingresa la nueva contraseña');
                            return false;
                        }

                        if (password.length < 8) {
                            Swal.showValidationMessage('La contraseña debe tener al menos 8 caracteres');
                            return false;
                        }

                        if (password !== passwordConfirm) {
                            Swal.showValidationMessage('Las contraseñas no coinciden');
                            return false;
                        }

                        return { password, password_confirmation: passwordConfirm };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let me = this;

                        // 🔍 LOG: Ver qué datos se envían
                        const dataToSend = {
                            'id': id,
                            'password': result.value.password,
                            'password_confirmation': result.value.password_confirmation
                        };
                        console.log('🔍 [RESET PASSWORD] Enviando al servidor:', dataToSend);

                        axios.put('/superadmin/users/reset-password', dataToSend)
                        .then(function (response){
                            // 🔍 LOG: Ver respuesta completa del servidor
                            console.log('✅ [RESET PASSWORD] Respuesta del servidor:', response);
                            console.log('✅ [RESET PASSWORD] Data:', response.data);
                            console.log('✅ [RESET PASSWORD] Status:', response.status);

                            Swal.fire({
                                icon: 'success',
                                title: '¡Contraseña Actualizada!',
                                html: `
                                    <p>La nueva contraseña es:</p>
                                    <h4 class="text-primary"><strong>${result.value.password}</strong></h4>
                                    <p class="text-muted">Comparte esta contraseña con el usuario</p>
                                `,
                                confirmButtonText: 'Entendido'
                            });
                        }).catch(function (error){
                            // 🔍 LOG: Ver error completo
                            console.error('❌ [RESET PASSWORD] Error completo:', error);
                            console.error('❌ [RESET PASSWORD] Response:', error.response);
                            console.error('❌ [RESET PASSWORD] Status:', error.response?.status);
                            console.error('❌ [RESET PASSWORD] Data:', error.response?.data);

                            let errorMsg = 'Ocurrió un error al actualizar la contraseña.';
                            if (error.response && error.response.data && error.response.data.errors) {
                                errorMsg = Object.values(error.response.data.errors).flat().join('<br>');
                            }
                            Swal.fire(
                                'Error!',
                                errorMsg,
                                'error'
                            );
                        });
                    }
                });
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
                  'role_id':me.role_id,
                }).then(function (response){
                  me.cerrarModal();
                  me.loadUsers(me.pagination.current_page, me.buscar, me.criterio, me.estatus, me.shop, me.rol)
                  Swal.fire(
                    'Exito!',
                    'Usuario creado correctamente.',
                    'success'
                  );
                }).catch(function (error){
                    console.log(error);
                    me.mostrarError(error);
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
                  me.loadUsers(me.pagination.current_page, me.buscar, me.criterio, me.estatus, me.shop, me.rol)
                  Swal.fire(
                    'Exito!',
                    'Actualización correcta.',
                    'success'
                  );
                }).catch(function (error){
                    console.log(error);
                    me.mostrarError(error);
                });
            },
            validarDatos(accion){
                this.errorUser=0;
                this.errorMostrarMsjUser=[];
                if(accion=='registrar'){
                  if(!this.shop_id) this.errorMostrarMsjUser.push('Debe seleccionar una tienda.');
                  if(!this.role_id) this.errorMostrarMsjUser.push('Debe seleccionar un rol.');
                  if(!this.name) this.errorMostrarMsjUser.push('El nombre no puede estar vacio.');
                  if(!this.email) this.errorMostrarMsjUser.push('El email no puede estar vacio.');
                  if(!this.password) this.errorMostrarMsjUser.push('El password no puede estar vacio.');
                  if(this.password!==this.password_confirmation) this.errorMostrarMsjUser.push('Los passwords no coinciden.');
                }
                if(accion=='actualizar'){
                  if(!this.shop_id) this.errorMostrarMsjUser.push('Debe seleccionar una tienda.');
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
                                this.tituloModal='Nuevo Usuario';
                                this.name='';
                                this.email ='';
                                this.password ='';
                                this.password_confirmation ='';
                                this.shop_id=null;
                                this.role_id=null;
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
            abrirModalEmail(user){
                this.modalEmail = 1;
                this.emailUserId = user.id;
                this.emailUserName = user.name;
                this.emailActual = user.email;
                this.emailNuevo = user.email;
                this.emailMarcarVerificado = true;
            },
            cerrarModalEmail(){
                this.modalEmail = 0;
                this.emailUserId = 0;
                this.emailUserName = '';
                this.emailActual = '';
                this.emailNuevo = '';
            },
            actualizarEmail(){
                if (!this.emailNuevo || !this.emailNuevo.trim()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campo Requerido',
                        text: 'El email no puede estar vacío.',
                    });
                    return;
                }

                // Validar formato de email básico
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.emailNuevo)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Formato Inválido',
                        text: 'Por favor ingresa un email válido.',
                    });
                    return;
                }

                let me = this;
                axios.put('/superadmin/users/update-email', {
                    user_id: me.emailUserId,
                    email: me.emailNuevo,
                    mark_verified: me.emailMarcarVerificado
                }).then(function(response){
                    me.cerrarModalEmail();
                    me.loadUsers(me.pagination.current_page, me.buscar, me.criterio, me.estatus, me.shop, me.rol);
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Actualizado',
                        html: `El email ha sido cambiado a:<br><strong>${me.emailNuevo}</strong>`,
                    });
                }).catch(function(error){
                    console.log(error);
                    me.mostrarError(error);
                });
            },
            mostrarError(error){
                let titulo = 'Error';
                let mensaje = '';
                let icono = 'error';

                // Sin respuesta del servidor (error de red/conexión)
                if (!error.response) {
                    titulo = 'Error de Conexión';
                    mensaje = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.';
                    Swal.fire(titulo, mensaje, icono);
                    return;
                }

                const status = error.response.status;
                const data = error.response.data;

                switch (status) {
                    case 422: // Errores de validación
                        titulo = 'Datos Inválidos';
                        icono = 'warning';
                        if (data.errors) {
                            // Extraer todos los mensajes de error de validación
                            const errores = Object.values(data.errors).flat();
                            mensaje = errores.map(err => this.traducirErrorValidacion(err)).join('<br>');
                        } else if (data.message) {
                            mensaje = this.traducirErrorValidacion(data.message);
                        } else {
                            mensaje = 'Por favor verifica los datos ingresados.';
                        }
                        break;

                    case 403: // Forbidden
                        titulo = 'Acceso Denegado';
                        icono = 'warning';
                        mensaje = data.error || data.message || 'No tienes permiso para realizar esta acción.';
                        break;

                    case 404: // Not Found
                        titulo = 'No Encontrado';
                        icono = 'warning';
                        mensaje = data.message || 'El recurso solicitado no existe.';
                        break;

                    case 401: // Unauthorized
                        titulo = 'Sesión Expirada';
                        icono = 'warning';
                        mensaje = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.';
                        break;

                    case 500: // Error de servidor
                        titulo = 'Error del Servidor';
                        mensaje = 'Ocurrió un error interno en el servidor. Contacta al administrador del sistema.';
                        break;

                    default:
                        titulo = 'Error';
                        mensaje = data.message || 'Ocurrió un error inesperado. Intenta nuevamente.';
                }

                Swal.fire({
                    icon: icono,
                    title: titulo,
                    html: mensaje,
                    confirmButtonText: 'Entendido'
                });
            },
            traducirErrorValidacion(mensaje){
                // Traducciones de mensajes comunes de Laravel
                const traducciones = {
                    'The email has already been taken.': 'El correo electrónico ya está registrado.',
                    'The email field is required.': 'El correo electrónico es obligatorio.',
                    'The email must be a valid email address.': 'El correo electrónico no es válido.',
                    'The name field is required.': 'El nombre es obligatorio.',
                    'The password field is required.': 'La contraseña es obligatoria.',
                    'The password must be at least 8 characters.': 'La contraseña debe tener al menos 8 caracteres.',
                    'The shop_id field is required.': 'Debe seleccionar una tienda.',
                    'The role_id field is required.': 'Debe seleccionar un rol.',
                    'The selected shop_id is invalid.': 'La tienda seleccionada no es válida.',
                    'The selected role_id is invalid.': 'El rol seleccionado no es válido.',
                    'The selected user_id is invalid.': 'El usuario seleccionado no es válido.',
                };
                return traducciones[mensaje] || mensaje;
            },
        },
        mounted() {
            this.loadUsers(1, this.buscar, this.criterio, this.estatus, this.shop, this.rol);
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
