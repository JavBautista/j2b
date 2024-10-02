<template>
  <div>

  <div class="container-fluid">

        <!-- Ejemplo de tabla Listado -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-align-justify"></i> Pre Registros
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <select class="form-control col-md-3" v-model="criterio">
                              <option value="name">Nombre</option>
                              <option value="description">Descripción</option>
                            </select>
                            <input type="text" v-model="buscar" class="form-control" placeholder="Texto a buscar" @keyup.enter="loadRegistros(1,buscar,criterio)">
                            <button type="submit" @click="loadRegistros(1,buscar,criterio)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Confirmado</th>
                            <th>Nombre</th>
                            <th>Tel.</th>
                            <th>Email</th>
                            <th>Obs.</th>
                            <th>Creación</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                          <tr v-for="reg in arrayRegistros" :key="reg.id">
                              <td v-text="reg.id"></td>
                              <td>
                                  <span v-if="reg.confirmed == 0" class="badge bg-danger">NO</span>
                                  <span v-else class="badge bg-success">SI</span>
                              </td>
                              <td v-text="reg.name"></td>
                              <td v-text="reg.phone"></td>
                              <td v-text="reg.email"></td>
                              <td v-text="reg.observations"></td>
                              <td>{{reg.created_at | formatDate }}</td>
                              <td>
                                <button v-if="reg.confirmed == 0" @click="eliminar(reg.id)" class="btn btn-sm btn-danger">
                                    Eliminar
                                </button>
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

</div>
</template>

<script>
    export default {
        data(){
            return {
              arrayRegistros:[],
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

              request_id:0,
              confirmed:0,
              token:'',
              name:'',
              phone:'',
              email:'',
              observations:'',

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
                me.loadRegistros(page,buscar,criterio);
            },
            eliminar(id){
              const swalWithBootstrapButtons = Swal.mixin({
                  customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                  },
                  buttonsStyling: false
                })

                swalWithBootstrapButtons.fire({
                  title: '¿Desea cambiar eliminar el registro?',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonText: 'Aceptar',
                  cancelButtonText: 'Cancelar',
                  reverseButtons: true
                }).then((result) => {
                  if (result.value) {

                    let me=this;
                    axios.put('/superadmin/pre-registers/delete',{
                        'id': id
                    }).then(function (response){
                        me.loadRegistros(me.pagination.current_page,me.buscar,me.criterio);
                        swalWithBootstrapButtons.fire(
                          '¡Inactivo!',
                          'Eliminación exitosa.',
                          'success'
                        )
                    }).catch(function (error){
                        console.log(error);
                    });

                  }
                })
            },
            loadRegistros(page,buscar,criterio){
                let me=this;
                var url = '/superadmin/pre-registers/get?page='+page+'&buscar='+buscar+'&criterio='+criterio;
                axios.get(url).then(function (response){
                    var respuesta  = response.data;
                    me.arrayRegistros = respuesta.registers.data;
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
        },
        mounted() {
            this.loadRegistros(1,'','name');
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
