/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp } from 'vue';
import dayjs from 'dayjs';
require('./bootstrap');

// Importar los componentes manualmente
import PlansComponent from './components/superadmin/PlansComponent.vue'
import PreRegisterComponent from './components/superadmin/PreRegisterComponent.vue'
import ShopsComponent from './components/superadmin/ShopsComponent.vue'
import UsersComponent from './components/superadmin/UsersComponent.vue'
import ExampleComponent from './components/ExampleComponent.vue';

const app = createApp({});

// Registrar los componentes globalmente
app.component('superadmin-plans-component', PlansComponent);
app.component('superadmin-pre-register-component', PreRegisterComponent);
app.component('superadmin-shops-component', ShopsComponent);
app.component('superadmin-users-component', UsersComponent);
app.component('example-component', ExampleComponent);

// Filtros ya no existen en Vue 3, pero puedes usar propiedades globales
app.config.globalProperties.$filters = {
    formatDate(value, format = 'DD/MM/YYYY') {
        return dayjs(value).format(format);
    },
    toCurrency(value) {
        if (typeof value !== "number") return value;
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(value);
    }
};

// Montar la aplicación en el `#app`
app.mount('#app');