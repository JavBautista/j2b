<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

      <!-- Header con título y botón -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
            <i class="fa fa-tags" style="color: var(--j2b-primary);"></i> Tarifas J2 Monitor
          </h4>
          <p class="mb-0" style="color: var(--j2b-gray-500);">
            Catálogo de cobro por rango de equipos del servicio de monitoreo SNMP
          </p>
        </div>
        <button type="button" @click="abrirModalNuevo" class="j2b-btn j2b-btn-primary">
          <i class="fa fa-plus"></i> Nuevo Tier
        </button>
      </div>

      <!-- Card principal -->
      <div class="j2b-card">
        <div class="j2b-card-body p-0">
          <div class="j2b-table-responsive">
            <table class="j2b-table">
              <thead>
                <tr>
                  <th style="width: 60px;">ID</th>
                  <th>Nombre</th>
                  <th style="width: 130px;">Rango equipos</th>
                  <th style="width: 130px;">Tipo</th>
                  <th style="width: 160px;">Precio</th>
                  <th style="width: 130px;">Incluye base</th>
                  <th style="width: 90px;">Moneda</th>
                  <th style="width: 100px;">Estado</th>
                  <th style="width: 150px;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tier in tiers" :key="tier.id">
                  <td><span class="j2b-badge j2b-badge-dark">{{ tier.id }}</span></td>
                  <td>
                    <strong>{{ tier.name }}</strong>
                    <span v-if="enUso(tier.id)" class="j2b-badge j2b-badge-warning ml-2" style="font-size:10px;">
                      EN USO
                    </span>
                  </td>
                  <td>
                    {{ tier.min_equipment }} <span style="color:#999">–</span>
                    {{ tier.max_equipment !== null ? tier.max_equipment : '∞' }}
                  </td>
                  <td>
                    <span v-if="tier.is_flat_rate" class="j2b-badge j2b-badge-info">Tarifa plana</span>
                    <span v-else class="j2b-badge j2b-badge-secondary">Por equipo</span>
                  </td>
                  <td>
                    <strong v-if="tier.is_flat_rate">${{ format(tier.flat_amount) }}</strong>
                    <strong v-else>${{ format(tier.price_per_equipment) }} <small style="color:#777;">/eq</small></strong>
                  </td>
                  <td>
                    <i v-if="tier.includes_base_plan" class="fa fa-check" style="color: var(--j2b-success, #28a745);"></i>
                    <span v-else style="color:#aaa">—</span>
                  </td>
                  <td>{{ tier.currency }}</td>
                  <td>
                    <span :class="['j2b-badge', tier.active ? 'j2b-badge-success' : 'j2b-badge-danger']">
                      {{ tier.active ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td>
                    <button class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="abrirModalEditar(tier)" title="Editar">
                      <i class="fa fa-pencil"></i>
                    </button>
                    <button
                      class="j2b-btn j2b-btn-sm"
                      :class="tier.active ? 'j2b-btn-warning' : 'j2b-btn-success'"
                      @click="toggleActive(tier)"
                      :title="tier.active ? 'Desactivar' : 'Activar'">
                      <i :class="tier.active ? 'fa fa-pause' : 'fa fa-play'"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="tiers.length === 0">
                  <td colspan="9" class="text-center" style="padding:24px; color:#999;">
                    No hay tiers configurados.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Nota informativa -->
      <div class="mt-3" style="font-size:13px; color:#666;">
        <i class="fa fa-info-circle"></i>
        <strong>Notas:</strong>
        El precio se aplica al <em>total</em> de licencias contratadas por la tienda (todos los equipos al precio del tier).
        Los tiers marcados <strong>EN USO</strong> tienen pagos históricos asociados — solo se puede editar precio, moneda, orden y estado.
        Para cambiar rango o tipo, desactiva el tier y crea uno nuevo.
      </div>
    </div>

    <!-- Modal Crear / Editar -->
    <div class="modal fade" tabindex="-1" :class="{ mostrar: showModal }" role="dialog" style="display:none;" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content j2b-modal-content">
          <div class="modal-header j2b-modal-header">
            <h5 class="modal-title">
              <i class="fa fa-tags" style="color: var(--j2b-primary);"></i>
              {{ modoEditar ? 'Editar tier' : 'Nuevo tier' }}
            </h5>
            <button type="button" class="j2b-modal-close" @click="cerrarModal" aria-label="Close">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <div class="modal-body j2b-modal-body">
            <div v-if="erroresValidacion.length" class="j2b-banner-alert j2b-banner-danger mb-3">
              <i class="fa fa-exclamation-circle"></i>
              <div>
                <div v-for="(e,i) in erroresValidacion" :key="i" v-text="e"></div>
              </div>
            </div>

            <p class="mb-3"><small style="color: var(--j2b-danger);">* Campos obligatorios</small></p>

            <div class="row">
            <div class="col-md-12 mb-3">
              <label class="j2b-label">Nombre del tier *</label>
              <input v-model="form.name" type="text" class="j2b-input" :disabled="rangoBloqueado" placeholder='ej. "11 a 25 equipos"'>
              <small v-if="rangoBloqueado" style="color:#c97a00;">El nombre no se puede cambiar: el tier está en uso.</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="j2b-label">Equipos mínimo *</label>
              <input v-model.number="form.min_equipment" type="number" min="1" class="j2b-input" :disabled="rangoBloqueado">
            </div>
            <div class="col-md-6 mb-3">
              <label class="j2b-label">Equipos máximo</label>
              <input v-model.number="form.max_equipment" type="number" min="1" class="j2b-input" :disabled="rangoBloqueado" placeholder="Vacío = sin tope (ej. 381+)">
              <small style="color:#999;">Déjalo vacío para tier abierto (sin tope).</small>
            </div>

            <div class="col-md-12 mb-3">
              <label class="j2b-label" style="display:block; margin-bottom:8px;">Tipo de tarifa *</label>
              <label style="margin-right:24px;">
                <input type="radio" :value="false" v-model="form.is_flat_rate" :disabled="rangoBloqueado">
                Por equipo (cuenta × precio)
              </label>
              <label>
                <input type="radio" :value="true" v-model="form.is_flat_rate" :disabled="rangoBloqueado">
                Tarifa plana (monto fijo, independiente del conteo)
              </label>
            </div>

            <div class="col-md-6 mb-3" v-if="!form.is_flat_rate">
              <label class="j2b-label">Precio por equipo (sin IVA) *</label>
              <input v-model.number="form.price_per_equipment" type="number" step="0.01" min="0" class="j2b-input" placeholder="ej. 25.00">
            </div>

            <div class="col-md-6 mb-3" v-if="form.is_flat_rate">
              <label class="j2b-label">Monto fijo (sin IVA) *</label>
              <input v-model.number="form.flat_amount" type="number" step="0.01" min="0" class="j2b-input" placeholder="ej. 4000.00">
            </div>

            <div class="col-md-6 mb-3" v-if="form.is_flat_rate">
              <label class="j2b-label">¿Incluye plan base J2Biznes?</label>
              <div>
                <label style="margin-right:24px;">
                  <input type="radio" :value="true" v-model="form.includes_base_plan" :disabled="rangoBloqueado"> Sí
                </label>
                <label>
                  <input type="radio" :value="false" v-model="form.includes_base_plan" :disabled="rangoBloqueado"> No
                </label>
              </div>
              <small style="color:#999;">Si está activo, el plan base de la tienda no se suma al cobro mensual.</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="j2b-label">Moneda *</label>
              <select v-model="form.currency" class="j2b-select">
                <option value="MXN">MXN</option>
                <option value="USD">USD</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="j2b-label">Orden *</label>
              <input v-model.number="form.sort_order" type="number" min="0" class="j2b-input">
              <small style="color:#999;">Menor primero. Usa múltiplos de 10 para insertar fácilmente.</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="j2b-label">¿Activo?</label>
              <div>
                <label style="margin-right:24px;">
                  <input type="radio" :value="true" v-model="form.active"> Sí
                </label>
                <label>
                  <input type="radio" :value="false" v-model="form.active"> No
                </label>
              </div>
            </div>
          </div>
          </div>

          <div class="modal-footer j2b-modal-footer">
            <button type="button" class="j2b-btn j2b-btn-outline" @click="cerrarModal">Cancelar</button>
            <button type="button" class="j2b-btn j2b-btn-primary" @click="guardar" :disabled="guardando">
              <i class="fa fa-save"></i>
              {{ guardando ? 'Guardando…' : (modoEditar ? 'Actualizar' : 'Crear') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      tiers: [],
      tierIdsInUse: [],
      showModal: false,
      modoEditar: false,
      guardando: false,
      erroresValidacion: [],
      form: this.formVacio(),
      tierEditandoId: null,
    };
  },
  computed: {
    rangoBloqueado() {
      return this.modoEditar && this.tierEditandoId && this.enUso(this.tierEditandoId);
    },
  },
  mounted() {
    this.cargar();
  },
  methods: {
    formVacio() {
      return {
        name: '',
        min_equipment: 1,
        max_equipment: null,
        is_flat_rate: false,
        price_per_equipment: null,
        flat_amount: null,
        includes_base_plan: false,
        currency: 'MXN',
        active: true,
        sort_order: 0,
      };
    },
    format(v) {
      if (v === null || v === undefined) return '—';
      return Number(v).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    enUso(id) {
      return this.tierIdsInUse.includes(id);
    },
    cargar() {
      window.axios.get('/superadmin/monitor-pricing-tiers').then((r) => {
        this.tiers = r.data.tiers;
        this.tierIdsInUse = r.data.tier_ids_in_use;
      });
    },
    abrirModalNuevo() {
      this.modoEditar = false;
      this.tierEditandoId = null;
      this.form = this.formVacio();
      const ultimo = this.tiers.length ? Math.max(...this.tiers.map((t) => t.sort_order)) : 0;
      this.form.sort_order = ultimo + 10;
      this.erroresValidacion = [];
      this.showModal = true;
    },
    abrirModalEditar(tier) {
      this.modoEditar = true;
      this.tierEditandoId = tier.id;
      this.form = {
        name: tier.name,
        min_equipment: tier.min_equipment,
        max_equipment: tier.max_equipment,
        is_flat_rate: !!tier.is_flat_rate,
        price_per_equipment: tier.price_per_equipment !== null ? Number(tier.price_per_equipment) : null,
        flat_amount: tier.flat_amount !== null ? Number(tier.flat_amount) : null,
        includes_base_plan: !!tier.includes_base_plan,
        currency: tier.currency,
        active: !!tier.active,
        sort_order: tier.sort_order,
      };
      this.erroresValidacion = [];
      this.showModal = true;
    },
    cerrarModal() {
      this.showModal = false;
    },
    validarLocal() {
      const errs = [];
      if (!this.form.name || !this.form.name.trim()) errs.push('El nombre es obligatorio.');
      if (!this.form.min_equipment || this.form.min_equipment < 1) errs.push('Equipos mínimo debe ser ≥ 1.');
      if (this.form.max_equipment !== null && this.form.max_equipment !== '' && Number(this.form.max_equipment) < Number(this.form.min_equipment)) {
        errs.push('Equipos máximo no puede ser menor al mínimo.');
      }
      if (this.form.is_flat_rate) {
        if (this.form.flat_amount === null || this.form.flat_amount === '' || Number(this.form.flat_amount) < 0) {
          errs.push('El monto fijo es obligatorio para tarifa plana.');
        }
      } else {
        if (this.form.price_per_equipment === null || this.form.price_per_equipment === '' || Number(this.form.price_per_equipment) < 0) {
          errs.push('El precio por equipo es obligatorio.');
        }
      }
      return errs;
    },
    guardar() {
      const errs = this.validarLocal();
      if (errs.length) { this.erroresValidacion = errs; return; }

      this.guardando = true;
      this.erroresValidacion = [];
      const payload = {
        ...this.form,
        max_equipment: this.form.max_equipment === '' ? null : this.form.max_equipment,
        price_per_equipment: this.form.is_flat_rate ? null : this.form.price_per_equipment,
        flat_amount: this.form.is_flat_rate ? this.form.flat_amount : null,
      };

      const req = this.modoEditar
        ? window.axios.put(`/superadmin/monitor-pricing-tiers/${this.tierEditandoId}`, payload)
        : window.axios.post('/superadmin/monitor-pricing-tiers', payload);

      req.then((r) => {
        this.cerrarModal();
        this.cargar();
        window.Swal.fire({ icon: 'success', title: r.data.message, timer: 1800, showConfirmButton: false });
      }).catch((e) => {
        this.guardando = false;
        if (e.response && e.response.status === 422) {
          const errors = e.response.data.errors || {};
          const flat = Object.values(errors).flat();
          this.erroresValidacion = flat.length ? flat : [e.response.data.message || 'Error de validación'];
        } else {
          window.Swal.fire({ icon: 'error', title: 'Error', text: (e.response && e.response.data && e.response.data.message) || 'No se pudo guardar.' });
        }
      }).finally(() => { this.guardando = false; });
    },
    toggleActive(tier) {
      const accion = tier.active ? 'desactivar' : 'activar';
      window.Swal.fire({
        title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} tier?`,
        text: tier.active
          ? 'Las tiendas que caigan en este rango no podrán cobrarse con él hasta que actives otro tier que cubra ese rango.'
          : 'Volverá a estar disponible para nuevos cobros.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar',
      }).then((result) => {
        if (!result.isConfirmed) return;
        window.axios.patch(`/superadmin/monitor-pricing-tiers/${tier.id}/toggle-active`).then((r) => {
          this.cargar();
          window.Swal.fire({ icon: 'success', title: r.data.message, timer: 1500, showConfirmButton: false });
        });
      });
    },
  },
};
</script>
