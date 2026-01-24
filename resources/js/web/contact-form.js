/**
 * Contact Form Validation & Security
 * J2Biznes Landing Page
 *
 * Validaciones:
 * - Campos requeridos
 * - Formato email
 * - Teléfono 10 dígitos
 * - Sanitización contra XSS/Inyección
 * - Protección contra spam básica
 */

(function() {
    'use strict';

    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initContactForm();
    });

    function initContactForm() {
        const form = document.getElementById('contactForm');
        if (!form) return;

        // Referencias a elementos
        const fields = {
            name: document.getElementById('contact_name'),
            email: document.getElementById('contact_email'),
            phone: document.getElementById('contact_phone'),
            company: document.getElementById('contact_company'),
            message: document.getElementById('contact_message'),
            isWhatsapp: document.getElementById('contact_is_whatsapp')
        };

        const errors = {
            name: document.getElementById('error_name'),
            email: document.getElementById('error_email'),
            phone: document.getElementById('error_phone'),
            company: document.getElementById('error_company'),
            message: document.getElementById('error_message')
        };

        const formError = document.getElementById('form_error');
        const formErrorText = document.getElementById('form_error_text');
        const formSuccess = document.getElementById('form_success');
        const submitBtn = document.getElementById('contact_submit_btn');

        // Honeypot anti-spam (campo oculto)
        addHoneypot(form);

        // Timestamp anti-bot
        const formLoadTime = Date.now();

        // Validación en tiempo real
        if (fields.name) {
            fields.name.addEventListener('blur', () => validateName(fields.name, errors.name));
            fields.name.addEventListener('input', () => sanitizeInput(fields.name));
        }

        if (fields.email) {
            fields.email.addEventListener('blur', () => validateEmail(fields.email, errors.email));
            fields.email.addEventListener('input', () => sanitizeInput(fields.email));
        }

        if (fields.phone) {
            fields.phone.addEventListener('input', (e) => {
                // Solo permitir números
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                sanitizeInput(fields.phone);
            });
            fields.phone.addEventListener('blur', () => validatePhone(fields.phone, errors.phone));
        }

        if (fields.company) {
            fields.company.addEventListener('input', () => sanitizeInput(fields.company));
        }

        if (fields.message) {
            fields.message.addEventListener('blur', () => validateMessage(fields.message, errors.message));
            fields.message.addEventListener('input', () => sanitizeInput(fields.message));
        }

        // Envío del formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Ocultar alertas previas
            hideElement(formError);
            hideElement(formSuccess);

            // Anti-bot: verificar tiempo mínimo (2 segundos)
            const timeDiff = Date.now() - formLoadTime;
            if (timeDiff < 2000) {
                showError(formError, formErrorText, 'Por favor espera un momento antes de enviar.');
                return;
            }

            // Verificar honeypot
            const honeypot = form.querySelector('input[name="website_url"]');
            if (honeypot && honeypot.value !== '') {
                // Bot detectado, simular éxito sin enviar
                console.warn('Bot detected via honeypot');
                showElement(formSuccess);
                return;
            }

            // Validar todos los campos
            const isValid = validateAll(fields, errors);

            if (!isValid) {
                showError(formError, formErrorText, 'Por favor corrige los errores marcados en rojo.');
                scrollToElement(formError);
                return;
            }

            // Sanitizar todos los datos antes de enviar
            const formData = sanitizeFormData(fields);

            // Verificar contenido malicioso
            const maliciousCheck = checkMaliciousContent(formData);
            if (maliciousCheck.isMalicious) {
                showError(formError, formErrorText, 'Se detectó contenido no permitido: ' + maliciousCheck.reason);
                return;
            }

            // Mostrar estado de carga
            setLoadingState(submitBtn, true);

            // Enviar formulario al backend
            submitToBackend(form, formData, formSuccess, formError, formErrorText, submitBtn, fields);
        });
    }

    // ==================== VALIDADORES ====================

    function validateName(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El nombre es requerido');
            return false;
        }

        if (value.length < 3) {
            setError(input, errorEl, 'El nombre debe tener al menos 3 caracteres');
            return false;
        }

        if (value.length > 100) {
            setError(input, errorEl, 'El nombre no puede exceder 100 caracteres');
            return false;
        }

        // Solo letras, espacios y caracteres latinos
        const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'-]+$/;
        if (!nameRegex.test(value)) {
            setError(input, errorEl, 'El nombre solo puede contener letras');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validateEmail(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El email es requerido');
            return false;
        }

        // Regex para email válido
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(value)) {
            setError(input, errorEl, 'Ingresa un email válido');
            return false;
        }

        if (value.length > 100) {
            setError(input, errorEl, 'El email no puede exceder 100 caracteres');
            return false;
        }

        // Verificar dominios de email temporales/spam comunes
        const spamDomains = ['tempmail', 'throwaway', 'guerrillamail', 'mailinator', '10minutemail'];
        const domain = value.split('@')[1].toLowerCase();
        if (spamDomains.some(spam => domain.includes(spam))) {
            setError(input, errorEl, 'Por favor usa un email válido, no temporal');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validatePhone(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El teléfono es requerido');
            return false;
        }

        // Solo números, exactamente 10 dígitos
        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(value)) {
            setError(input, errorEl, 'El teléfono debe tener exactamente 10 dígitos');
            return false;
        }

        // Verificar que no sean todos el mismo dígito
        if (/^(\d)\1{9}$/.test(value)) {
            setError(input, errorEl, 'Ingresa un número de teléfono válido');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validateMessage(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El mensaje es requerido');
            return false;
        }

        if (value.length < 10) {
            setError(input, errorEl, 'El mensaje debe tener al menos 10 caracteres');
            return false;
        }

        if (value.length > 1000) {
            setError(input, errorEl, 'El mensaje no puede exceder 1000 caracteres');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validateAll(fields, errors) {
        let isValid = true;

        if (!validateName(fields.name, errors.name)) isValid = false;
        if (!validateEmail(fields.email, errors.email)) isValid = false;
        if (!validatePhone(fields.phone, errors.phone)) isValid = false;
        if (!validateMessage(fields.message, errors.message)) isValid = false;

        return isValid;
    }

    // ==================== SANITIZACIÓN ====================

    function sanitizeInput(input) {
        if (!input) return;

        let value = input.value;

        // Eliminar tags HTML
        value = value.replace(/<[^>]*>/g, '');

        // Eliminar scripts inline
        value = value.replace(/javascript:/gi, '');
        value = value.replace(/on\w+\s*=/gi, '');

        // Eliminar caracteres de control
        value = value.replace(/[\x00-\x1F\x7F]/g, '');

        input.value = value;
    }

    function sanitizeFormData(fields) {
        return {
            name: escapeHtml(fields.name.value.trim()),
            email: escapeHtml(fields.email.value.trim().toLowerCase()),
            phone: fields.phone.value.trim().replace(/[^0-9]/g, ''),
            company: escapeHtml(fields.company.value.trim()),
            message: escapeHtml(fields.message.value.trim()),
            is_whatsapp: fields.isWhatsapp.checked ? 1 : 0
        };
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        };
        return text.replace(/[&<>"'`=\/]/g, function(m) { return map[m]; });
    }

    // ==================== DETECCIÓN DE CONTENIDO MALICIOSO ====================

    function checkMaliciousContent(data) {
        const allText = Object.values(data).join(' ').toLowerCase();

        // Patrones de SQL Injection
        const sqlPatterns = [
            /(\b(select|insert|update|delete|drop|union|exec|execute)\b.*\b(from|into|where|table)\b)/i,
            /(--|;|\/\*|\*\/)/,
            /(\bor\b|\band\b)\s*[\d'"].*[=<>]/i,
            /['"].*(\bor\b|\band\b).*['"]/i
        ];

        // Patrones de XSS
        const xssPatterns = [
            /<script[\s\S]*?>[\s\S]*?<\/script>/gi,
            /javascript\s*:/gi,
            /on\w+\s*=\s*["']?[^"']*["']?/gi,
            /<iframe[\s\S]*?>/gi,
            /<object[\s\S]*?>/gi,
            /<embed[\s\S]*?>/gi,
            /<link[\s\S]*?>/gi,
            /expression\s*\(/gi,
            /url\s*\(\s*["']?\s*data:/gi
        ];

        // Patrones de inyección de comandos
        const cmdPatterns = [
            /[;&|`$]|\$\(/,
            /\.\.\//,
            /(\/etc\/passwd|\/bin\/sh|cmd\.exe)/i
        ];

        // Patrones de phishing/spam
        const spamPatterns = [
            /\b(viagra|cialis|lottery|winner|congratulations|click here|act now|limited time)\b/gi,
            /(bit\.ly|tinyurl|goo\.gl|t\.co)\/\w+/gi,
            /\b\d{16}\b/, // Números de tarjeta
        ];

        // Verificar SQL Injection
        for (const pattern of sqlPatterns) {
            if (pattern.test(allText)) {
                return { isMalicious: true, reason: 'Caracteres no permitidos detectados' };
            }
        }

        // Verificar XSS
        for (const pattern of xssPatterns) {
            if (pattern.test(allText)) {
                return { isMalicious: true, reason: 'Código no permitido detectado' };
            }
        }

        // Verificar inyección de comandos
        for (const pattern of cmdPatterns) {
            if (pattern.test(allText)) {
                return { isMalicious: true, reason: 'Caracteres especiales no permitidos' };
            }
        }

        // Verificar spam/phishing
        let spamCount = 0;
        for (const pattern of spamPatterns) {
            if (pattern.test(allText)) spamCount++;
        }
        if (spamCount >= 2) {
            return { isMalicious: true, reason: 'Contenido sospechoso detectado' };
        }

        // Verificar URLs excesivas
        const urlRegex = /https?:\/\/[^\s]+/gi;
        const urls = allText.match(urlRegex) || [];
        if (urls.length > 3) {
            return { isMalicious: true, reason: 'Demasiados enlaces en el mensaje' };
        }

        return { isMalicious: false };
    }

    // ==================== HONEYPOT ANTI-SPAM ====================

    function addHoneypot(form) {
        const honeypot = document.createElement('div');
        honeypot.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
        honeypot.innerHTML = '<input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">';
        form.appendChild(honeypot);
    }

    // ==================== UI HELPERS ====================

    function setError(input, errorEl, message) {
        if (input) {
            input.classList.remove('valid');
            input.classList.add('error');
        }
        if (errorEl) {
            errorEl.textContent = message;
        }
    }

    function setValid(input, errorEl) {
        if (input) {
            input.classList.remove('error');
            input.classList.add('valid');
        }
        if (errorEl) {
            errorEl.textContent = '';
        }
    }

    function showError(element, textElement, message) {
        if (textElement) textElement.textContent = message;
        showElement(element);
    }

    function showElement(element) {
        if (element) element.style.display = 'flex';
    }

    function hideElement(element) {
        if (element) element.style.display = 'none';
    }

    function scrollToElement(element) {
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function setLoadingState(btn, isLoading) {
        if (!btn) return;

        if (isLoading) {
            btn.classList.add('loading');
            btn.disabled = true;
        } else {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }

    function resetForm(form, fields) {
        form.reset();

        // Limpiar clases de validación
        Object.values(fields).forEach(field => {
            if (field && field.classList) {
                field.classList.remove('error', 'valid');
            }
        });

        // Limpiar mensajes de error
        document.querySelectorAll('.field-error').forEach(el => {
            el.textContent = '';
        });
    }

    // ==================== ENVÍO AL BACKEND ====================

    function submitToBackend(form, formData, successEl, errorEl, errorTextEl, submitBtn, fields) {
        // Obtener CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || document.querySelector('input[name="_token"]')?.value;

        // Preparar datos para enviar
        const payload = {
            name: formData.name,
            email: formData.email,
            phone: formData.phone,
            is_whatsapp: formData.is_whatsapp,
            company: formData.company,
            message: formData.message,
            website_url: form.querySelector('input[name="website_url"]')?.value || '' // Honeypot
        };

        // Enviar al backend
        fetch('/contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json().then(data => ({ status: response.status, data })))
        .then(({ status, data }) => {
            setLoadingState(submitBtn, false);

            if (status === 200 && data.success) {
                // Éxito
                showElement(successEl);
                resetForm(form, fields);
                scrollToElement(successEl);

                // Ocultar mensaje de éxito después de 5 segundos
                setTimeout(function() {
                    hideElement(successEl);
                }, 5000);
            } else if (status === 422 && data.errors) {
                // Errores de validación
                showError(errorEl, errorTextEl, data.message || 'Por favor corrige los errores.');

                // Mostrar errores en campos específicos
                Object.keys(data.errors).forEach(field => {
                    const errorSpan = document.getElementById('error_' + field);
                    const input = document.getElementById('contact_' + field);
                    if (errorSpan) {
                        errorSpan.textContent = data.errors[field][0];
                    }
                    if (input) {
                        input.classList.add('error');
                        input.classList.remove('valid');
                    }
                });

                scrollToElement(errorEl);
            } else if (status === 429) {
                // Rate limit
                showError(errorEl, errorTextEl, data.message || 'Demasiados intentos. Intenta más tarde.');
                scrollToElement(errorEl);
            } else {
                // Otro error
                showError(errorEl, errorTextEl, data.message || 'Ocurrió un error. Intenta de nuevo.');
                scrollToElement(errorEl);
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            setLoadingState(submitBtn, false);
            showError(errorEl, errorTextEl, 'Error de conexión. Verifica tu internet e intenta de nuevo.');
            scrollToElement(errorEl);
        });
    }

})();
