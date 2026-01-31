/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp } from 'vue';
import dayjs from 'dayjs';
require('./bootstrap');

// Plugin de visor de imágenes global
import ImageViewerPlugin from './plugins/ImageViewer';

// Components de SuperAdmin
import PlansComponent from './components/superadmin/PlansComponent.vue'
import PreRegisterComponent from './components/superadmin/PreRegisterComponent.vue'
import ShopsComponent from './components/superadmin/ShopsComponent.vue'
import UsersComponent from './components/superadmin/UsersComponent.vue'
import SubscriptionManagementComponent from './components/superadmin/SubscriptionManagementComponent.vue'
import ShopPaymentsComponent from './components/superadmin/ShopPaymentsComponent.vue'
import ContactMessagesComponent from './components/superadmin/ContactMessagesComponent.vue'
import LegalDocumentsComponent from './components/superadmin/LegalDocumentsComponent.vue'
import ExampleComponent from './components/ExampleComponent.vue';
//Componenetes de tiendas
import ClientsComponent from './components/shops/ClientsComponent.vue';
import RentasClienteComponent from './components/shops/RentasClienteComponent.vue';
import TemplateCreatorComponent from './components/shops/TemplateCreator.vue';
//Componentes de admin
import ReceiptsComponent from './components/admin/ReceiptsComponent.vue';
import AdminChatAIComponent from './components/admin/AdminChatAIComponent.vue';
import TasksComponent from './components/admin/TasksComponent.vue';
import ProductsComponent from './components/admin/ProductsComponent.vue';
import ProductImportComponent from './components/admin/ProductImportComponent.vue';
import CategoriesComponent from './components/admin/CategoriesComponent.vue';
import ServicesComponent from './components/admin/ServicesComponent.vue';
import EquipmentsComponent from './components/admin/EquipmentsComponent.vue';
import AdminUsersComponent from './components/admin/UsersComponent.vue';
import ReportsComponent from './components/admin/ReportsComponent.vue';
import GastosComponent from './components/admin/GastosComponent.vue';
import ReceiptCreateComponent from './components/admin/ReceiptCreateComponent.vue';
import ReceiptListComponent from './components/admin/ReceiptListComponent.vue';
import ReceiptFormComponent from './components/admin/ReceiptFormComponent.vue';
import PurchaseOrderListComponent from './components/admin/PurchaseOrderListComponent.vue';
import PurchaseOrderCreateComponent from './components/admin/PurchaseOrderCreateComponent.vue';
import PurchaseOrderShowComponent from './components/admin/PurchaseOrderShowComponent.vue';
import AiSettingsComponent from './components/admin/AiSettingsComponent.vue';
import AiIndexingComponent from './components/admin/AiIndexingComponent.vue';

// Componentes Shared (reutilizables)
import ModalSelectClient from './components/shared/ModalSelectClient.vue';
import ModalSelectSupplier from './components/shared/ModalSelectSupplier.vue';
import ModalSelectProduct from './components/shared/ModalSelectProduct.vue';
import ModalSelectService from './components/shared/ModalSelectService.vue';
import ModalSelectEquipment from './components/shared/ModalSelectEquipment.vue';

const app = createApp({});

// Asuperadmin
app.component('superadmin-plans-component', PlansComponent);
app.component('superadmin-pre-register-component', PreRegisterComponent);
app.component('superadmin-shops-component', ShopsComponent);
app.component('superadmin-users-component', UsersComponent);
app.component('subscription-management-component', SubscriptionManagementComponent);
app.component('shop-payments-component', ShopPaymentsComponent);
app.component('contact-messages-component', ContactMessagesComponent);
app.component('legal-documents-component', LegalDocumentsComponent);
//Tiendas
app.component('shop-template-creator-component', TemplateCreatorComponent);
app.component('shop-clients-component', ClientsComponent);
app.component('rentas-cliente-component', RentasClienteComponent);
//Admin
app.component('receipts-component', ReceiptsComponent);
app.component('admin-chat-ai-component', AdminChatAIComponent);
app.component('tasks-component', TasksComponent);
app.component('products-component', ProductsComponent);
app.component('product-import-component', ProductImportComponent);
app.component('categories-component', CategoriesComponent);
app.component('services-component', ServicesComponent);
app.component('equipments-component', EquipmentsComponent);
app.component('ai-settings-component', AiSettingsComponent);
app.component('ai-indexing-component', AiIndexingComponent);
app.component('users-component', AdminUsersComponent);
app.component('reports-component', ReportsComponent);
app.component('gastos-component', GastosComponent);
app.component('receipt-create-component', ReceiptCreateComponent);
app.component('receipt-list-component', ReceiptListComponent);
app.component('receipt-form-component', ReceiptFormComponent);
app.component('purchase-order-list-component', PurchaseOrderListComponent);
app.component('purchase-order-create-component', PurchaseOrderCreateComponent);
app.component('purchase-order-show-component', PurchaseOrderShowComponent);

// Shared (reutilizables en cualquier módulo)
app.component('modal-select-client', ModalSelectClient);
app.component('modal-select-supplier', ModalSelectSupplier);
app.component('modal-select-product', ModalSelectProduct);
app.component('modal-select-service', ModalSelectService);
app.component('modal-select-equipment', ModalSelectEquipment);


//app.component('example-component', ExampleComponent);

// Plugin de visor de imágenes (disponible como this.$viewImage, this.$viewImages)
app.use(ImageViewerPlugin);

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