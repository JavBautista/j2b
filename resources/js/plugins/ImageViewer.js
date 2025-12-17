/**
 * Plugin Global de Visor de Imágenes
 * Uso:
 *   this.$viewImage(url)                    - Ver una sola imagen
 *   this.$viewImages(urls, startIndex)     - Ver galería de imágenes
 *   this.$viewImage({ url, title })        - Ver imagen con título
 */

import { createApp, h, ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import 'vue-easy-lightbox/dist/external-css/vue-easy-lightbox.css';

// Estado global del visor
const state = {
    visible: ref(false),
    images: ref([]),
    index: ref(0),
    titles: ref([])
};

// Componente wrapper para el lightbox
const ImageViewerComponent = {
    setup() {
        const onHide = () => {
            state.visible.value = false;
        };

        return () => h(VueEasyLightbox, {
            visible: state.visible.value,
            imgs: state.images.value,
            index: state.index.value,
            onHide: onHide,
            moveDisabled: false,
            rotateDisabled: false,
            zoomDisabled: false,
            pinchDisabled: false,
            maskClosable: true,
            escDisabled: false,
            loop: true
        });
    }
};

// Función para normalizar URLs de imagen
function normalizeImageUrl(img) {
    if (!img) return '';

    // Si es un objeto con propiedad url o image
    if (typeof img === 'object') {
        const url = img.url || img.image || img.src || img.path || '';
        return normalizeUrl(url);
    }

    return normalizeUrl(img);
}

function normalizeUrl(url) {
    if (!url) return '';

    // Si ya es una URL completa
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
        return url;
    }

    // Si ya tiene /storage/
    if (url.startsWith('/storage/')) {
        return url;
    }

    // Agregar /storage/ si no lo tiene
    return `/storage/${url}`;
}

// Instancia del componente montado
let mountedApp = null;
let containerEl = null;

// Función para asegurar que el componente está montado
function ensureMounted() {
    if (!mountedApp) {
        containerEl = document.createElement('div');
        containerEl.id = 'image-viewer-container';
        document.body.appendChild(containerEl);

        mountedApp = createApp(ImageViewerComponent);
        mountedApp.mount(containerEl);
    }
}

// API pública
const ImageViewer = {
    /**
     * Ver una sola imagen
     * @param {string|object} image - URL de la imagen o objeto {url, title}
     */
    viewImage(image) {
        ensureMounted();

        const url = normalizeImageUrl(image);
        if (!url) {
            console.warn('ImageViewer: No se proporcionó URL de imagen');
            return;
        }

        state.images.value = [url];
        state.index.value = 0;
        state.visible.value = true;
    },

    /**
     * Ver múltiples imágenes (galería)
     * @param {array} images - Array de URLs o objetos de imagen
     * @param {number} startIndex - Índice inicial (default 0)
     */
    viewImages(images, startIndex = 0) {
        ensureMounted();

        if (!images || !Array.isArray(images) || images.length === 0) {
            console.warn('ImageViewer: No se proporcionaron imágenes');
            return;
        }

        state.images.value = images.map(normalizeImageUrl).filter(Boolean);
        state.index.value = Math.max(0, Math.min(startIndex, state.images.value.length - 1));
        state.visible.value = true;
    },

    /**
     * Cerrar el visor
     */
    close() {
        state.visible.value = false;
    }
};

// Plugin de Vue
export default {
    install(app) {
        // Métodos globales en todas las instancias de componentes
        app.config.globalProperties.$viewImage = ImageViewer.viewImage;
        app.config.globalProperties.$viewImages = ImageViewer.viewImages;
        app.config.globalProperties.$closeImageViewer = ImageViewer.close;

        // También disponible como provide/inject
        app.provide('imageViewer', ImageViewer);
    }
};

// Exportar también las funciones directamente para uso fuera de componentes Vue
export { ImageViewer };
