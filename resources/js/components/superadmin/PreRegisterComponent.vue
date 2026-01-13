<template>
<div class="container-fluid" style="padding: 1.5rem;">

    <!-- Header con titulo -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-clipboard" style="color: var(--j2b-primary);"></i> Pre-Registros
            </h4>
            <p class="mb-0" style="color: var(--j2b-gray-500);">Solicitudes de registro recibidas</p>
        </div>
        <span class="j2b-badge j2b-badge-info">
            <i class="fa fa-list"></i> {{ pagination.total }} solicitudes
        </span>
    </div>

    <!-- Estadisticas rapidas -->
    <div class="j2b-stat-grid mb-4" style="grid-template-columns: repeat(2, 1fr);">
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-success">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ confirmedCount }}</div>
                <div class="j2b-stat-label">Confirmados</div>
            </div>
        </div>
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-warning">
                <i class="fa fa-clock-o"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ pendingCount }}</div>
                <div class="j2b-stat-label">Pendientes</div>
            </div>
        </div>
    </div>

    <!-- Card principal -->
    <div class="j2b-card">
        <div class="j2b-card-header">
            <h5 class="j2b-card-title mb-0">
                <i class="fa fa-list j2b-text-primary"></i> Listado de Solicitudes
            </h5>
        </div>
        <div class="j2b-card-body">
            <!-- Filtros de busqueda -->
            <div class="row mb-4">
                <div class="col-md-10">
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="j2b-select" v-model="estatus" @change="loadRegistros(1,buscar,criterio,estatus)" style="width: 140px;">
                            <option value="">Todos</option>
                            <option value="confirmed">Confirmados</option>
                            <option value="pending">Pendientes</option>
                        </select>
                        <select class="j2b-select" v-model="criterio" style="width: 130px;">
                            <option value="name">Nombre</option>
                            <option value="email">Email</option>
                            <option value="phone">Telefono</option>
                        </select>
                        <div class="d-flex" style="flex: 1; min-width: 200px;">
                            <input
                                type="text"
                                v-model="buscar"
                                class="j2b-input"
                                placeholder="Buscar..."
                                @keyup.enter="loadRegistros(1,buscar,criterio,estatus)"
                                style="border-radius: var(--j2b-radius-md) 0 0 var(--j2b-radius-md);"
                            >
                            <button
                                @click="loadRegistros(1,buscar,criterio,estatus)"
                                class="j2b-btn j2b-btn-primary"
                                style="border-radius: 0 var(--j2b-radius-md) var(--j2b-radius-md) 0;"
                            >
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                        <button
                            v-if="buscar || estatus"
                            @click="buscar=''; estatus=''; loadRegistros(1,'',criterio,'')"
                            class="j2b-btn j2b-btn-outline"
                        >
                            <i class="fa fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="j2b-table-responsive">
                <table class="j2b-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 100px;">Estado</th>
                            <th>Nombre</th>
                            <th style="width: 120px;">Telefono</th>
                            <th>Email</th>
                            <th>Observaciones</th>
                            <th style="width: 100px;">Fecha</th>
                            <th style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="reg in arrayRegistros" :key="reg.id">
                            <td>
                                <span class="j2b-badge j2b-badge-dark">{{ reg.id }}</span>
                            </td>
                            <td>
                                <span v-if="reg.confirmed == 0" class="j2b-badge j2b-badge-warning">
                                    <i class="fa fa-clock-o"></i> Pendiente
                                </span>
                                <span v-else class="j2b-badge j2b-badge-success">
                                    <i class="fa fa-check"></i> Confirmado
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 11px; width: 32px; height: 32px; flex-shrink: 0;">
                                        {{ reg.name ? reg.name.charAt(0).toUpperCase() : '?' }}
                                    </div>
                                    <strong style="color: var(--j2b-dark);">{{ reg.name }}</strong>
                                </div>
                            </td>
                            <td>
                                <span v-if="reg.phone" style="color: var(--j2b-gray-600);">
                                    <i class="fa fa-phone j2b-text-primary"></i> {{ reg.phone }}
                                </span>
                                <span v-else class="j2b-text-muted">-</span>
                            </td>
                            <td>
                                <span v-if="reg.email" style="color: var(--j2b-gray-600);">
                                    <i class="fa fa-envelope j2b-text-info"></i> {{ reg.email }}
                                </span>
                                <span v-else class="j2b-text-muted">-</span>
                            </td>
                            <td>
                                <span v-if="reg.observations" style="color: var(--j2b-gray-600); font-size: 0.85em;">
                                    {{ reg.observations.length > 50 ? reg.observations.substring(0,50) + '...' : reg.observations }}
                                </span>
                                <span v-else class="j2b-text-muted">-</span>
                            </td>
                            <td>
                                <small style="color: var(--j2b-gray-500);">{{ reg.created_at | formatDate }}</small>
                            </td>
                            <td>
                                <button
                                    v-if="reg.confirmed == 0"
                                    @click="eliminar(reg.id)"
                                    class="j2b-btn j2b-btn-sm j2b-btn-danger"
                                    title="Eliminar"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>
                                <span v-else class="j2b-text-muted">-</span>
                            </td>
                        </tr>
                        <tr v-if="arrayRegistros.length === 0">
                            <td colspan="8" class="text-center py-5">
                                <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                <p style="color: var(--j2b-gray-500);">No hay solicitudes registradas</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginacion -->
            <div v-if="pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4">
                <div>
                    <small style="color: var(--j2b-gray-500);">
                        Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} registros
                    </small>
                </div>
                <nav>
                    <ul class="pagination mb-0" style="gap: 4px;">
                        <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                            <a
                                class="j2b-btn j2b-btn-sm j2b-btn-outline"
                                href="#"
                                @click.prevent="cambiarPagina(pagination.current_page-1,buscar,criterio,estatus)"
                                :style="pagination.current_page <= 1 ? 'opacity: 0.5; pointer-events: none;' : ''"
                            >
                                <i class="fa fa-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page">
                            <a
                                class="j2b-btn j2b-btn-sm"
                                :class="page == isActived ? 'j2b-btn-primary' : 'j2b-btn-outline'"
                                href="#"
                                @click.prevent="cambiarPagina(page,buscar,criterio,estatus)"
                            >
                                {{ page }}
                            </a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                            <a
                                class="j2b-btn j2b-btn-sm j2b-btn-outline"
                                href="#"
                                @click.prevent="cambiarPagina(pagination.current_page+1,buscar,criterio,estatus)"
                                :style="pagination.current_page >= pagination.last_page ? 'opacity: 0.5; pointer-events: none;' : ''"
                            >
                                <i class="fa fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
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
            estatus:'',
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
        },
        confirmedCount: function(){
            return this.arrayRegistros.filter(r => r.confirmed == 1).length;
        },
        pendingCount: function(){
            return this.arrayRegistros.filter(r => r.confirmed == 0).length;
        }
    },
    methods : {
        cambiarPagina(page,buscar,criterio,estatus){
            let me = this;
            me.pagination.current_page = page;
            me.loadRegistros(page,buscar,criterio,estatus);
        },
        eliminar(id){
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success ml-2',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            })

            swalWithBootstrapButtons.fire({
                title: '¿Eliminar este registro?',
                text: 'Esta accion no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    let me=this;
                    axios.put('/superadmin/pre-registers/delete',{
                        'id': id
                    }).then(function (response){
                        me.loadRegistros(me.pagination.current_page,me.buscar,me.criterio,me.estatus);
                        swalWithBootstrapButtons.fire(
                            'Eliminado',
                            'El registro ha sido eliminado.',
                            'success'
                        )
                    }).catch(function (error){
                        console.log(error);
                    });
                }
            })
        },
        loadRegistros(page,buscar,criterio,estatus){
            let me=this;
            var url = '/superadmin/pre-registers/get?page='+page+'&buscar='+buscar+'&criterio='+criterio+'&estatus='+estatus;
            axios.get(url).then(function (response){
                var respuesta  = response.data;
                me.arrayRegistros = respuesta.registers.data;
                me.pagination = respuesta.pagination;
            })
            .catch(function (error) {
                console.log(error);
            });
        },
    },
    mounted() {
        this.loadRegistros(1,'','name','');
    }
}
</script>

<style scoped>
.pagination {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
}
.page-item {
    margin: 0;
}
</style>
