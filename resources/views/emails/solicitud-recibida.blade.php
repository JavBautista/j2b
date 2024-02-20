<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmaci&oacute;n de registro</title>
</head>
<body>
    <hr>

    <p>Hola {{$msg->name}} </p>
    <hr>

    <p>Gracias por registrarse en J2Biznes, por favor confirme su registro en el siguiente link:</p>

    <a href="{{route('solicitud.confirm',['xtoken'=>$msg->token])}}">Confirmación de resgitro</a>

    <p>Saludos.</p>
    <p>J2Biznes powered by Levcore.app</p>
    <p>2024</p>
</body>
</html>
