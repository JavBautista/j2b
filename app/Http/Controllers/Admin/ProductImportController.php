<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductImportController extends Controller
{
    /**
     * Página de importación de productos
     */
    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;

        return view('admin.products.import', [
            'shop' => $shop
        ]);
    }

    /**
     * Obtener categorías para el mapeo
     */
    public function getCategories()
    {
        $user = auth()->user();
        $shop = $user->shop;

        $categories = Category::where('shop_id', $shop->id)
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'ok' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Descargar plantilla Excel
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        $headers = [
            'Nombre *', 'Codigo', 'Codigo Barras', 'Descripcion',
            'Costo *', 'Precio Nv1 *', 'Precio Nv2', 'Precio Nv3',
            'Stock', 'Reserva'
        ];

        $columns = range('A', 'J');
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($columns[$col] . '1', $header);
        }

        // Estilo encabezados: fondo azul, texto blanco, negrita
        $headerRange = 'A1:J1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');

        // Auto-ajustar ancho de columnas
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'plantilla_productos_' . date('Y-m-d') . '.xlsx';
        $temp = tempnam(sys_get_temp_dir(), 'productos_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp);

        return response()->download($temp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview del archivo antes de importar
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'category_id' => 'required|exists:categories,id'
        ]);

        try {
            $user = auth()->user();
            $shop = $user->shop;

            // Validar que la categoría pertenezca a la tienda
            $selectedCategory = Category::where('shop_id', $shop->id)
                ->where('id', $request->category_id)
                ->first();

            if (!$selectedCategory) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Categoría no válida'
                ], 422);
            }

            // Leer el archivo
            $file = $request->file('file');
            $data = Excel::toArray([], $file);

            if (empty($data) || empty($data[0])) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El archivo está vacío'
                ], 422);
            }

            $rows = $data[0];
            $headers = array_shift($rows); // Primera fila = encabezados

            $preview = [];
            $errorsCount = 0;

            foreach ($rows as $index => $row) {
                // Ignorar filas vacías
                if (empty(array_filter($row))) continue;

                $rowData = [
                    'row_number' => $index + 2, // +2 porque índice 0 y encabezado
                    'name' => trim($row[0] ?? ''),
                    'key' => trim($row[1] ?? ''),
                    'barcode' => trim($row[2] ?? ''),
                    'description' => trim($row[3] ?? ''),
                    'cost' => $this->parseNumber($row[4] ?? 0),
                    'retail' => $this->parseNumber($row[5] ?? 0),
                    'wholesale' => $this->parseNumber($row[6] ?? 0),
                    'wholesale_premium' => $this->parseNumber($row[7] ?? 0),
                    'stock' => intval($row[8] ?? 0),
                    'reserve' => intval($row[9] ?? 0),
                    // Usar la categoría seleccionada por el usuario
                    'category_id' => $selectedCategory->id,
                    'category_name' => $selectedCategory->name,
                    'errors' => [],
                    'valid' => true
                ];

                // Validaciones
                if (empty($rowData['name'])) {
                    $rowData['errors'][] = 'Nombre es obligatorio';
                    $rowData['valid'] = false;
                }
                if ($rowData['cost'] <= 0) {
                    $rowData['errors'][] = 'Costo debe ser mayor a 0';
                    $rowData['valid'] = false;
                }
                if ($rowData['retail'] <= 0) {
                    $rowData['errors'][] = 'Precio Nv1 debe ser mayor a 0';
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

    /**
     * Ejecutar la importación
     */
    public function import(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string',
            'products.*.cost' => 'required|numeric|min:0',
            'products.*.retail' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id'
        ]);

        try {
            $user = auth()->user();
            $shop = $user->shop;

            // Validar que la categoría pertenezca a la tienda
            $categoryId = $request->category_id;
            $category = Category::where('shop_id', $shop->id)
                ->where('id', $categoryId)
                ->first();

            if (!$category) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Categoría no válida'
                ], 422);
            }

            $products = $request->products;
            $created = 0;
            $errors = [];

            foreach ($products as $index => $productData) {
                try {
                    // Solo importar los válidos
                    if (isset($productData['valid']) && !$productData['valid']) {
                        continue;
                    }

                    $product = new Product();
                    $product->shop_id = $shop->id;
                    $product->active = 1;
                    $product->name = $productData['name'];
                    $product->key = $productData['key'] ?? null;
                    $product->barcode = $productData['barcode'] ?? null;
                    $product->description = $productData['description'] ?? null;
                    $product->category_id = $categoryId;
                    $product->cost = $productData['cost'];
                    $product->retail = $productData['retail'];
                    $product->wholesale = $productData['wholesale'] ?? 0;
                    $product->wholesale_premium = $productData['wholesale_premium'] ?? 0;
                    $product->stock = $productData['stock'] ?? 0;
                    $product->reserve = $productData['reserve'] ?? 0;
                    $product->save();

                    $created++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $productData['row_number'] ?? ($index + 1),
                        'name' => $productData['name'] ?? 'Desconocido',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'ok' => true,
                'message' => "Importación completada: $created productos creados.",
                'created' => $created,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error en la importación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parsear número (maneja comas y puntos)
     */
    private function parseNumber($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        // Remover caracteres no numéricos excepto punto y coma
        $value = preg_replace('/[^\d.,\-]/', '', $value);
        // Si tiene coma como decimal (formato europeo)
        if (preg_match('/,\d{1,2}$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
        return floatval($value);
    }
}
