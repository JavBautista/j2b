<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class GastosController extends Controller
{
    private function getShop()
    {
        return Auth::user()->shop;
    }

    private function logExpenseEvent(Expense $expense, string $action, ?string $description = null)
    {
        $expense->logs()->create([
            'user' => Auth::user()->name ?? null,
            'action' => $action,
            'description' => $description,
        ]);
    }

    public function index()
    {
        $shop = $this->getShop();
        return view('admin.gastos.index', compact('shop'));
    }

    public function getExpenses(Request $request)
    {
        $shop = $this->getShop();

        $buscar = $request->input('buscar', '');
        $filtroStatus = $request->input('filtro_status', 'TODOS');
        $filtroOrdenar = $request->input('filtro_ordenar', 'ID_DESC');
        $filtroActivo = $request->input('filtro_activo', 'TODOS');

        $query = Expense::where('shop_id', $shop->id);

        // Filtro de búsqueda por nombre
        if (!empty($buscar)) {
            $query->where(function($q) use ($buscar) {
                $q->where('name', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%');
            });
        }

        // Filtro por estatus
        if ($filtroStatus !== 'TODOS') {
            $query->where('status', $filtroStatus);
        }

        // Filtro por activo/inactivo
        if ($filtroActivo === 'ACTIVOS') {
            $query->where('active', 1);
        } elseif ($filtroActivo === 'INACTIVOS') {
            $query->where('active', 0);
        }

        // Ordenamiento
        switch ($filtroOrdenar) {
            case 'ID_ASC':
                $query->orderBy('id', 'asc');
                break;
            case 'FECHA_ASC':
                $query->orderBy('date', 'asc');
                break;
            case 'FECHA_DESC':
                $query->orderBy('date', 'desc');
                break;
            case 'TOTAL_ASC':
                $query->orderBy('total', 'asc');
                break;
            case 'TOTAL_DESC':
                $query->orderBy('total', 'desc');
                break;
            case 'ID_DESC':
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        return $query->paginate(12);
    }

    public function getCounters()
    {
        $shop = $this->getShop();

        $total = Expense::where('shop_id', $shop->id)->count();
        $nuevos = Expense::where('shop_id', $shop->id)->where('status', 'NUEVO')->where('active', 1)->count();
        $pagados = Expense::where('shop_id', $shop->id)->where('status', 'PAGADO')->where('active', 1)->count();
        $activos = Expense::where('shop_id', $shop->id)->where('active', 1)->count();
        $inactivos = Expense::where('shop_id', $shop->id)->where('active', 0)->count();

        // Suma de totales
        $sumaNuevos = Expense::where('shop_id', $shop->id)->where('status', 'NUEVO')->where('active', 1)->sum('total');
        $sumaPagados = Expense::where('shop_id', $shop->id)->where('status', 'PAGADO')->where('active', 1)->sum('total');

        return response()->json([
            'total' => $total,
            'nuevos' => $nuevos,
            'pagados' => $pagados,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'suma_nuevos' => $sumaNuevos,
            'suma_pagados' => $sumaPagados
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'total' => 'required|numeric|min:0',
        ]);

        $shop = $this->getShop();
        $date = Carbon::parse($request->date)->format('Y-m-d');

        $expense = new Expense();
        $expense->shop_id = $shop->id;
        $expense->active = 1;
        $expense->status = 'NUEVO';
        $expense->name = $request->name;
        $expense->description = $request->description;
        $expense->date = $date;
        $expense->total = $request->total;
        $expense->is_tax_invoiced = 0;
        $expense->save();

        $this->logExpenseEvent($expense, 'create', 'Gasto creado con total: $' . number_format($expense->total, 2));

        return response()->json([
            'ok' => true,
            'expense' => $expense,
        ]);
    }

    public function update(Request $request, $id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->findOrFail($id);

        $oldName = $expense->name;
        $oldDesc = $expense->description;

        $expense->name = $request->name;
        $expense->description = $request->description;
        $expense->save();

        $this->logExpenseEvent($expense, 'update', "Nombre: {$oldName} -> {$request->name}");

        return response()->json([
            'ok' => true,
            'expense' => $expense,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->findOrFail($id);

        $oldStatus = $expense->status;
        $expense->status = $request->status;
        $expense->save();

        $this->logExpenseEvent($expense, 'update_status', "Status: {$oldStatus} -> {$request->status}");

        return response()->json([
            'ok' => true,
            'expense' => $expense,
        ]);
    }

    public function updateTotal(Request $request, $id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->findOrFail($id);

        $oldTotal = $expense->total;
        $expense->total = $request->total;
        $expense->save();

        $this->logExpenseEvent($expense, 'update_total', "Total: \${$oldTotal} -> \${$request->total}");

        return response()->json([
            'ok' => true,
            'expense' => $expense,
        ]);
    }

    public function updateFecha(Request $request, $id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->findOrFail($id);

        $oldDate = $expense->date;
        $expense->date = Carbon::parse($request->date)->format('Y-m-d');
        $expense->save();

        $this->logExpenseEvent($expense, 'update_fecha', "Fecha: {$oldDate} -> {$expense->date}");

        return response()->json([
            'ok' => true,
            'expense' => $expense,
        ]);
    }

    public function toggleActive(Request $request, $id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->findOrFail($id);

        $expense->active = !$expense->active;
        $expense->save();

        $estado = $expense->active ? 'activado' : 'desactivado';
        $this->logExpenseEvent($expense, $expense->active ? 'activate' : 'deactivate', "Gasto {$estado}");

        return response()->json([
            'ok' => true,
            'expense' => $expense,
        ]);
    }

    public function toggleFacturado(Request $request, $id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->findOrFail($id);

        $expense->is_tax_invoiced = !$expense->is_tax_invoiced;
        $expense->save();

        $estado = $expense->is_tax_invoiced ? 'facturado' : 'no facturado';
        $this->logExpenseEvent($expense, $expense->is_tax_invoiced ? 'facturado' : 'no_facturado', "Marcado como {$estado}");

        return response()->json([
            'ok' => true,
            'expense' => $expense,
        ]);
    }

    public function getLogs($id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->with('logs')->findOrFail($id);

        return response()->json([
            'ok' => true,
            'logs' => $expense->logs()->latest()->get()
        ]);
    }

    public function getAttachments($id)
    {
        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->with('attachments')->findOrFail($id);

        return response()->json([
            'ok' => true,
            'attachments' => $expense->attachments
        ]);
    }

    public function uploadAttachment(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $shop = $this->getShop();
        $expense = Expense::where('shop_id', $shop->id)->findOrFail($id);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('expenses', 'public');

            $attachment = new ExpenseAttachment();
            $attachment->expense_id = $expense->id;
            $attachment->file_path = $filePath;
            $attachment->file_type = $file->getClientMimeType();
            $attachment->save();

            $this->logExpenseEvent($expense, 'upload_attachment', 'Se subió archivo: ' . $file->getClientOriginalName());

            return response()->json([
                'ok' => true,
                'attachment' => $attachment,
            ]);
        }

        return response()->json(['ok' => false, 'error' => 'No se subió ningún archivo.'], 400);
    }

    public function deleteAttachment($id)
    {
        $attachment = ExpenseAttachment::findOrFail($id);
        $expense = $attachment->expense;

        // Verificar que pertenece a la tienda
        $shop = $this->getShop();
        if ($expense->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        // Eliminar archivo físico
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $archivo = basename($attachment->file_path);
        $attachment->delete();

        $this->logExpenseEvent($expense, 'delete_attachment', "Se eliminó archivo: {$archivo}");

        return response()->json(['ok' => true]);
    }
}
