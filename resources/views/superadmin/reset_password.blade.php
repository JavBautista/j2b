@extends('superadmin.layouts.app')

@section('content')
<div class="container-fluid" style="padding: 1.5rem;">

    <!-- Header con titulo -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-key" style="color: var(--j2b-primary);"></i> Cambiar Contrasena
            </h4>
            <p class="mb-0" style="color: var(--j2b-gray-500);">Actualiza tu contrasena de acceso</p>
        </div>
        <a href="{{ route('superadmin.index') }}" class="j2b-btn j2b-btn-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Mensaje de exito -->
    @if(session('success'))
        <div class="j2b-banner-alert j2b-banner-success mb-4">
            <i class="fa fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Mensaje de error -->
    @if($errors->any())
        <div class="j2b-banner-alert j2b-banner-danger mb-4">
            <i class="fa fa-exclamation-circle"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <!-- Card principal -->
            <div class="j2b-card">
                <div class="j2b-card-header" style="background: var(--j2b-gradient-dark); color: white;">
                    <h5 class="mb-0">
                        <i class="fa fa-lock"></i> Nueva Contrasena
                    </h5>
                </div>
                <div class="j2b-card-body" style="padding: 2rem;">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <!-- Contrasena actual -->
                        <div class="mb-4">
                            <label for="password-old" class="j2b-label">
                                <i class="fa fa-unlock-alt j2b-text-warning"></i> Contrasena Actual
                            </label>
                            <div class="position-relative">
                                <input
                                    id="password-old"
                                    type="password"
                                    class="j2b-input @error('password-old') is-invalid @enderror"
                                    name="password-old"
                                    required
                                    placeholder="Ingresa tu contrasena actual"
                                    style="padding-left: 40px;"
                                >
                                <i class="fa fa-key" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--j2b-gray-400);"></i>
                            </div>
                            @error('password-old')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <hr style="border-color: var(--j2b-gray-200); margin: 1.5rem 0;">

                        <!-- Nueva contrasena -->
                        <div class="mb-4">
                            <label for="password" class="j2b-label">
                                <i class="fa fa-lock j2b-text-primary"></i> Nueva Contrasena
                            </label>
                            <div class="position-relative">
                                <input
                                    id="password"
                                    type="password"
                                    class="j2b-input @error('password') is-invalid @enderror"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Minimo 8 caracteres"
                                    style="padding-left: 40px;"
                                >
                                <i class="fa fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--j2b-gray-400);"></i>
                            </div>
                            @error('password')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <small class="j2b-text-muted d-block mt-1">
                                <i class="fa fa-info-circle"></i> Usa al menos 8 caracteres con letras y numeros
                            </small>
                        </div>

                        <!-- Confirmar contrasena -->
                        <div class="mb-4">
                            <label for="password-confirm" class="j2b-label">
                                <i class="fa fa-check-circle j2b-text-success"></i> Confirmar Contrasena
                            </label>
                            <div class="position-relative">
                                <input
                                    id="password-confirm"
                                    type="password"
                                    class="j2b-input"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Repite la nueva contrasena"
                                    style="padding-left: 40px;"
                                >
                                <i class="fa fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--j2b-gray-400);"></i>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="j2b-btn j2b-btn-primary j2b-btn-lg" style="flex: 1;">
                                <i class="fa fa-save"></i> Guardar Contrasena
                            </button>
                            <a href="{{ route('superadmin.index') }}" class="j2b-btn j2b-btn-secondary j2b-btn-lg">
                                <i class="fa fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info adicional -->
            <div class="j2b-card mt-4" style="border-left: 4px solid var(--j2b-info);">
                <div class="j2b-card-body py-3">
                    <h6 style="color: var(--j2b-dark); margin-bottom: 0.5rem;">
                        <i class="fa fa-shield" style="color: var(--j2b-info);"></i> Consejos de Seguridad
                    </h6>
                    <ul class="mb-0" style="color: var(--j2b-gray-600); font-size: 0.9em; padding-left: 1.2rem;">
                        <li>No compartas tu contrasena con nadie</li>
                        <li>Usa una combinacion de letras, numeros y simbolos</li>
                        <li>Evita usar informacion personal como fechas o nombres</li>
                        <li>Cambia tu contrasena periodicamente</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.j2b-banner-alert.j2b-banner-success {
    background: rgba(0, 245, 160, 0.1);
    border: 1px solid var(--j2b-primary);
    border-radius: var(--j2b-radius-md);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--j2b-dark);
}
.j2b-banner-alert.j2b-banner-success i {
    color: var(--j2b-primary);
    font-size: 1.2em;
}

.j2b-banner-alert.j2b-banner-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid #ef4444;
    border-radius: var(--j2b-radius-md);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: var(--j2b-dark);
}
.j2b-banner-alert.j2b-banner-danger i {
    color: #ef4444;
    font-size: 1.2em;
    margin-top: 2px;
}
</style>
@endsection
