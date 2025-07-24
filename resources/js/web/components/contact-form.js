/**
 * Contact Form Component
 * Lazy-loaded component for form validation and submission
 */

class ContactForm {
    constructor(element) {
        this.element = element;
        this.form = element.querySelector('form');
        this.submitButton = element.querySelector('[type="submit"]');
        this.messageElement = element.querySelector('.form-message');
        
        if (this.form) {
            this.init();
        }
    }

    init() {
        this.setupValidation();
        this.setupSubmission();
        this.setupReCaptcha();
    }

    setupValidation() {
        const inputs = this.form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Real-time validation
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });

        // Form submission validation
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');
        
        let isValid = true;
        let errorMessage = '';

        // Required field validation
        if (required && !value) {
            isValid = false;
            errorMessage = 'Este campo es requerido';
        }

        // Type-specific validation
        if (value && type === 'email') {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(value)) {
                isValid = false;
                errorMessage = 'Ingresa un email válido';
            }
        }

        if (value && type === 'tel') {
            const phonePattern = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phonePattern.test(value.replace(/\s+/g, ''))) {
                isValid = false;
                errorMessage = 'Ingresa un teléfono válido';
            }
        }

        // Update UI
        this.updateFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    updateFieldValidation(field, isValid, errorMessage) {
        const fieldContainer = field.closest('.form-field') || field.parentElement;
        const errorElement = fieldContainer.querySelector('.field-error') || 
                            this.createErrorElement(fieldContainer);

        if (isValid) {
            field.classList.remove('error');
            field.classList.add('valid');
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        } else {
            field.classList.remove('valid');
            field.classList.add('error');
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';
        }
    }

    createErrorElement(container) {
        const errorElement = document.createElement('span');
        errorElement.className = 'field-error';
        errorElement.style.cssText = 'color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;';
        container.appendChild(errorElement);
        return errorElement;
    }

    clearFieldError(field) {
        if (field.classList.contains('error')) {
            field.classList.remove('error');
            const fieldContainer = field.closest('.form-field') || field.parentElement;
            const errorElement = fieldContainer.querySelector('.field-error');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }
    }

    setupSubmission() {
        // Add loading states and animations
        this.originalButtonText = this.submitButton?.textContent || 'Enviar';
    }

    async handleSubmit() {
        // Validate all fields
        const inputs = this.form.querySelectorAll('input, textarea, select');
        let isFormValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isFormValid = false;
            }
        });

        if (!isFormValid) {
            this.showMessage('Por favor corrige los errores en el formulario', 'error');
            return;
        }

        // Show loading state
        this.setLoading(true);

        try {
            // Collect form data
            const formData = new FormData(this.form);
            const data = Object.fromEntries(formData.entries());

            // Submit form (replace with your actual endpoint)
            const response = await this.submitForm(data);

            if (response.success) {
                this.showMessage('¡Mensaje enviado exitosamente! Te contactaremos pronto.', 'success');
                this.form.reset();
                this.clearAllValidation();
            } else {
                throw new Error(response.message || 'Error al enviar el formulario');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showMessage('Error al enviar el formulario. Intenta nuevamente.', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    async submitForm(data) {
        // Simulate API call - replace with your actual endpoint
        return new Promise((resolve) => {
            setTimeout(() => {
                // Simulate success for demo
                resolve({ success: true });
            }, 1500);
        });
    }

    setLoading(isLoading) {
        if (!this.submitButton) return;

        if (isLoading) {
            this.submitButton.disabled = true;
            this.submitButton.textContent = 'Enviando...';
            this.submitButton.classList.add('loading');
        } else {
            this.submitButton.disabled = false;
            this.submitButton.textContent = this.originalButtonText;
            this.submitButton.classList.remove('loading');
        }
    }

    showMessage(message, type) {
        if (!this.messageElement) {
            this.messageElement = this.createMessageElement();
        }

        this.messageElement.textContent = message;
        this.messageElement.className = `form-message ${type}`;
        this.messageElement.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            this.messageElement.style.display = 'none';
        }, 5000);
    }

    createMessageElement() {
        const messageElement = document.createElement('div');
        messageElement.className = 'form-message';
        messageElement.style.cssText = `
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0.5rem;
            font-weight: 500;
            display: none;
        `;
        
        // Add styles for different message types
        const style = document.createElement('style');
        style.textContent = `
            .form-message.success {
                background-color: #dcfce7;
                color: #166534;
                border: 1px solid #bbf7d0;
            }
            .form-message.error {
                background-color: #fef2f2;
                color: #dc2626;
                border: 1px solid #fecaca;
            }
        `;
        document.head.appendChild(style);

        this.form.appendChild(messageElement);
        return messageElement;
    }

    clearAllValidation() {
        const inputs = this.form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.classList.remove('valid', 'error');
            const fieldContainer = input.closest('.form-field') || input.parentElement;
            const errorElement = fieldContainer.querySelector('.field-error');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        });
    }

    setupReCaptcha() {
        // Lazy load reCAPTCHA if needed
        const recaptchaElement = this.element.querySelector('.g-recaptcha');
        if (recaptchaElement && !window.grecaptcha) {
            this.loadReCaptcha();
        }
    }

    loadReCaptcha() {
        const script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
}

export default ContactForm;