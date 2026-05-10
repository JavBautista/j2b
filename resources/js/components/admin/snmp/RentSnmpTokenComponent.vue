<template>
    <div class="card mb-3">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center py-2">
            <div>
                <i class="fa fa-cloud-download"></i>
                <strong class="ms-1">Agente SNMP</strong>
                <small class="ms-2 d-none d-md-inline">Token para esta renta</small>
            </div>
            <button class="btn btn-sm btn-outline-light" @click="abierto = !abierto">
                <i class="fa" :class="abierto ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                {{ abierto ? 'Ocultar' : 'Mostrar' }}
            </button>
        </div>

        <div v-show="abierto" class="card-body">
            <div v-if="cargando" class="text-center py-3">
                <i class="fa fa-spinner fa-spin"></i> Cargando...
            </div>

            <div v-else-if="!token">
                <p class="text-muted mb-2 small">Esta renta no tiene token de agente SNMP. Genera uno para que el script remoto solo lea los equipos de esta renta.</p>
                <button class="btn btn-success btn-sm" @click="regenerar()">
                    <i class="fa fa-key"></i> Generar token
                </button>
            </div>

            <div v-else>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge" :class="token.active ? 'bg-success' : 'bg-secondary'">
                        {{ token.active ? 'Activo' : 'Desactivado' }}
                    </span>
                    <small v-if="token.last_used_at" class="text-muted">
                        Último uso: {{ formatFecha(token.last_used_at) }}
                        <span v-if="token.last_used_ip">desde {{ token.last_used_ip }}</span>
                    </small>
                    <small v-else class="text-muted fst-italic">Aún sin uso registrado.</small>
                </div>

                <label class="form-label small mb-1">Token (Bearer)</label>
                <div class="input-group input-group-sm mb-2">
                    <input
                        :type="mostrarToken ? 'text' : 'password'"
                        class="form-control font-monospace"
                        :value="token.token"
                        readonly
                    />
                    <button class="btn btn-outline-secondary" type="button" @click="mostrarToken = !mostrarToken">
                        <i class="fa" :class="mostrarToken ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                    <button class="btn btn-outline-primary" type="button" @click="copiar()">
                        <i class="fa fa-clipboard"></i> Copiar
                    </button>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-sm" :class="token.active ? 'btn-outline-warning' : 'btn-outline-success'" @click="toggle()">
                        <i class="fa" :class="token.active ? 'fa-pause' : 'fa-play'"></i>
                        {{ token.active ? 'Desactivar' : 'Activar' }}
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="regenerar()">
                        <i class="fa fa-refresh"></i> Regenerar
                    </button>
                </div>

                <div class="small mt-2 text-muted">
                    <i class="fa fa-info-circle"></i>
                    El agente Python instalado en la red de esta renta solo recibirá los equipos de esta renta (no los de otras sucursales).
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RentSnmpTokenComponent',

    props: {
        rentId: { type: [Number, String], required: true },
    },

    data() {
        return {
            cargando: true,
            abierto: false,
            token: null,
            mostrarToken: false,
        };
    },

    mounted() {
        this.cargar();
    },

    methods: {
        async cargar() {
            this.cargando = true;
            try {
                const res = await axios.get(`/admin/rents/${this.rentId}/snmp-token`);
                this.token = res.data.token;
            } catch (err) {
                Swal.fire('Error', 'No se pudo cargar el token SNMP.', 'error');
            } finally {
                this.cargando = false;
            }
        },

        async regenerar() {
            const yaExiste = !!this.token;
            if (yaExiste) {
                const r = await Swal.fire({
                    title: '¿Regenerar token?',
                    text: 'El token actual dejará de funcionar inmediatamente. Tu agente Python tendrá que actualizar el token configurado.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, regenerar',
                    cancelButtonText: 'Cancelar',
                });
                if (!r.isConfirmed) return;
            }

            try {
                const res = await axios.post(`/admin/rents/${this.rentId}/snmp-token/regenerate`);
                this.token = res.data.token;
                this.mostrarToken = true;
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.data.message, showConfirmButton: false, timer: 2200 });
            } catch (err) {
                Swal.fire('Error', err.response?.data?.message || 'No se pudo generar el token.', 'error');
            }
        },

        async toggle() {
            try {
                const res = await axios.post(`/admin/rents/${this.rentId}/snmp-token/toggle`);
                this.token = res.data.token;
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.data.message, showConfirmButton: false, timer: 1800 });
            } catch (err) {
                Swal.fire('Error', err.response?.data?.message || 'No se pudo cambiar el estado.', 'error');
            }
        },

        async copiar() {
            try {
                await navigator.clipboard.writeText(this.token.token);
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Token copiado', showConfirmButton: false, timer: 1400 });
            } catch (err) {
                Swal.fire('Error', 'No se pudo copiar al portapapeles.', 'error');
            }
        },

        formatFecha(iso) {
            return this.$filters.formatDate(iso, 'DD/MM/YYYY HH:mm');
        },
    },
};
</script>
