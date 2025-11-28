<template>
  <div class="admin-chat-ai">
    <!-- Chat Controls Header -->
    <div class="chat-controls d-flex justify-content-between align-items-center p-3 border-bottom">
      <div class="chat-stats">
        <span class="badge bg-light text-dark me-2">
          <i class="fas fa-comments me-1"></i>{{ mensajes.length }} mensajes
        </span>
      </div>
      <button
        type="button"
        class="btn btn-sm btn-outline-danger"
        @click="limpiarHistorial"
        v-if="mensajes.length > 0"
      >
        <i class="fas fa-trash me-1"></i>Limpiar
      </button>
    </div>

    <!-- Chat Body -->
    <div class="chat-body-content">
      <!-- Área de Mensajes -->
      <div ref="chatContainer" class="chat-messages">
        <!-- Mensaje de bienvenida cuando no hay mensajes -->
        <div v-if="mensajes.length === 0" class="welcome-message text-center py-5">
          <div class="welcome-icon mb-4">
            <i class="fas fa-robot"></i>
          </div>
          <h5 class="mb-3">¡Hola! Soy tu asistente administrativo J2Biznes</h5>
          <p class="text-muted mb-4">Estoy aquí para ayudarte con la gestión de tu negocio</p>
          <div class="suggestions">
            <p class="text-muted mb-2"><small>Puedes preguntarme cosas como:</small></p>
            <div class="suggestion-chips">
              <button @click="inputText = '¿Qué funcionalidades tiene J2Biznes?'" class="suggestion-chip">¿Qué puede hacer J2Biznes?</button>
              <button @click="inputText = '¿Cómo gestiono mis clientes?'" class="suggestion-chip">Gestión de clientes</button>
              <button @click="inputText = '¿Cómo funcionan las tareas?'" class="suggestion-chip">Sistema de tareas</button>
            </div>
          </div>
        </div>

        <!-- Lista de mensajes -->
        <div
          v-for="(mensaje, index) in mensajes"
          :key="index"
          class="mensaje-wrapper mb-3"
          :class="mensaje.role"
        >
          <div class="mensaje">
            <div class="mensaje-content">
              <p class="mb-1">{{ mensaje.content }}</p>
              <small class="text-muted">{{ mensaje.timestamp }}</small>
              <small v-if="mensaje.usage" class="ms-2 text-muted">
                ({{ mensaje.usage.total_tokens }} tokens)
              </small>
            </div>
          </div>
        </div>

        <!-- Indicador escribiendo... -->
        <div v-if="escribiendo" class="mensaje-wrapper assistant mb-3">
          <div class="mensaje">
            <div class="mensaje-content">
              <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulario de entrada -->
      <div class="chat-input">
        <form @submit.prevent="enviarMensaje">
          <div class="input-group">
            <textarea
              v-model="inputText"
              class="form-control"
              placeholder="Escribe tu pregunta aquí..."
              rows="2"
              :disabled="escribiendo"
              @keydown.enter.prevent="enviarMensaje"
              maxlength="2000"
            ></textarea>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="!inputText.trim() || escribiendo"
            >
              <i class="fas fa-paper-plane me-1"></i>Enviar
            </button>
          </div>
          <small class="text-muted">
            {{ inputText.length }} / 2000 caracteres
          </small>
        </form>
      </div>

      <!-- Mensaje de error -->
      <div v-if="error" class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Error:</strong> {{ error }}
        <button type="button" class="btn-close" @click="error = null"></button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminChatAIComponent',

  data() {
    return {
      mensajes: [],
      inputText: '',
      escribiendo: false,
      error: null
    }
  },

  mounted() {
    // Auto-scroll al final cuando se carga el componente
    this.scrollToBottom();
  },

  methods: {
    /**
     * Enviar mensaje al chat
     */
    async enviarMensaje() {
      if (!this.inputText.trim() || this.escribiendo) return;

      const pregunta = this.inputText.trim();
      this.inputText = '';

      // Agregar mensaje del usuario
      this.agregarMensaje('user', pregunta);

      // Mostrar indicador de escritura
      this.escribiendo = true;
      this.error = null;

      try {
        const response = await fetch('/admin/asistente/chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            prompt: pregunta
          })
        });

        const data = await response.json();

        if (data.success) {
          // Agregar respuesta de la IA
          this.agregarMensaje('assistant', data.response, data.usage);
        } else {
          this.error = data.error || 'Error al procesar la solicitud';
        }

      } catch (err) {
        console.error('Error al enviar mensaje:', err);
        this.error = 'Error de conexión. Por favor, intenta de nuevo.';
      } finally {
        this.escribiendo = false;
      }
    },

    /**
     * Agregar mensaje al historial
     */
    agregarMensaje(role, content, usage = null) {
      this.mensajes.push({
        role: role,
        content: content,
        usage: usage,
        timestamp: this.getTimestamp()
      });

      // Auto-scroll después de agregar mensaje
      this.$nextTick(() => {
        this.scrollToBottom();
      });
    },

    /**
     * Limpiar historial
     */
    limpiarHistorial() {
      if (confirm('¿Estás seguro de que deseas limpiar el historial del chat?')) {
        this.mensajes = [];
        this.error = null;
      }
    },

    /**
     * Scroll automático al final
     */
    scrollToBottom() {
      if (this.$refs.chatContainer) {
        this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
      }
    },

    /**
     * Obtener timestamp formateado
     */
    getTimestamp() {
      const now = new Date();
      return now.toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
}
</script>

<style scoped>
.admin-chat-ai {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
}

/* Chat Controls */
.chat-controls {
  background: #f8f9fa;
  flex-shrink: 0;
}

.chat-stats .badge {
  font-weight: 500;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
}

/* Chat Body */
.chat-body-content {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  overflow-y: auto;
  overflow-x: hidden;
  min-height: 0;
}

/* Welcome Message */
.welcome-message {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.welcome-icon {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 3rem;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.suggestion-chips {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: center;
}

.suggestion-chip {
  background: white;
  border: 2px solid #667eea;
  color: #667eea;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.suggestion-chip:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Área de mensajes */
.chat-messages {
  flex: 0 1 auto;
  padding: 1.5rem;
  background-color: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #dee2e6;
  margin-bottom: 1.5rem;
  min-height: 400px;
  max-height: 500px;
  overflow-y: auto;
}

/* Mensajes */
.mensaje-wrapper {
  display: flex;
  margin-bottom: 1rem;
}

.mensaje-wrapper.user {
  justify-content: flex-end;
}

.mensaje-wrapper.assistant {
  justify-content: flex-start;
}

.mensaje {
  max-width: 70%;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  word-wrap: break-word;
}

.mensaje-wrapper.user .mensaje {
  background-color: #007bff;
  color: white;
  border-bottom-right-radius: 4px;
}

.mensaje-wrapper.assistant .mensaje {
  background-color: white;
  color: #333;
  border: 1px solid #dee2e6;
  border-bottom-left-radius: 4px;
}

.mensaje-content p {
  margin-bottom: 0.25rem;
  white-space: pre-wrap;
}

.mensaje-content small {
  opacity: 0.8;
  font-size: 0.75rem;
}

/* Indicador de escritura */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background-color: #6c757d;
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    opacity: 0.3;
    transform: translateY(0);
  }
  30% {
    opacity: 1;
    transform: translateY(-8px);
  }
}

/* Input de chat */
.chat-input {
  flex-shrink: 0;
}

.chat-input textarea {
  resize: none;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.chat-input .btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

/* Scrollbar personalizado */
.chat-messages::-webkit-scrollbar {
  width: 8px;
}

.chat-messages::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Responsive */
@media (max-width: 768px) {
  .mensaje {
    max-width: 85%;
  }

  .chat-messages {
    min-height: 300px;
    max-height: 400px;
  }
}
</style>
