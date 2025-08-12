@extends('auth.contenido')

@section('estilos')
<style>
    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #00ff88 0%, #00d4ff 50%, #1a1a2e 100%);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        overflow-x: hidden;
    }

    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 20px;
    }

    /* Animated background particles */
    .bg-animation {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
    }

    .bg-animation::before,
    .bg-animation::after {
        content: '';
        position: absolute;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }

    .bg-animation::before {
        width: 200px;
        height: 200px;
        top: 10%;
        left: 10%;
        animation-delay: -2s;
    }

    .bg-animation::after {
        width: 300px;
        height: 300px;
        bottom: 10%;
        right: 10%;
        animation-delay: -4s;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
        50% { transform: translateY(-20px) rotate(180deg); opacity: 0.3; }
    }

    .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.1),
            0 1px 3px rgba(0, 0, 0, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.2);
        width: 100%;
        max-width: 440px;
        position: relative;
        z-index: 2;
        overflow: hidden;
        animation: slideIn 0.8s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .login-header {
        text-align: center;
        padding: 40px 40px 20px;
        position: relative;
    }

    .logo-container {
        position: relative;
        display: inline-block;
        margin-bottom: 20px;
    }

    .logo-ring {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00ff88, #00d4ff);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        position: relative;
        animation: pulse 2s infinite;
    }

    .logo-ring::before {
        content: '';
        position: absolute;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 2px solid transparent;
        background: linear-gradient(135deg, #00ff88, #00d4ff) border-box;
        mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
        mask-composite: exclude;
        animation: rotate 3s linear infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .logo-ring img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        z-index: 1;
        position: relative;
    }

    .login-title {
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px 0;
        background: linear-gradient(135deg, #1a1a2e, #00d4ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .login-subtitle {
        color: #64748b;
        font-size: 16px;
        margin: 0;
        font-weight: 400;
    }

    .login-form {
        padding: 0 40px 40px;
    }

    .form-group {
        margin-bottom: 24px;
        position: relative;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .input-wrapper {
        position: relative;
    }

    .form-control {
        width: 100%;
        padding: 16px 20px 16px 50px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #ffffff;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #00ff88;
        box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
        transform: translateY(-2px);
    }

    .form-control.is-invalid {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 18px;
        z-index: 1;
        transition: color 0.3s ease;
    }

    .form-control:focus + .input-icon {
        color: #00ff88;
    }

    .invalid-feedback {
        display: block;
        color: #ef4444;
        font-size: 14px;
        margin-top: 6px;
        font-weight: 500;
    }

    .remember-section {
        display: flex;
        align-items: center;
        margin-bottom: 32px;
    }

    .custom-checkbox {
        display: flex;
        align-items: center;
        cursor: pointer;
    }

    .custom-checkbox input[type="checkbox"] {
        display: none;
    }

    .checkbox-mark {
        width: 20px;
        height: 20px;
        border: 2px solid #d1d5db;
        border-radius: 6px;
        margin-right: 12px;
        position: relative;
        transition: all 0.3s ease;
        background: white;
    }

    .custom-checkbox input:checked + .checkbox-mark {
        background: linear-gradient(135deg, #00ff88, #00d4ff);
        border-color: #00ff88;
    }

    .checkbox-mark::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        color: white;
        font-weight: bold;
        font-size: 12px;
        transition: transform 0.2s ease;
    }

    .custom-checkbox input:checked + .checkbox-mark::after {
        transform: translate(-50%, -50%) scale(1);
    }

    .checkbox-label {
        color: #374151;
        font-size: 14px;
        font-weight: 500;
    }

    .login-button {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #00ff88, #00d4ff);
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .login-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .login-button:hover::before {
        left: 100%;
    }

    .login-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 255, 136, 0.3);
    }

    .login-button:active {
        transform: translateY(0);
    }

    .register-section {
        text-align: center;
        padding: 24px 40px;
        background: rgba(248, 250, 252, 0.8);
        border-top: 1px solid rgba(226, 232, 240, 0.5);
        border-radius: 0 0 24px 24px;
    }

    .register-text {
        color: #64748b;
        font-size: 14px;
        margin: 0 0 12px 0;
    }

    .register-link {
        color: #00d4ff;
        text-decoration: none;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .register-link:hover {
        background: rgba(0, 212, 255, 0.1);
        transform: translateY(-1px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .login-card {
            margin: 20px;
            max-width: none;
        }
        
        .login-header, .login-form, .register-section {
            padding-left: 24px;
            padding-right: 24px;
        }
        
        .login-title {
            font-size: 28px;
        }
    }
</style>
@endsection

@section('login')
<div class="login-container">
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-container">
                <div class="logo-ring">
                    <img src="{{ asset('img/j2b_1200px.png') }}" alt="J2Biznes Logo">
                </div>
            </div>
            <h1 class="login-title">Bienvenido</h1>
            <p class="login-subtitle">Accede a tu panel de control</p>
        </div>

        <!-- Form -->
        <form class="login-form" method="POST" action="{{ route('login') }}">
            @csrf
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <div class="input-wrapper">
                    <input 
                        id="email" 
                        type="email" 
                        name="email"  
                        class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" 
                        placeholder="tu@correo.com" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                    >
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                @if ($errors->has('email'))
                    <div class="invalid-feedback">
                        {{ $errors->first('email') }}
                    </div>
                @endif
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-wrapper">
                    <input 
                        id="password" 
                        type="password" 
                        class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" 
                        name="password" 
                        required 
                        placeholder="Tu contraseña segura"
                    >
                    <i class="fas fa-lock input-icon"></i>
                </div>
                @if ($errors->has('password'))
                    <div class="invalid-feedback">
                        {{ $errors->first('password') }}
                    </div>
                @endif
            </div>

            <!-- Remember Me -->
            <div class="remember-section">
                <label class="custom-checkbox">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span class="checkbox-mark"></span>
                    <span class="checkbox-label">Mantener sesión iniciada</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-button">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                Iniciar Sesión
            </button>
        </form>

        <!-- Navigation Section -->
        <div class="register-section">
            <!-- <p class="register-text">¿No tienes una cuenta?</p>
            <a href="{{ route('solicitud') }}" class="register-link">
                <i class="fas fa-user-plus" style="margin-right: 6px;"></i>
                Crear cuenta nueva
            </a> -->
            <a href="{{ url('/') }}" class="register-link">
                <i class="fas fa-arrow-left" style="margin-right: 6px;"></i>
                Regresar al inicio
            </a>
        </div>
    </div>
</div>

<script>
// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    // Add floating effect to form inputs
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });

    // Add ripple effect to button
    const button = document.querySelector('.login-button');
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('div');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        `;
        
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});

// Add ripple animation
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>
@endsection