{{--
    Header bar full-width para páginas del admin.

    Props:
      - title         (string, requerido)   título principal
      - subtitle      (string, opcional)    descripción breve abajo del título (solo en raíz)
      - icon          (string, opcional)    clase FontAwesome (ej: "fa-list-alt"), solo en raíz
      - parentLabel   (string, opcional)    nombre del padre (mini-breadcrumb, ej: "Notas de Venta")
      - parentRoute   (string, opcional)    URL del padre. Si presente, muestra botón "← volver"

    Slots:
      - actions       (opcional)            HTML a la derecha (ej: botones de acción)

    Uso página raíz:
      <x-admin.page-header title="Notas de Venta" icon="fa-list-alt" subtitle="..." />

    Uso página hija:
      <x-admin.page-header
          title="Ver Nota de Venta"
          parent-label="Notas de Venta"
          :parent-route="route('admin.receipts')" />
--}}

@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'parentLabel' => null,
    'parentRoute' => null,
])

<div class="bg-white border-bottom mb-3">
    <div class="container-fluid py-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                @if($parentRoute)
                    <a href="{{ $parentRoute }}"
                       class="btn btn-outline-dark d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                       style="width: 38px; height: 38px;"
                       title="Volver a {{ $parentLabel ?? 'la lista' }}">
                        <i class="fa fa-arrow-left"></i>
                    </a>
                @endif
                <div>
                    @if($parentLabel)
                        <small class="text-muted d-block lh-1">
                            <i class="fa fa-list me-1"></i>{{ $parentLabel }}
                        </small>
                    @endif
                    <h5 class="mb-0 fw-semibold">
                        @if($icon && !$parentLabel)
                            <i class="fa {{ $icon }} me-2 text-secondary"></i>
                        @endif
                        {{ $title }}
                    </h5>
                    @if($subtitle && !$parentLabel)
                        <small class="text-muted">{{ $subtitle }}</small>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="d-flex align-items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</div>
