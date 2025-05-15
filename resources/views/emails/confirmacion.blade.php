<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu cuenta - J2Biznes</title>
    <style>
        :root {
            --azul-j2b: #2563eb;
            --verde-agua-j2b: #0d9488;
            --gris-claro: #f8fafc;
            --texto-oscuro: #1e293b;
            --texto-claro: #64748b;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: var(--texto-oscuro);
            line-height: 1.6;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background-color: var(--azul-j2b);
            padding: 30px 20px;
            text-align: center;
        }
        
        .logo {
            max-width: 180px;
            height: auto;
        }
        
        .content {
            padding: 30px;
        }
        
        h1 {
            color: var(--azul-j2b);
            font-size: 24px;
            margin-top: 0;
        }
        
        .confirmation-button {
            display: inline-block;
            background-color: var(--verde-agua-j2b);
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        
        .footer {
            background-color: var(--gris-claro);
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: var(--texto-claro);
        }
        
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 25px 0;
        }
        
        .code-container {
            background-color: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
        }
        
        .small-text {
            font-size: 14px;
            color: var(--texto-claro);
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="https://j2b.levcore.app/img/j2b_b_60px.png" alt="J2Biznes Logo" class="logo">
        </div>
        
        <div class="content">
            <h1>¡Último paso, {{ $nombre }}!</h1>
            <p>Gracias por registrarte en <strong>J2Biznes</strong>. Estás a un solo paso de acceder a todas las funcionalidades de nuestra plataforma.</p>
            
            <p>Por favor confirma tu dirección de correo electrónico haciendo clic en el siguiente botón:</p>
            
            <div style="text-align: center;">
                <a href="{{ $url }}" class="confirmation-button">Confirmar mi cuenta</a>
            </div>
            
            <div class="divider"></div>
            
            <p class="small-text">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <div class="code-container">
                {{ $url }}
            </div>
            
            <p class="small-text">Este enlace expirará en 24 horas. Si no has solicitado este registro, por favor ignora este mensaje.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} J2Biznes. Todos los derechos reservados.</p>
            <p>
                <a href="https://levcore.app" style="color: var(--azul-j2b); text-decoration: none;">Visita nuestro sitio web</a> | 
                <a href="https://levcore.app/contacto" style="color: var(--azul-j2b); text-decoration: none;">Contacto</a>
            </p>
        </div>
    </div>
</body>
</html>