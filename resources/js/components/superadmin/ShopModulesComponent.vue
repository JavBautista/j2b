<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="/superadmin/subscription-management" class="j2b-btn j2b-btn-outline mb-2" style="font-size: 0.85rem;">
                    <i class="fa fa-arrow-left"></i> Volver a Suscripciones
                </a>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-th-large" style="color: var(--j2b-primary);"></i> Módulos de {{ shopName }}
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Activa o desactiva funcionalidades y define su precio para esta tienda</p>
            </div>
        </div>

        <!-- Info de la tienda -->
        <div class="j2b-card mb-4" v-if="shop">
            <div class="j2b-card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <small class="j2b-text-muted">Estado Suscripción</small>
                        <div>
                            <span class="j2b-badge" :class="getStatusBadgeClass(shop.subscription_status)">
                                {{ getStatusLabel(shop.subscription_status) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <small class="j2b-text-muted">Renta base (módulos core)</small>
                        <div><strong>${{ formatNumber(shop.monthly_price || 0) }}</strong> <small class="j2b-text-muted">/mes</small></div>
                    </div>
                    <div class="col-md-4">
                        <small class="j2b-text-muted">Extra por módulos vendibles activos</small>
                        <div><strong style="color: var(--j2b-primary);">${{ formatNumber(totalVendibles) }}</strong> <small class="j2b-text-muted">/mes</small></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aviso core -->
        <div class="j2b-banner-alert j2b-banner-info mb-3">
            <i class="fa fa-info-circle"></i>
            Los <strong>módulos base</strong> (clientes, ventas, compras, etc.) están <strong>incluidos siempre</strong> en la renta y no se pueden desactivar. Abajo administras los <strong>módulos vendibles</strong>.
        </div>

        <!-- Card de módulos -->
        <div class="j2b-card">
            <div class="j2b-card-body p-0">
                <div v-if="loading" class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color: var(--j2b-primary);"></i>
                    <p class="mt-2 j2b-text-muted">Cargando módulos...</p>
                </div>

                <div v-else class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th>Módulo</th>
                                <th style="width: 110px;">Tipo</th>
                                <th style="width: 140px;">Estado</th>
                                <th style="width: 170px;">Precio pactado</th>
                                <th style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in modules" :key="m.id">
                                <td>
                                    <i :class="'fa ' + (m.icon || 'fa-cube')" style="color: var(--j2b-gray-500);"></i>
                                    <strong class="ml-1">{{ m.name }}</strong>
                                    <div v-if="m.requires && m.requires.length" class="j2b-text-muted">
                                        <small><i class="fa fa-link"></i> requiere: {{ m.requires.join(', ') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span v-if="m.is_core" class="j2b-badge j2b-badge-secondary">Base</span>
                                    <span v-else class="j2b-badge j2b-badge-info">Vendible</span>
                                </td>
                                <td>
                                    <span v-if="m.is_core" class="j2b-badge j2b-badge-success">
                                        <i class="fa fa-check"></i> Incluido
                                    </span>
                                    <label v-else class="j2b-switch-label">
                                        <input type="checkbox" v-model="m.enabled">
                                        <span :class="m.enabled ? 'text-success' : 'j2b-text-muted'">
                                            {{ m.enabled ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <div v-if="!m.is_core" class="d-flex align-items-center gap-1">
                                        <span class="j2b-badge j2b-badge-dark">$</span>
                                        <input type="number" class="j2b-input" v-model.number="m.price" step="0.01" min="0" placeholder="0.00" style="max-width: 110px;">
                                    </div>
                                    <small v-else class="j2b-text-muted">—</small>
                                </td>
                                <td>
                                    <button v-if="!m.is_core" type="button" class="j2b-btn j2b-btn-sm j2b-btn-primary" @click="guardarModulo(m)" :disabled="savingId === m.id">
                                        <i class="fa fa-save"></i> {{ savingId === m.id ? '...' : 'Guardar' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
  </div>
</template>

<script>
export default {
    props: {
        shopId: {
            type: Number,
            required: true
        },
        shopName: {
            type: String,
            required: true
        }
    },

    data() {
        return {
            loading: false,
            savingId: null,
            shop: null,
            modules: []
        }
    },

    computed: {
        totalVendibles() {
            return this.modules
                .filter(m => !m.is_core && m.enabled)
                .reduce((acc, m) => acc + (parseFloat(m.price) || 0), 0);
        }
    },

    mounted() {
        this.loadModules();
    },

    methods: {
        loadModules() {
            this.loading = true;
            axios.get(`/superadmin/subscription-management/${this.shopId}/modules/get`)
                .then(response => {
                    this.shop = response.data.shop;
                    this.modules = response.data.modules;
                })
                .catch(() => this.mostrarError('Error al cargar los módulos'))
                .finally(() => { this.loading = false; });
        },

        guardarModulo(m) {
            this.savingId = m.id;
            axios.put(`/superadmin/subscription-management/${this.shopId}/modules/${m.id}`, {
                enabled: m.enabled,
                price: (m.price === '' || m.price === null || m.price === undefined) ? null : m.price,
                notes: m.notes || null
            })
            .then(response => {
                this.mostrarExito(response.data.message || 'Módulo actualizado');
                this.loadModules();
            })
            .catch(error => {
                this.mostrarError(error.response?.data?.message || 'Error al actualizar el módulo');
                this.loadModules(); // revertir el estado visual al real
            })
            .finally(() => { this.savingId = null; });
        },

        formatNumber(value) {
            if (value === null || value === undefined) return '0.00';
            return Number(value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        getStatusLabel(status) {
            const labels = {
                'trial': 'Trial',
                'active': 'Activo',
                'grace_period': 'En Gracia',
                'expired': 'Vencido',
                'cancelled': 'Cancelado'
            };
            return labels[status] || status;
        },

        getStatusBadgeClass(status) {
            const classes = {
                'trial': 'j2b-badge-info',
                'active': 'j2b-badge-success',
                'grace_period': 'j2b-badge-warning',
                'expired': 'j2b-badge-danger',
                'cancelled': 'j2b-badge-secondary'
            };
            return classes[status] || 'j2b-badge-secondary';
        },

        mostrarExito(mensaje) {
            Swal.fire({ icon: 'success', title: 'Éxito', text: mensaje, confirmButtonColor: '#38b2ac' });
        },

        mostrarError(mensaje) {
            Swal.fire({ icon: 'error', title: 'Error', text: mensaje, confirmButtonColor: '#e53e3e' });
        }
    }
}
</script>

<style scoped>
.j2b-switch-label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    margin: 0;
    font-weight: 500;
}

.j2b-switch-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.gap-1 {
    gap: 0.25rem;
}

.ml-1 {
    margin-left: 0.25rem;
}
</style>
