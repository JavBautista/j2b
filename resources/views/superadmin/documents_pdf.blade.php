<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        @page { margin: 110px 70px 90px 70px; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.55;
            color: #1a1a2e;
        }

        /* Encabezado y pie en cada pagina */
        header {
            position: fixed;
            top: -70px; left: 0; right: 0;
            height: 50px;
            text-align: center;
            color: #6c757d;
            font-size: 9px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 6px;
        }
        footer {
            position: fixed;
            bottom: -60px; left: 0; right: 0;
            height: 40px;
            color: #6c757d;
            font-size: 9px;
            border-top: 1px solid #dee2e6;
            padding-top: 6px;
        }
        footer .pagenum:before { content: counter(page); }

        h1 { font-size: 18px; margin: 0 0 14px; text-align: center; text-transform: uppercase; }
        h2 { font-size: 14px; margin: 18px 0 8px; border-bottom: 1px solid #e9ecef; padding-bottom: 3px; }
        h3 { font-size: 12px; margin: 14px 0 6px; }
        h4 { font-size: 11px; margin: 12px 0 5px; }

        p { margin: 0 0 8px; text-align: justify; }
        ul, ol { margin: 0 0 8px; padding-left: 20px; }
        li { margin-bottom: 3px; }

        strong { font-weight: bold; }
        em { font-style: italic; }
        code { font-family: 'DejaVu Sans Mono', monospace; background: #f1f3f5; padding: 1px 3px; font-size: 10px; }

        blockquote {
            margin: 8px 0;
            padding: 6px 12px;
            background: #f8f9fa;
            border-left: 3px solid #adb5bd;
            color: #495057;
            font-size: 10px;
        }

        hr { border: none; border-top: 1px solid #ced4da; margin: 16px 0; }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ced4da; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f1f3f5; font-weight: bold; }

        a { color: #1a1a2e; text-decoration: none; }
    </style>
</head>
<body>
    <header>{{ $document->title }} @if($document->version) &mdash; v{{ $document->version }} @endif</header>
    <footer>
        <span style="float:left;">Documento generado por J2Biznes</span>
        <span style="float:right;">Página <span class="pagenum"></span></span>
    </footer>

    <main>
        {!! $html !!}
    </main>
</body>
</html>
