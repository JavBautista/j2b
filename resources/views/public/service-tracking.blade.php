<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Servicio - {{ $shop->name }}</title>
    <link rel="icon" href="/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 24px 16px;
            text-align: center;
        }
        .header-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid rgba(255,255,255,0.2);
        }
        .header h1 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        .tracking-code {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 8px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px 16px 40px;
        }

        .service-title {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .service-title h2 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a2e;
        }
        .service-title .meta {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 4px;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #dee2e6;
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 0;
            padding: 12px 0;
        }

        .timeline-dot {
            position: absolute;
            left: -40px;
            top: 14px;
            width: 33px;
            height: 33px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            z-index: 1;
        }
        .timeline-dot.completed {
            background: #28a745;
        }
        .timeline-dot.current {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
        }
        .timeline-dot.pending {
            background: #dee2e6;
            color: #adb5bd;
        }

        .timeline-content {
            background: #fff;
            border-radius: 10px;
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .timeline-content.current {
            border: 2px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .timeline-content.pending {
            opacity: 0.5;
        }

        .step-name {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .step-desc {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 2px;
        }
        .step-date {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 6px;
        }
        .step-note {
            font-size: 0.8rem;
            color: #495057;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 6px 10px;
            margin-top: 6px;
            font-style: italic;
        }
        .step-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            color: #fff;
            margin-left: 6px;
            vertical-align: middle;
        }

        .footer {
            text-align: center;
            padding: 20px 16px;
            font-size: 0.75rem;
            color: #adb5bd;
        }
        .footer a {
            color: #6c757d;
            text-decoration: none;
        }

        /* Evidencia */
        .evidence-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .evidence-gallery img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .evidence-gallery img:hover {
            transform: scale(1.08);
        }
        /* Lightbox */
        .lightbox-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .lightbox-overlay.active {
            display: flex;
        }
        .lightbox-overlay img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
        }
        .lightbox-close {
            position: absolute;
            top: 16px; right: 20px;
            color: #fff;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
        }
    </style>
</head>
<body>

    <div class="header">
        @if($shop->logo)
            <img src="{{ asset('storage/' . $shop->logo) }}" alt="{{ $shop->name }}" class="header-logo">
        @endif
        <h1>{{ $shop->name }}</h1>
        <p>Seguimiento de Servicio</p>
        <div class="tracking-code">{{ $task->tracking_code }}</div>
    </div>

    <div class="container">
        <div class="service-title">
            <h2>{{ $task->title }}</h2>
            <div class="meta">
                Folio #{{ $task->folio ?? $task->id }}
                &middot; Creada {{ \Carbon\Carbon::parse($task->created_at)->format('d/m/Y') }}
            </div>
        </div>

        <div class="timeline">
            @foreach($steps as $index => $step)
                @php
                    $isCompleted = $index < $currentStepIndex;
                    $isCurrent = $index == $currentStepIndex;
                    $isPending = $index > $currentStepIndex;

                    // Buscar entrada del historial para este paso
                    $entry = $history->where('step_id', $step->id)->last();
                @endphp

                <div class="timeline-item">
                    <div class="timeline-dot {{ $isCurrent ? 'current' : ($isCompleted ? 'completed' : 'pending') }}"
                         style="{{ !$isPending ? 'background-color: ' . ($step->color ?? '#0d6efd') : '' }}">
                        @if($isCompleted)
                            <i class="fa fa-check" style="font-family: inherit;">&#10003;</i>
                        @elseif($isCurrent)
                            <span style="font-size: 10px;">&#9679;</span>
                        @else
                            <span style="font-size: 10px;">&#9675;</span>
                        @endif
                    </div>

                    <div class="timeline-content {{ $isCurrent ? 'current' : '' }} {{ $isPending ? 'pending' : '' }}"
                         style="{{ $isCurrent ? 'border-color: ' . ($step->color ?? '#0d6efd') : '' }}">
                        <div>
                            <span class="step-name">{{ $step->name }}</span>
                            @if($isCurrent)
                                <span class="step-badge" style="background: {{ $step->color ?? '#0d6efd' }}">Actual</span>
                            @elseif($isCompleted)
                                <span class="step-badge" style="background: #28a745">Completado</span>
                            @endif
                        </div>
                        @if($step->description)
                            <div class="step-desc">{{ $step->description }}</div>
                        @endif
                        @if($entry)
                            <div class="step-date">
                                {{ \Carbon\Carbon::parse($entry->created_at)->format('d/m/Y H:i') }}
                            </div>
                            @if($entry->notes)
                                <div class="step-note">{{ $entry->notes }}</div>
                            @endif
                            @if($entry->evidence && $entry->evidence->count() > 0)
                                <div class="evidence-gallery">
                                    @foreach($entry->evidence as $ev)
                                        <img src="{{ asset('storage/' . $ev->image) }}" alt="{{ $ev->caption ?? 'Evidencia' }}" onclick="openLightbox(this.src)">
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="footer">
        Powered by <a href="https://j2biznes.com" target="_blank">J2Biznes</a>
    </div>

    <!-- Lightbox -->
    <div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <img id="lightbox-img" src="" alt="Evidencia">
    </div>
    <script>
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').classList.add('active');
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
        }
    </script>

</body>
</html>
