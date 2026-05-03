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
import CfdiManagementComponent from './components/superadmin/CfdiManagementComponent.vue'
import SuperadminCfdiFacturasComponent from './components/superadmin/CfdiFacturasComponent.vue'
import PdfPhrasesComponent from './components/superadmin/PdfPhrasesComponent.vue'
import ExampleComponent from './components/ExampleComponent.vue';
//Componenetes de tiendas
import ClientsComponent from './components/shops/ClientsComponent.vue';
import RentasClienteComponent from './components/shops/RentasClienteComponent.vue';
import RentConsignmentsList from './components/admin/rentas/RentConsignmentsList.vue';
import CobrarRentaModal from './components/shops/CobrarRentaModal.vue';
import TemplateCreatorComponent from './components/shops/TemplateCreator.vue';
//Componentes de admin
import ReceiptsComponent from './components/admin/ReceiptsComponent.vue';
import AdminChatAIComponent from './components/admin/AdminChatAIComponent.vue';
import AdminDashboardComponent from './components/admin/AdminDashboardComponent.vue';
import TasksComponent from './components/admin/TasksComponent.vue';
import TaskDetailComponent from './components/admin/TaskDetailComponent.vue';
import ProductsComponent from './components/admin/ProductsComponent.vue';
import ProductImportComponent from './components/admin/ProductImportComponent.vue';
import ClientImportComponent from './components/admin/ClientImportComponent.vue';
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
import CfdiConfigComponent from './components/admin/CfdiConfigComponent.vue';
import CfdiFacturasComponent from './components/admin/CfdiFacturasComponent.vue';
import ShopBankAccountsComponent from './components/admin/ShopBankAccountsComponent.vue';
import ReceiptSettingsComponent from './components/admin/ReceiptSettingsComponent.vue';
import ServiceTrackingConfigComponent from './components/admin/ServiceTrackingConfigComponent.vue';
import MonitoreoComponent from './components/admin/MonitoreoComponent.vue';
import TrackingMapModal from './components/admin/TrackingMapModal.vue';
import TrackingHistoryListModal from './components/admin/TrackingHistoryListModal.vue';

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
app.component('cfdi-management-component', CfdiManagementComponent);
app.component('superadmin-cfdi-facturas-component', SuperadminCfdiFacturasComponent);
app.component('pdf-phrases-component', PdfPhrasesComponent);
//Tiendas
app.component('shop-template-creator-component', TemplateCreatorComponent);
app.component('shop-clients-component', ClientsComponent);
app.component('rentas-cliente-component', RentasClienteComponent);
app.component('rent-consignments-list', RentConsignmentsList);
app.component('cobrar-renta-modal', CobrarRentaModal);
//Admin
app.component('receipts-component', ReceiptsComponent);
app.component('admin-chat-ai-component', AdminChatAIComponent);
app.component('admin-dashboard-component', AdminDashboardComponent);
app.component('tasks-component', TasksComponent);
app.component('task-detail-component', TaskDetailComponent);
app.component('products-component', ProductsComponent);
app.component('product-import-component', ProductImportComponent);
app.component('client-import-component', ClientImportComponent);
app.component('categories-component', CategoriesComponent);
app.component('services-component', ServicesComponent);
app.component('equipments-component', EquipmentsComponent);
app.component('ai-settings-component', AiSettingsComponent);
app.component('ai-indexing-component', AiIndexingComponent);
app.component('cfdi-config-component', CfdiConfigComponent);
app.component('cfdi-facturas-component', CfdiFacturasComponent);
app.component('shop-bank-accounts-component', ShopBankAccountsComponent);
app.component('receipt-settings-component', ReceiptSettingsComponent);
app.component('service-tracking-config-component', ServiceTrackingConfigComponent);
app.component('monitoreo-component', MonitoreoComponent);
app.component('tracking-map-modal', TrackingMapModal);
app.component('tracking-history-list-modal', TrackingHistoryListModal);
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

// Shop config global (currency + tax)
const shopConfig = window.__shopConfig || { currency: 'MXN', taxName: 'IVA', taxRate: 16 };
app.config.globalProperties.$shopCurrency = shopConfig.currency;
app.config.globalProperties.$shopTaxName = shopConfig.taxName;
app.config.globalProperties.$shopTaxRate = shopConfig.taxRate;
app.config.globalProperties.$taxDecimal = shopConfig.taxRate / 100;
app.config.globalProperties.$taxDivisor = 1 + (shopConfig.taxRate / 100);
app.config.globalProperties.$hasTax = shopConfig.taxRate > 0 && shopConfig.taxName !== null;

// Filtros ya no existen en Vue 3, pero puedes usar propiedades globales
app.config.globalProperties.$filters = {
    formatDate(value, format = 'DD/MM/YYYY') {
        return dayjs(value).format(format);
    },
    toCurrency(value, currencyCode = 'MXN') {
        if (typeof value !== "number") return value;
        const locale = currencyCode === 'USD' ? 'en-US' : 'es-MX';
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currencyCode
        }).format(value);
    }
};

// Montar la aplicación en el `#app`
app.mount('#app');