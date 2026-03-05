<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ClientImportController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;

        return view('admin.clients.import', [
            'shop' => $shop
        ]);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clientes');

        $headers = [
            'Nombre *', 'Empresa', 'Email', 'Telefono Movil', 'Telefono Fijo',
            'Direccion', 'Colonia', 'Ciudad', 'Estado', 'CP',
            'Nivel (1=Basico, 2=Premium, 3=VIP)', 'Observaciones'
        ];

        // Escribir encabezados con estilo
        $columns = range('A', 'L');
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($columns[$col] . '1', $header);
        }

        // Estilo encabezados: fondo azul, texto blanco, negrita
        $headerRange = 'A1:L1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');

        // Auto-ajustar ancho de columnas
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'plantilla_clientes_' . date('Y-m-d') . '.xlsx';
        $temp = tempnam(sys_get_temp_dir(), 'clientes_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp);

        return response()->download($temp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray([], $file);

            if (empty($data) || empty($data[0])) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El archivo esta vacio'
                ], 422);
            }

            $rows = $data[0];
            $headers = array_shift($rows);

            $preview = [];
            $errorsCount = 0;

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;

                $level = intval($row[10] ?? 1);
                if ($level < 1 || $level > 3) $level = 1;

                $rowData = [
                    'row_number' => $index + 2,
                    'name' => trim($row[0] ?? ''),
                    'company' => trim($row[1] ?? ''),
                    'email' => trim($row[2] ?? ''),
                    'movil' => trim($row[3] ?? ''),
                    'phone' => trim($row[4] ?? ''),
                    'address' => trim($row[5] ?? ''),
                    'district' => trim($row[6] ?? ''),
                    'city' => trim($row[7] ?? ''),
                    'state' => trim($row[8] ?? ''),
                    'zip_code' => trim($row[9] ?? ''),
                    'level' => $level,
                    'observations' => trim($row[11] ?? ''),
                    'errors' => [],
                    'valid' => true
                ];

                if (empty($rowData['name'])) {
                    $rowData['errors'][] = 'Nombre es obligatorio';
                    $rowData['valid'] = false;
                }

                if (!empty($rowData['email']) && !filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
                    $rowData['errors'][] = 'Email no tiene formato valido';
                    $rowData['valid'] = false;
                }

                if (!$rowData['valid']) {
                    $errorsCount++;
                }

                $preview[] = $rowData;
            }

            return response()->json([
                'ok' => true,
                'preview' => $preview,
                'total' => count($preview),
                'valid' => count($preview) - $errorsCount,
                'errors' => $errorsCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al procesar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'clients' => 'required|array|min:1',
            'clients.*.name' => 'required|string',
        ]);

        try {
            $user = auth()->user();
            $shop = $user->shop;

            $clients = $request->clients;
            $created = 0;
            $errors = [];

            foreach ($clients as $index => $clientData) {
                try {
                    if (isset($clientData['valid']) && !$clientData['valid']) {
                        continue;
                    }

                    $client = new Client();
                    $client->shop_id = $shop->id;
                    $client->active = 1;
                    $client->name = $clientData['name'];
                    $client->company = $clientData['company'] ?? null;
                    $client->email = $clientData['email'] ?? null;
                    $client->movil = $clientData['movil'] ?? null;
                    $client->phone = $clientData['phone'] ?? null;
                    $client->address = $clientData['address'] ?? null;
                    $client->district = $clientData['district'] ?? null;
                    $client->city = $clientData['city'] ?? null;
                    $client->state = $clientData['state'] ?? null;
                    $client->zip_code = $clientData['zip_code'] ?? null;
                    $client->level = $clientData['level'] ?? 1;
                    $client->observations = $clientData['observations'] ?? null;
                    $client->save();

                    $created++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $clientData['row_number'] ?? ($index + 1),
                        'name' => $clientData['name'] ?? 'Desconocido',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'ok' => true,
                'message' => "Importacion completada: $created clientes creados.",
                'created' => $created,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error en la importacion: ' . $e->getMessage()
            ], 500);
        }
    }
}
