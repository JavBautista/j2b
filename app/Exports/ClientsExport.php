<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $shop_id;

    public function __construct($shop_id)
    {
        $this->shop_id = $shop_id;
    }

    public function collection()
    {
        return Client::where('shop_id', $this->shop_id)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Activo',
            'Nombre',
            'Empresa',
            'Email',
            'Teléfono',
            'Móvil',
            'Dirección',
            'Número Exterior',
            'Número Interior',
            'Colonia',
            'Ciudad',
            'Estado',
            'Código Postal',
            'Referencia',
            'Detalle',
            'Observaciones',
            'Nivel',
            'Origen',
            'Latitud',
            'Longitud',
            'Fecha Creación',
            'Fecha Actualización'
        ];
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->active ? 'Sí' : 'No',
            $client->name ?? '',
            $client->company ?? '',
            $client->email ?? '',
            $client->phone ?? '',
            $client->movil ?? '',
            $client->address ?? '',
            $client->number_out ?? '',
            $client->number_int ?? '',
            $client->district ?? '',
            $client->city ?? '',
            $client->state ?? '',
            $client->zip_code ?? '',
            $client->reference ?? '',
            $client->detail ?? '',
            $client->observations ?? '',
            $client->level ?? '',
            $client->origin ?? '',
            $client->location_latitude ?? '',
            $client->location_longitude ?? '',
            $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : '',
            $client->updated_at ? $client->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
