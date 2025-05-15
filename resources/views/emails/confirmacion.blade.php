<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu cuenta - J2Biznes</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: Arial, sans-serif !important; }
        .button { background-color: #0d9488 !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        /* Estilos base compatibles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #1e293b;
            line-height: 1.6;
        }
        
        table {
            border-spacing: 0;
            width: 100%;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        /* Header con fallback para Outlook */
        .header {
            background-color: #2563eb;
            padding: 30px 20px;
            text-align: center;
            mso-padding-alt: 30px 20px;
        }
        
        .logo {
            max-width: 180px;
            height: auto;
        }
        
        .content {
            padding: 30px;
            mso-padding-alt: 30px;
        }
        
        h1 {
            color: #2563eb;
            font-size: 24px;
            margin-top: 0;
        }
        
        /* Botón compatible con Outlook */
        .button-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .button {
            background-color: #0d9488;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            mso-padding-alt: 0;
        }
        
        /* Texto pequeño */
        .small-text {
            font-size: 14px;
            color: #64748b;
        }
        
        /* Divider */
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 25px 0;
        }
        
        /* Código de confirmación */
        .code-container {
            background-color: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
        }
        
        /* Footer */
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }
        
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!--[if mso]>
    <center>
    <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="600">
    <tr>
    <td>
    <![endif]-->
    
    <div class="email-container">
        <!-- Header con tabla para Outlook -->
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="header" style="background-color: #2563eb;">
                    <img src="https://j2b.levcore.app/img/j2b_b_60px.png" alt="J2Biznes Logo" class="logo" style="width: 180px; height: auto;">
                </td>
            </tr>
        </table>
        
        <!-- Contenido principal -->
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="content">
                    <h1 style="color: #2563eb; font-size: 24px; margin-top: 0;">¡Último paso, {{ $nombre }}!</h1>
                    <p>Gracias por registrarte en <strong>J2Biznes</strong>. Estás a un solo paso de acceder a todas las funcionalidades de nuestra plataforma.</p>
                    
                    <p>Por favor confirma tu dirección de correo electrónico haciendo clic en el siguiente botón:</p>
                    
                    <!-- Botón con tabla para Outlook -->
                    <div class="button-container">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" bgcolor="#0d9488" style="border-radius: 6px;">
                                    <a href="{{ $url }}" class="button" style="color: #ffffff; text-decoration: none; padding: 12px 24px; display: inline-block; font-weight: 600;">Confirmar mi cuenta</a>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="divider" style="height: 1px; background-color: #e2e8f0; margin: 25px 0;"></div>
                    
                    <p class="small-text" style="font-size: 14px; color: #64748b;">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
                    <div class="code-container" style="background-color: #f1f5f9; padding: 15px; border-radius: 6px; margin: 20px 0; word-break: break-all; font-family: monospace; font-size: 14px;">
                        {{ $url }}
                    </div>
                    
                    <p class="small-text" style="font-size: 14px; color: #64748b;">Este enlace expirará en 24 horas. Si no has solicitado este registro, por favor ignora este mensaje.</p>
                </td>
            </tr>
        </table>
        
        <!-- Footer -->
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="#f8fafc">
            <tr>
                <td class="footer" style="padding: 20px; text-align: center; font-size: 14px; color: #64748b;">
                    <p>© {{ date('Y') }} J2Biznes. Todos los derechos reservados.</p>
                    <p>
                        <a href="https://levcore.app" style="color: #2563eb; text-decoration: none;">Visita nuestro sitio web</a> | 
                        <a href="https://levcore.app/contacto" style="color: #2563eb; text-decoration: none;">Contacto</a>
                    </p>
                </td>
            </tr>
        </table>
    </div>
    
    <!--[if mso]>
    </td>
    </tr>
    </table>
    </center>
    <![endif]-->
</body>
</html>