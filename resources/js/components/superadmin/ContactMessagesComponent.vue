<template>
<div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-envelope" style="color: var(--j2b-primary);"></i> Mensajes de Contacto
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Mensajes recibidos desde el formulario del landing</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span v-if="unreadCount > 0" class="j2b-badge j2b-badge-warning">
                    <i class="fa fa-bell"></i> {{ unreadCount }} sin leer
                </span>
                <button v-if="selectedMessages.length > 0" type="button" @click="markSelectedAsRead()" class="j2b-btn j2b-btn-secondary">
                    <i class="fa fa-check-double"></i> Marcar leídos ({{ selectedMessages.length }})
                </button>
                <button v-if="selectedMessages.length > 0" type="button" @click="deleteSelected()" class="j2b-btn j2b-btn-danger">
                    <i class="fa fa-trash"></i> Eliminar ({{ selectedMessages.length }})
                </button>
            </div>
        </div>

        <!-- Card principal -->
        <div class="j2b-card">
            <!-- Filtros -->
            <div class="j2b-card-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex gap-2 flex-wrap">
                            <select class="j2b-select" style="width: auto; min-width: 130px;" v-model="filter" @change="getMessages()">
                                <option value="">Todos</option>
                                <option value="unread">No leídos</option>
                                <option value="read">Leídos</option>
                            </select>
                            <select class="j2b-select" style="width: auto; min-width: 120px;" v-model="criterio">
                                <option value="name">Nombre</option>
                                <option value="email">Email</option>
                                <option value="phone">Teléfono</option>
                                <option value="company">Empresa</option>
                            </select>
                            <div class="j2b-input-icon" style="flex: 1; min-width: 200px;">
                                <i class="fa fa-search"></i>
                                <input type="text" v-model="buscar" class="j2b-input" placeholder="Buscar mensaje..." @keyup.enter="getMessages()">
                            </div>
                            <button type="button" @click="getMessages()" class="j2b-btn j2b-btn-primary">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <span class="j2b-badge j2b-badge-info">{{ pagination.total }} mensajes</span>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="j2b-card-body p-0">
                <div class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll()">
                                </th>
                                <th style="width: 50px;"></th>
                                <th>Contacto</th>
                                <th>Mensaje</th>
                                <th style="width: 140px;">Fecha</th>
                                <th style="width: 140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="msg in messages" :key="msg.id" :class="{ 'message-unread': !msg.is_read }">
                                <td>
                                    <input type="checkbox" :value="msg.id" v-model="selectedMessages">
                                </td>
                                <td>
                                    <span v-if="!msg.is_read" class="j2b-badge j2b-badge-primary" title="No leído">
                                        <i class="fa fa-circle" style="font-size: 8px;"></i>
                                    </span>
                                    <span v-else class="j2b-text-muted">
                                        <i class="fa fa-check" style="font-size: 10px;"></i>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 11px; width: 36px; height: 36px; flex-shrink: 0;">
                                            {{ msg.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <strong :style="{ color: msg.is_read ? 'var(--j2b-gray-600)' : 'var(--j2b-dark)', fontWeight: msg.is_read ? '500' : '600' }">
                                                {{ msg.name }}
                                            </strong>
                                            <small class="d-block" style="color: var(--j2b-gray-500);">
                                                {{ msg.email }}
                                            </small>
                                            <small class="d-flex align-items-center gap-1" style="color: var(--j2b-gray-500);">
                                                <i class="fa fa-phone" style="font-size: 10px;"></i> {{ msg.formatted_phone }}
                                                <a v-if="msg.is_whatsapp" :href="msg.whatsapp_link" target="_blank" class="ml-1" style="color: #25D366;" title="WhatsApp">
                                                    <i class="fa fa-whatsapp"></i>
                                                </a>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="message-preview" @click="openMessage(msg)" style="cursor: pointer;">
                                        <small v-if="msg.company" class="d-block" style="color: var(--j2b-primary);">
                                            <i class="fa fa-building" style="font-size: 10px;"></i> {{ msg.company }}
                                        </small>
                                        <span :style="{ color: msg.is_read ? 'var(--j2b-gray-500)' : 'var(--j2b-dark)' }">
                                            {{ truncateMessage(msg.message, 80) }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <small style="color: var(--j2b-gray-500);">{{ msg.created_at }}</small>
                                    <br>
                                    <small style="color: var(--j2b-gray-400);">{{ msg.created_at_human }}</small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-primary" @click="openMessage(msg)" title="Ver mensaje">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button v-if="msg.is_read" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="markAsUnread(msg)" title="Marcar como no leído">
                                            <i class="fa fa-envelope"></i>
                                        </button>
                                        <button v-else class="j2b-btn j2b-btn-sm j2b-btn-secondary" @click="markAsRead(msg)" title="Marcar como leído">
                                            <i class="fa fa-envelope-open"></i>
                                        </button>
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-danger" @click="deleteMessage(msg)" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="messages.length === 0">
                                <td colspan="6" class="text-center py-4">
                                    <div style="color: var(--j2b-gray-400);">
                                        <i class="fa fa-inbox fa-3x mb-2"></i>
                                        <p class="mb-0">No hay mensajes</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div class="j2b-card-footer" v-if="pagination.last_page > 1">
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
                                <i class="fa fa-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item" v-for="page in paginationPages" :key="page" :class="{ active: pagination.current_page === page }">
                            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
                                <i class="fa fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Ver Mensaje -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalView}" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-envelope-open-text" style="color: var(--j2b-primary);"></i> Mensaje de Contacto
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" v-if="currentMessage">
                    <!-- Info del contacto -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="j2b-icon-circle j2b-icon-primary mr-3" style="font-size: 18px; width: 50px; height: 50px;">
                                    {{ currentMessage.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h5 class="mb-0" style="color: var(--j2b-dark);">{{ currentMessage.name }}</h5>
                                    <small v-if="currentMessage.company" style="color: var(--j2b-gray-500);">
                                        <i class="fa fa-building"></i> {{ currentMessage.company }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <small style="color: var(--j2b-gray-500);">
                                <i class="fa fa-clock"></i> {{ currentMessage.created_at }}
                            </small>
                            <br>
                            <small style="color: var(--j2b-gray-400);">{{ currentMessage.created_at_human }}</small>
                        </div>
                    </div>

                    <!-- Datos de contacto -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="j2b-info-item">
                                <label><i class="fa fa-envelope"></i> Email</label>
                                <a :href="'mailto:' + currentMessage.email" style="color: var(--j2b-primary);">
                                    {{ currentMessage.email }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="j2b-info-item">
                                <label><i class="fa fa-phone"></i> Teléfono</label>
                                <span>
                                    <a :href="'tel:+52' + currentMessage.phone" style="color: var(--j2b-dark);">
                                        {{ currentMessage.formatted_phone }}
                                    </a>
                                    <a v-if="currentMessage.is_whatsapp" :href="currentMessage.whatsapp_link" target="_blank" class="ml-2 j2b-btn j2b-btn-sm" style="background: #25D366; color: white; padding: 2px 8px;">
                                        <i class="fa fa-whatsapp"></i> WhatsApp
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje -->
                    <div class="j2b-message-box">
                        <label style="color: var(--j2b-gray-500); font-weight: 500; margin-bottom: 8px; display: block;">
                            <i class="fa fa-comment-alt"></i> Mensaje
                        </label>
                        <div class="message-content" style="background: var(--j2b-gray-100); padding: 16px; border-radius: 8px; white-space: pre-wrap; line-height: 1.6;">
                            {{ currentMessage.message }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">Cerrar</button>
                    <a v-if="currentMessage" :href="'mailto:' + currentMessage.email" class="j2b-btn j2b-btn-primary">
                        <i class="fa fa-reply"></i> Responder por Email
                    </a>
                    <a v-if="currentMessage && currentMessage.is_whatsapp" :href="currentMessage.whatsapp_link" target="_blank" class="j2b-btn" style="background: #25D366; color: white;">
                        <i class="fa fa-whatsapp"></i> WhatsApp
                    </a>
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
            messages: [],
            pagination: {
                total: 0,
                current_page: 1,
                per_page: 15,
                last_page: 1,
                from: 0,
                to: 0
            },
            unreadCount: 0,
            filter: '',
            criterio: 'name',
            buscar: '',
            selectedMessages: [],
            selectAll: false,
            currentMessage: null,
            modalView: 0
        }
    },

    computed: {
        paginationPages() {
            let pages = [];
            let start = Math.max(1, this.pagination.current_page - 2);
            let end = Math.min(this.pagination.last_page, this.pagination.current_page + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        }
    },

    mounted() {
        this.getMessages();
    },

    methods: {
        getMessages(page = 1) {
            let params = {
                page: page,
                filter: this.filter,
                criterio: this.criterio,
                buscar: this.buscar
            };

            axios.get('/superadmin/contact-messages/get', { params })
                .then(response => {
                    this.messages = response.data.messages;
                    this.pagination = response.data.pagination;
                    this.unreadCount = response.data.unread_count;
                    this.selectedMessages = [];
                    this.selectAll = false;
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    this.showAlert('error', 'Error al cargar mensajes');
                });
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.getMessages(page);
            }
        },

        truncateMessage(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedMessages = this.messages.map(m => m.id);
            } else {
                this.selectedMessages = [];
            }
        },

        openMessage(msg) {
            axios.get('/superadmin/contact-messages/' + msg.id)
                .then(response => {
                    this.currentMessage = response.data.message;
                    this.modalView = 1;
                })
                .catch(error => {
                    console.error('Error loading message:', error);
                    this.showAlert('error', 'Error al cargar el mensaje');
                });
        },

        markAsRead(msg) {
            axios.put('/superadmin/contact-messages/' + msg.id + '/read')
                .then(() => {
                    msg.is_read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                })
                .catch(error => {
                    console.error('Error marking as read:', error);
                });
        },

        markAsUnread(msg) {
            axios.put('/superadmin/contact-messages/' + msg.id + '/unread')
                .then(() => {
                    msg.is_read = false;
                    this.unreadCount++;
                })
                .catch(error => {
                    console.error('Error marking as unread:', error);
                });
        },

        markSelectedAsRead() {
            if (this.selectedMessages.length === 0) return;

            axios.put('/superadmin/contact-messages/mark-multiple-read', {
                ids: this.selectedMessages
            })
            .then(() => {
                this.getMessages(this.pagination.current_page);
                this.showAlert('success', 'Mensajes marcados como leídos');
            })
            .catch(error => {
                console.error('Error marking as read:', error);
                this.showAlert('error', 'Error al marcar mensajes');
            });
        },

        deleteMessage(msg) {
            Swal.fire({
                title: '¿Eliminar mensaje?',
                text: `Se eliminará el mensaje de ${msg.name}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete('/superadmin/contact-messages/' + msg.id)
                        .then(() => {
                            this.getMessages(this.pagination.current_page);
                            this.showAlert('success', 'Mensaje eliminado');
                        })
                        .catch(error => {
                            console.error('Error deleting message:', error);
                            this.showAlert('error', 'Error al eliminar mensaje');
                        });
                }
            });
        },

        deleteSelected() {
            if (this.selectedMessages.length === 0) return;

            Swal.fire({
                title: '¿Eliminar mensajes?',
                text: `Se eliminarán ${this.selectedMessages.length} mensajes`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post('/superadmin/contact-messages/delete-multiple', {
                        ids: this.selectedMessages
                    })
                    .then(() => {
                        this.getMessages(this.pagination.current_page);
                        this.showAlert('success', 'Mensajes eliminados');
                    })
                    .catch(error => {
                        console.error('Error deleting messages:', error);
                        this.showAlert('error', 'Error al eliminar mensajes');
                    });
                }
            });
        },

        cerrarModal() {
            this.modalView = 0;
            this.currentMessage = null;
        },

        showAlert(type, message) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
}
</script>

<style scoped>
.message-unread {
    background-color: rgba(0, 255, 136, 0.05);
}

.message-unread:hover {
    background-color: rgba(0, 255, 136, 0.1);
}

.message-preview {
    max-width: 300px;
}

.j2b-info-item {
    margin-bottom: 8px;
}

.j2b-info-item label {
    display: block;
    font-size: 12px;
    color: var(--j2b-gray-500);
    margin-bottom: 4px;
}

.gap-1 {
    gap: 4px;
}

.gap-2 {
    gap: 8px;
}
</style>
