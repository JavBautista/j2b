/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

// Import Day.js
import dayjs from 'dayjs';

require('./bootstrap');

window.Vue = require('vue').default;

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('superadmin-plans-component', require('./components/superadmin/PlansComponent.vue').default);
Vue.component('superadmin-pre-register-component', require('./components/superadmin/PreRegisterComponent.vue').default);
Vue.component('superadmin-shops-component', require('./components/superadmin/ShopsComponent.vue').default);
Vue.component('superadmin-users-component', require('./components/superadmin/UsersComponent.vue').default);
Vue.component('example-component', require('./components/ExampleComponent.vue').default);

// Example of a Vue filter using Day.js (optional)
Vue.filter('formatDate', function(value, format = 'DD/MM/YYYY') {
    return dayjs(value).format(format); // This will format the date using Day.js
});

Vue.filter('toCurrency', function (value) {
    if (typeof value !== "number") {
        return value;
    }
    var formatter = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    });
    return formatter.format(value);
});
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
