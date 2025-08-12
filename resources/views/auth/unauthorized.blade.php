<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso No Autorizado - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .unauthorized-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 500px;
            text-align: center;
            padding: 3rem 2rem;
        }
        .icon-warning {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 1rem;
        }
        .btn-home {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="unauthorized-card">
                    <div class="icon-warning">
                        🚫
                    </div>
                    <h1 class="h2 mb-4 text-dark">Acceso No Autorizado</h1>
                    <p class="text-muted mb-4">
                        Tu cuenta no tiene permisos para acceder al panel web. Cerrando sesión automáticamente...
                    </p>
                    <div class="alert alert-warning" role="alert">
                        <strong>Redirigiendo en <span id="countdown">3</span> segundos...</strong>
                    </div>
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cerrando sesión...</span>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto logout después de 3 segundos
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                document.getElementById('logout-form').submit();
            }
        }, 1000);
        
        // Por si el formulario no funciona, forzar redirección
        setTimeout(function() {
            window.location.href = '/';
        }, 4000);
    </script>
</body>
</html>