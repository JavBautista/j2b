<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Expense;
use App\Models\ExpenseAttachment;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class ExpenseController extends Controller
{
    private function logExpenseEvent(Expense $expense, Request $request, string $action, ?string $description = null)
    {
        $expense->logs()->create([
            'user' => $request->user()->name ?? null,
            'action' => $action,
            'description' => $description,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->input('buscar', '');
        $filtroStatus = $request->input('filtro_status', 'TODOS');
        $filtroOrdenar = $request->input('filtro_ordenar', 'ID_DESC');

        $query = Expense::where('shop_id', $shop->id);

        // Filtro de búsqueda por nombre
        if (!empty($buscar)) {
            $query->where('name', 'like', '%' . $buscar . '%');
        }

        // Filtro por estatus
        if ($filtroStatus !== 'TODOS') {
            $query->where('status', $filtroStatus);
        }

        // Ordenamiento
        switch ($filtroOrdenar) {
            case 'ID_ASC':
                $query->orderBy('id', 'asc');
                break;
            case 'ID_DESC':
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        // Solo gastos activos
        $query->where('active', 1);

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'total' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $shop = $user->shop;

        $date = Carbon::parse($request->date)->format('Y-m-d');

        $expense= new Expense();
        $expense->shop_id = $shop->id;
        $expense->active  = 1;
        $expense->status  = $request->status;
        $expense->name    = $request->name;
        $expense->description = $request->description;
        $expense->date    = $date;
        $expense->total   = $request->total;
        $expense->is_tax_invoiced = 0;
        $expense->save();

        $this->logExpenseEvent($expense, $request, 'create', 'Gasto creado con total: '.$expense->total);


        return response()->json([
            'ok'=>true,
            'expense' => $expense,
        ]);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        
        $oldName=$expense->name;
        $oldDesc=$expense->description;

        $expense->name    = $request->name;
        $expense->description = $request->description;

        $expense->save();

        $log_desc="Nombre: {$oldName} -> {$request->name} | Descripción: {$oldDesc} -> {$request->description}";

        $this->logExpenseEvent($expense, $request,  'update', $log_desc);

        return response()->json([
            'ok'=>true,
            'expense' => $expense,
        ]);
    }

    public function active(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->active = 1;
        $expense->save();

        $this->logExpenseEvent($expense, $request, 'active', "Se actualizó a activo");

        return response()->json([
            'ok'=>true
        ]);
    }

    public function inactive(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->active = 0;
        $expense->save();

        $this->logExpenseEvent($expense, $request, 'inactive', "Se actualizó a inactivo");

        return response()->json([
            'ok'=>true
        ]);
    }

    public function facturado(Request $request,$id)
    {
        $expense = Expense::findOrFail($id);
        $expense->is_tax_invoiced = 1;
        $expense->save();
        $this->logExpenseEvent($expense, $request, 'facturado', "Se actualizó a facturado");
        return response()->json([
            'ok'=>true
        ]);
    }

    public function noFacturado(Request $request,$id)
    {
        $expense = Expense::findOrFail($id);
        $expense->is_tax_invoiced = 0;
        $expense->save();

        $this->logExpenseEvent($expense, $request, 'no_facturado', "Se actualizó a no facturado");

        return response()->json([
            'ok'=>true
        ]);
    }

    public function updateStatus(Request $request,$id)
    {
        $expense = Expense::findOrFail($id);
        $oldStatus = $expense->status;
        $expense->status  = $request->status;
        $expense->save();

        $this->logExpenseEvent($expense, $request, 'update_status', "Se actualizó el estatus de $oldStatus a {$request->status}");
        return response()->json([
            'ok'=>true
        ]);
    }

    public function updateFecha(Request $request,$id)
    {
        $date = Carbon::parse($request->date)->format('Y-m-d');

        $expense = Expense::findOrFail($id);
        $oldDate = $expense->date;
        $expense->date  = $date;
        $expense->save();

        $this->logExpenseEvent($expense, $request, 'update_fecha', "Se actualizó la fecha de $oldDate a {$request->date}");

        return response()->json([
            'ok'=>true
        ]);
    }

    public function updateTotal(Request $request,$id)
    {
        $expense = Expense::findOrFail($id);
        $oldTotal = $expense->total;
        $expense->total  = $request->total;
        $expense->save();

        $this->logExpenseEvent($expense, $request, 'update_total', "Se actualizó el total de $oldTotal a {$request->total}");


        return response()->json([
            'ok'=>true
        ]);
    }

    public function logs(Request $request, $id)
    {
        $expense = Expense::with('logs')->findOrFail($id);
        return response()->json([
            'ok' => true,
            'logs' => $expense->logs()->latest()->get()
        ]);
    }

    public function getAttachments($id)
    {
        $expense = Expense::with('attachments')->findOrFail($id);

        return response()->json([
            'ok' => true,
            'attachments' => $expense->attachments
        ]);
    }

    public function uploadAttachment(Request $request,$id)
    {
        $request->validate([
            'file' => 'required|file|max:5120', // máx 5MB
        ]);

        $expense = Expense::findOrFail($id);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Guarda en storage/app/public/expenses
            $filePath = $file->store('expenses', 'public');

            $attachment = new ExpenseAttachment();
            $attachment->expense_id = $expense->id;
            $attachment->file_path = $filePath;
            $attachment->file_type = $file->getClientMimeType();
            $attachment->save();

            // Log
            $filename = Str::limit($file->getClientOriginalName(), 60);
            $this->logExpenseEvent($expense, $request, 'upload_attachment', 'Se subió un archivo: ' . $filename );

            return response()->json([
                'ok' => true,
                'attachment' => $attachment,
            ]);
        }

        return response()->json(['ok' => false, 'error' => 'No se subió ningún archivo.'], 400);
    }



    public function deleteAttachment(Request $request,$id)
    {
    
        $attachment = ExpenseAttachment::findOrFail($id);
        $expense = $attachment->expense;

        // Elimina del disco
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Guarda nombre del archivo para log
        $archivo = basename($attachment->file_path);

        // Borra de la base de datos
        $attachment->delete();

        // Log
        $this->logExpenseEvent($expense, $request, 'delete_attachment', "Se eliminó el archivo: $archivo");

        return response()->json(['ok' => true]);
    }


    public function uploadAttachmentImage(Request $request,$id)
    {
        $request->validate([
            'file' => 'required|file|max:5120', // máx 5MB
        ]);

        $expense = Expense::findOrFail($id);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Guarda en storage/app/public/expenses
            $filePath = $file->store('expenses', 'public');

            $attachment = new ExpenseAttachment();
            $attachment->expense_id = $expense->id;
            $attachment->file_path = $filePath;
            $attachment->file_type = $file->getClientMimeType();
            $attachment->save();

            // Log
            $filename = Str::limit($file->getClientOriginalName(), 60);
            $this->logExpenseEvent($expense, $request, 'upload_attachment', 'Se subió un archivo: ' . $filename );

            return response()->json([
                'ok' => true,
                'attachment' => $attachment,
            ]);
        }

        return response()->json(['ok' => false, 'error' => 'No se subió ningún archivo.'], 400);
    }


    public function uploadImageExpense(Request $request, $id){
        //Log::info('Request all:', $request->all());
        //Log::info('FILES:', $_FILES);
        
        $user = $request->user();
        
        $expense = Expense::findOrFail($id);
        
        // Validar la existencia del archivo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            // Guardar la imagen en la ubicación 'public'
            $imagePath = $image->store('expenses', 'public');
            
            $expenseImage = new ExpenseAttachment();        
            $expenseImage->expense_id = $expense->id;
            $expenseImage->file_path =  $imagePath;
            $expenseImage->file_type =  $image->getClientMimeType();
            $expenseImage->save();            

            $this->logExpenseEvent($expense, $request, 'upload_attachment', 'Se subió imagen.');
            
        }

        return response()->json([
            'ok'=>true,
            'expense' => $expense,
        ]);
    }



    public function deleteImageExpense(Request $request,$id)
    {
    
        $attachment = ExpenseAttachment::findOrFail($id);
        $expense = $attachment->expense;

        // Elimina del disco
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Guarda nombre del archivo para log
        $archivo = basename($attachment->file_path);

        // Borra de la base de datos
        $attachment->delete();

        // Log
        $this->logExpenseEvent($expense, $request, 'delete_attachment', "Se eliminó el archivo: $archivo");

        return response()->json(['ok' => true]);
    }


}



