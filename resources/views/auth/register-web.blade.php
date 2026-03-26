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
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px 0;
        background: linear-gradient(135deg, #1a1a2e, #00d4ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .login-subtitle {
        color: #64748b;
        font-size: 15px;
        margin: 0;
        font-weight: 400;
    }

    .login-form {
        padding: 0 40px 30px;
    }

    .form-group {
        margin-bottom: 18px;
        position: relative;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        font-size: 14px;
    }

    .input-wrapper {
        position: relative;
    }

    .form-control {
        width: 100%;
        padding: 14px 18px 14px 46px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s ease;
        background: #ffffff;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #00ff88;
        box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
        transform: translateY(-1px);
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
        font-size: 16px;
        z-index: 1;
        transition: color 0.3s ease;
    }

    .form-control:focus + .input-icon {
        color: #00ff88;
    }

    .invalid-feedback {
        display: block;
        color: #ef4444;
        font-size: 13px;
        margin-top: 4px;
        font-weight: 500;
    }

    .row-2col {
        display: flex;
        gap: 12px;
    }

    .row-2col .form-group {
        flex: 1;
    }

    .login-button {
        width: 100%;
        padding: 14px;
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
        margin-top: 6px;
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

    .register-section {
        text-align: center;
        padding: 20px 40px;
        background: rgba(248, 250, 252, 0.8);
        border-top: 1px solid rgba(226, 232, 240, 0.5);
        border-radius: 0 0 24px 24px;
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

    .alert-success-custom {
        background: linear-gradient(135deg, #dcfce7, #d1fae5);
        border: 1px solid #86efac;
        color: #166534;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.5;
        text-align: center;
    }

    .alert-success-custom i {
        margin-right: 8px;
        font-size: 18px;
    }

    @media (max-width: 768px) {
        .login-card {
            margin: 10px;
            max-width: none;
        }

        .login-header, .login-form, .register-section {
            padding-left: 24px;
            padding-right: 24px;
        }

        .login-title {
            font-size: 24px;
        }

        .row-2col {
            flex-direction: column;
            gap: 0;
        }
    }
</style>
@endsection

@section('login')
<div class="login-container">
    <div class="bg-animation"></div>

    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-container">
                <div class="logo-ring">
                    <img src="{{ asset('img/j2b_1200px.png') }}" alt="J2Biznes Logo">
                </div>
            </div>
            <h1 class="login-title">Crear Cuenta</h1>
            <p class="login-subtitle">Prueba gratis por 30 dias</p>
        </div>

        <!-- Form -->
        <form class="login-form" method="POST" action="{{ route('web.register.store') }}">
            @csrf

            @if(session('success'))
                <div class="alert-success-custom">
                    <i class="fas fa-envelope-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Nombre -->
            <div class="form-group">
                <label for="name">Nombre completo</label>
                <div class="input-wrapper">
                    <input
                        id="name"
                        type="text"
                        name="name"
                        class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}"
                        placeholder="Tu nombre"
                        value="{{ old('name') }}"
                        required
                        autofocus
                    >
                    <i class="fas fa-user input-icon"></i>
                </div>
                @if ($errors->has('name'))
                    <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                @endif
            </div>

            <!-- Negocio -->
            <div class="form-group">
                <label for="shop">Nombre de tu negocio</label>
                <div class="input-wrapper">
                    <input
                        id="shop"
                        type="text"
                        name="shop"
                        class="form-control{{ $errors->has('shop') ? ' is-invalid' : '' }}"
                        placeholder="Mi Tienda"
                        value="{{ old('shop') }}"
                        required
                    >
                    <i class="fas fa-store input-icon"></i>
                </div>
                @if ($errors->has('shop'))
                    <div class="invalid-feedback">{{ $errors->first('shop') }}</div>
                @endif
            </div>

            <!-- Telefono y Email en 2 columnas -->
            <div class="row-2col">
                <div class="form-group">
                    <label for="phone">Telefono (10 digitos)</label>
                    <div class="input-wrapper">
                        <input
                            id="phone"
                            type="text"
                            name="phone"
                            class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}"
                            placeholder="4421234567"
                            value="{{ old('phone') }}"
                            required
                            maxlength="10"
                        >
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                    @if ($errors->has('phone'))
                        <div class="invalid-feedback">{{ $errors->first('phone') }}</div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="email">Correo electronico</label>
                    <div class="input-wrapper">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                            placeholder="tu@correo.com"
                            value="{{ old('email') }}"
                            required
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    @if ($errors->has('email'))
                        <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                    @endif
                </div>
            </div>

            <!-- Password y Confirmacion -->
            <div class="row-2col">
                <div class="form-group">
                    <label for="password">Contrasena</label>
                    <div class="input-wrapper">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                            placeholder="Min. 8 caracteres"
                            required
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    @if ($errors->has('password'))
                        <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmar</label>
                    <div class="input-wrapper">
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Repetir contrasena"
                            required
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="login-button">
                <i class="fas fa-user-plus" style="margin-right: 8px;"></i>
                Crear mi cuenta
            </button>
        </form>

        <!-- Footer -->
        <div class="register-section">
            <a href="{{ route('login') }}" class="register-link">
                <i class="fas fa-sign-in-alt" style="margin-right: 6px;"></i>
                Ya tengo cuenta, iniciar sesion
            </a>
        </div>
    </div>
</div>
@endsection
