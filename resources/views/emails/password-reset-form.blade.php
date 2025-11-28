<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer Contraseña | J2Biznes</title>
  <style>
    :root {
      --azul-j2b: #2563eb;
      --verde-agua-j2b: #0d9488;
      --gris-fondo: #f8fafc;
      --texto-oscuro: #1e293b;
      --texto-claro: #64748b;
      --rojo-error: #dc2626;
    }

    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background-color: var(--gris-fondo);
      color: var(--texto-oscuro);
      margin: 0;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      line-height: 1.6;
    }

    .reset-box {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      padding: 3rem;
      max-width: 500px;
      width: 100%;
      border-top: 4px solid var(--azul-j2b);
    }

    .logo {
      max-width: 180px;
      height: auto;
      display: block;
      margin: 0 auto 2rem;
    }

    h1 {
      color: var(--azul-j2b);
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      text-align: center;
    }

    .subtitle {
      color: var(--texto-claro);
      text-align: center;
      margin-bottom: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--texto-oscuro);
    }

    input[type="password"],
    input[type="email"] {
      width: 100%;
      padding: 12px;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }

    input:focus {
      outline: none;
      border-color: var(--azul-j2b);
    }

    .btn {
      width: 100%;
      padding: 14px;
      background-color: var(--verde-agua-j2b);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn:hover {
      background-color: #0f766e;
    }

    .error {
      background-color: #fee2e2;
      border-left: 4px solid var(--rojo-error);
      color: var(--rojo-error);
      padding: 12px;
      border-radius: 4px;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }

    .help-text {
      font-size: 0.875rem;
      color: var(--texto-claro);
      margin-top: 0.5rem;
    }

    @media (max-width: 600px) {
      .reset-box {
        padding: 2rem 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="reset-box">
    <img src="{{ asset('img/j2b_1200px.png') }}" alt="J2Biznes Logo" class="logo">

    <h1>Restablecer Contraseña</h1>
    <p class="subtitle">Ingresa tu nueva contraseña</p>

    @if ($errors->any())
      <div class="error">
        @foreach ($errors->all() as $error)
          {{ $error }}<br>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('password.reset.process') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <input type="hidden" name="email" value="{{ $email }}">

      <div class="form-group">
        <label for="password">Nueva Contraseña</label>
        <input
          type="password"
          id="password"
          name="password"
          required
          minlength="8"
          placeholder="Mínimo 8 caracteres">
        <p class="help-text">La contraseña debe tener al menos 8 caracteres</p>
      </div>

      <div class="form-group">
        <label for="password_confirmation">Confirmar Nueva Contraseña</label>
        <input
          type="password"
          id="password_confirmation"
          name="password_confirmation"
          required
          minlength="8"
          placeholder="Repite tu contraseña">
      </div>

      <button type="submit" class="btn">Restablecer Contraseña</button>
    </form>
  </div>
</body>
</html>
