<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cuenta Confirmada | J2Biznes</title>
  <style>
    :root {
      --azul-j2b: #2563eb;       /* Azul principal */
      --verde-agua-j2b: #0d9488; /* Verde agua principal */
      --azul-claro: #93c5fd;     /* Azul claro */
      --gris-fondo: #f8fafc;     /* Fondo gris claro */
      --texto-oscuro: #1e293b;   /* Texto principal */
      --texto-claro: #64748b;    /* Texto secundario */
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
    
    .confirmation-box {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      padding: 3rem;
      max-width: 500px;
      width: 90%;
      text-align: center;
      border-top: 4px solid var(--verde-agua-j2b);
    }
    
    .logo-container {
      margin-bottom: 1.5rem;
    }
    
    .logo {
      max-width: 200px;
      height: auto;
    }
    
    .confirmation-icon {
      background-color: #dcfce7;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
    }
    
    .confirmation-icon svg {
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
    
    .confirmation-message {
      color: var(--texto-claro);
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
    }
    
    .app-button {
      background-color: var(--azul-j2b);
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      margin-top: 1rem;
      transition: background-color 0.3s;
    }
    
    .app-button:hover {
      background-color: #1d4ed8;
    }
    
    .footer-text {
      margin-top: 2rem;
      font-size: 0.875rem;
      color: var(--texto-claro);
    }
  </style>
</head>
<body>
  <div class="confirmation-box">
    <div class="logo-container">
      <img src="{{ asset('img/j2b_1200px.png') }}" alt="J2Biznes Logo" class="logo">
    </div>
    
    <div class="confirmation-icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </div>
    
    <h1>¡Cuenta Confirmada con Éxito!</h1>
    
    <div class="confirmation-message">
      <p>Tu correo electrónico ha sido verificado correctamente.</p>
      <!--<p>Ahora puedes acceder a todas las funcionalidades de J2Biznes.</p>-->
      <p>Ahora puedes <strong>abrir la app</strong> e iniciar sesión con tus datos.</p>
    </div>
    
    <div>
      <!--<a href="j2biznesapp://login" class="app-button">Abrir la Aplicación</a>
      <p class="footer-text">Si la aplicación no se abre automáticamente, búscala en tu dispositivo</p>-->
    </div>
  </div>
</body>
</html>