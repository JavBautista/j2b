<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en el Registro | J2Biznes</title>
    <style>
        :root {
            --primary-color: #4f46e5; /* Color principal de tu app */
            --error-color: #dc2626;
            --bg-color: #f8fafc;
            --text-color: #1e293b;
        }
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
        }
        .error-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
            border-left: 5px solid var(--error-color);
        }
        .error-icon {
            font-size: 3rem;
            color: var(--error-color);
            margin-bottom: 1rem;
        }
        h1 {
            color: var(--error-color);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .error-message {
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1.5rem;
        }
        .action-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .action-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <!-- Puedes añadir tu logo aquí -->
        <img src="{{ asset('img/j2b_1200px.png') }}" width="50%" alt="J2Biznes Logo" class="logo">
        
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        
        <h1>Error en el proceso de registro</h1>
        
        <div class="error-message">
            {{ $message }}
        </div>
        
        <div>
            <p>Por favor intenta nuevamente o contacta a nuestro soporte técnico.</p>
            <!--<a href="{{ url('/') }}" class="action-link">Volver al inicio</a>-->
        </div>
    </div>
</body>
</html>