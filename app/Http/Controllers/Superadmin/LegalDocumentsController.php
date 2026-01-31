<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LegalDocument;

class LegalDocumentsController extends Controller
{
    /**
     * Vista principal
     */
    public function index()
    {
        return view('superadmin.legal-documents');
    }

    /**
     * Obtener documentos legales
     */
    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $documents = LegalDocument::orderBy('type', 'asc')->get();

        return response()->json([
            'ok' => true,
            'documents' => $documents
        ]);
    }

    /**
     * Crear o actualizar documento
     */
    public function store(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'type' => 'required|in:terms,privacy',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'version' => 'nullable|string|max:50',
            'effective_date' => 'nullable|date',
        ]);

        // Verificar si ya existe un documento de este tipo
        $existing = LegalDocument::where('type', $request->type)->first();

        if ($existing) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe un documento de este tipo. Use la opción de editar.'
            ], 400);
        }

        $document = LegalDocument::create([
            'type' => $request->type,
            'title' => $request->title,
            'content' => $request->content,
            'version' => $request->version ?? '1.0',
            'effective_date' => $request->effective_date,
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Documento creado correctamente.',
            'document' => $document
        ]);
    }

    /**
     * Actualizar documento existente
     */
    public function update(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'id' => 'required|exists:legal_documents,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'version' => 'nullable|string|max:50',
            'effective_date' => 'nullable|date',
        ]);

        $document = LegalDocument::findOrFail($request->id);
        $document->title = $request->title;
        $document->content = $request->content;
        $document->version = $request->version ?? $document->version;
        $document->effective_date = $request->effective_date;
        $document->save();

        return response()->json([
            'ok' => true,
            'message' => 'Documento actualizado correctamente.',
            'document' => $document
        ]);
    }

    /**
     * Eliminar documento
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $document = LegalDocument::findOrFail($id);
        $document->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Documento eliminado correctamente.'
        ]);
    }
}
