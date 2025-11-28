<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contraseña Actualizada | J2Biznes</title>
  <style>
    :root {
      --azul-j2b: #2563eb;
      --verde-agua-j2b: #0d9488;
      --gris-fondo: #f8fafc;
      --texto-oscuro: #1e293b;
      --texto-claro: #64748b;
    }

    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background-color: var(--gris-fondo);
      color: var(--texto-oscuro);
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      line-height: 1.6;
    }

    .success-box {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      padding: 3rem;
      max-width: 500px;
      width: 90%;
      text-align: center;
      border-top: 4px solid var(--verde-agua-j2b);
    }

    .logo {
      max-width: 200px;
      height: auto;
      margin-bottom: 1.5rem;
    }

    .success-icon {
      background-color: #dcfce7;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
    }

    .success-icon svg {
      color: var(--verde-agua-j2b);
      width: 40px;
      height: 40px;
    }

    h1 {
      color: var(--verde-agua-j2b);
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }

    .success-message {
      color: var(--texto-claro);
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
    }

    .footer-text {
      margin-top: 2rem;
      font-size: 0.875rem;
      color: var(--texto-claro);
    }
  </style>
</head>
<body>
  <div class="success-box">
    <img src="{{ asset('img/j2b_1200px.png') }}" alt="J2Biznes Logo" class="logo">

    <div class="success-icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </div>

    <h1>¡Contraseña Actualizada!</h1>

    <div class="success-message">
      <p>Tu contraseña ha sido restablecida exitosamente.</p>
      <p>Ahora puedes <strong>abrir la app</strong> e iniciar sesión con tu nueva contraseña.</p>
    </div>

    <p class="footer-text">Si tienes problemas para iniciar sesión, contacta a soporte.</p>
  </div>
</body>
</html>
