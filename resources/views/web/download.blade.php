@extends('web.layouts.app')

@section('title', 'Descargar J2Biznes - App de Gestión Empresarial')
@section('description', 'Descarga gratis J2Biznes, la app de punto de venta y gestión empresarial para tu negocio. Disponible para Android.')

@section('content')
    <section class="download-section">
        <div class="container">
            <div class="download-wrapper">
                <div class="download-info scroll-reveal">
                    <div class="download-badge">
                        <i class="fab fa-android"></i> Android
                    </div>
                    <h1>Descarga J2Biznes Gratis</h1>
                    <p>Obtén acceso inmediato a la app que transformará la forma en que manejas tu negocio. Control de inventarios, punto de venta, clientes y mucho más.</p>

                    <div class="download-features">
                        <div class="download-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Prueba gratuita de {{ $trialDays }} días</span>
                        </div>
                        <div class="download-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Sin tarjeta de crédito</span>
                        </div>
                        <div class="download-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Soporte en español</span>
                        </div>
                        <div class="download-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Instalación en 2 minutos</span>
                        </div>
                    </div>

                    <div class="download-requirements">
                        <h4><i class="fas fa-mobile-alt"></i> Requisitos</h4>
                        <p>Android 5.1 o superior</p>
                    </div>
                </div>

                <div class="download-form-side scroll-reveal">
                    <div class="download-form-card">
                        <div class="form-header">
                            <i class="fas fa-download"></i>
                            <h3>Descarga Directa</h3>
                            <p>Ingresa tu correo para descargar</p>
                        </div>

                        @if(session('error'))
                            <div class="form-alert form-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>{{ session('error') }}</span>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="form-alert form-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form action="{{ route('download.process') }}" method="POST" class="download-form">
                            @csrf
                            <div class="form-group">
                                <label for="email">Correo electrónico</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="tu@email.com"
                                    required
                                    maxlength="255"
                                    value="{{ old('email') }}"
                                >
                            </div>

                            <button type="submit" class="download-btn">
                                <i class="fas fa-download"></i>
                                <span>Descargar APK</span>
                            </button>

                            <p class="form-disclaimer">
                                <i class="fas fa-lock"></i>
                                Tu correo solo se usará para enviarte actualizaciones importantes. No spam.
                            </p>
                        </form>

                        <div class="download-help">
                            <p><i class="fas fa-question-circle"></i> ¿Necesitas ayuda con la instalación?</p>
                            <a href="https://wa.me/524425592717?text=Hola%2C%20necesito%20ayuda%20para%20instalar%20J2Biznes" target="_blank" class="help-link">
                                <i class="fab fa-whatsapp"></i> Escríbenos por WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
.download-section {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    padding: 100px 0 60px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.download-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    max-width: 1100px;
    margin: 0 auto;
}

.download-info h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 20px;
    line-height: 1.2;
}

.download-info > p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    line-height: 1.7;
    margin-bottom: 30px;
}

.download-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0, 255, 136, 0.1);
    color: #00ff88;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 20px;
}

.download-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 30px;
}

.download-feature {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
}

.download-feature i {
    color: #00ff88;
    font-size: 1.1rem;
}

.download-requirements {
    background: rgba(255, 255, 255, 0.05);
    padding: 15px 20px;
    border-radius: 10px;
    border-left: 3px solid #00ff88;
}

.download-requirements h4 {
    color: #fff;
    font-size: 0.95rem;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.download-requirements p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin: 0;
}

.download-form-card {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
}

.form-header i {
    font-size: 3rem;
    color: #00ff88;
    margin-bottom: 15px;
}

.form-header h3 {
    font-size: 1.5rem;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.form-header p {
    color: #666;
    font-size: 0.95rem;
}

.download-form .form-group {
    margin-bottom: 20px;
}

.download-form label {
    display: block;
    color: #333;
    font-weight: 500;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.download-form input[type="email"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.download-form input[type="email"]:focus {
    outline: none;
    border-color: #00ff88;
    box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
}

.download-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
    color: #1a1a2e;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.download-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
}

.form-disclaimer {
    text-align: center;
    color: #888;
    font-size: 0.85rem;
    margin-top: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.form-alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

.form-alert-error {
    background: #fff5f5;
    color: #dc3545;
    border: 1px solid #f8d7da;
}

.download-help {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    text-align: center;
}

.download-help p {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.help-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #25d366;
    font-weight: 500;
    text-decoration: none;
    transition: opacity 0.3s;
}

.help-link:hover {
    opacity: 0.8;
}

/* Responsive */
@media (max-width: 900px) {
    .download-wrapper {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .download-info {
        text-align: center;
    }

    .download-features {
        justify-content: center;
    }

    .download-requirements {
        text-align: left;
    }
}

@media (max-width: 600px) {
    .download-section {
        padding: 80px 20px 40px;
    }

    .download-info h1 {
        font-size: 1.8rem;
    }

    .download-features {
        grid-template-columns: 1fr;
    }

    .download-form-card {
        padding: 30px 20px;
    }
}
</style>
@endpush
