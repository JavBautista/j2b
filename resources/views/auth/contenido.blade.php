<!DOCTYPE html>
<html lang="es">
<head>

  <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>J2Biznes</title>
  <link rel="icon" href="{{ asset('/images/favicon.ico') }}">

  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   @yield('estilos')
</head>

<body>
  <div class="container">
    @yield('login')
  </div>
  <!-- Bootstrap and necessary plugins -->
  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/dashboard.js') }}" defer></script>

</body>
</html>