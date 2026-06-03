<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Illuminate\Support\Str;

class DocumentsController extends Controller
{
    /**
     * Vista principal
     */
    public function index()
    {
        return view('superadmin.documents');
    }

    /**
     * Listado de documentos
     */
    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $documents = Document::orderBy('updated_at', 'desc')->get();

        return response()->json([
            'ok' => true,
            'documents' => $documents,
        ]);
    }

    /**
     * Crear documento
     */
    public function store(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'title'    => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'content'  => 'required|string',
            'version'  => 'nullable|string|max:50',
            'notes'    => 'nullable|string',
        ]);

        $document = Document::create([
            'title'     => $request->title,
            'category'  => $request->category,
            'content'   => $request->content,
            'version'   => $request->version ?: '1.0',
            'notes'     => $request->notes,
            'is_active' => true,
        ]);

        return response()->json([
            'ok'       => true,
            'message'  => 'Documento creado correctamente.',
            'document' => $document,
        ]);
    }

    /**
     * Actualizar documento
     */
    public function update(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'id'       => 'required|exists:documents,id',
            'title'    => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'content'  => 'required|string',
            'version'  => 'nullable|string|max:50',
            'notes'    => 'nullable|string',
        ]);

        $document = Document::findOrFail($request->id);
        $document->title    = $request->title;
        $document->category = $request->category;
        $document->content  = $request->content;
        $document->version  = $request->version ?: $document->version;
        $document->notes    = $request->notes;
        $document->save();

        return response()->json([
            'ok'       => true,
            'message'  => 'Documento actualizado correctamente.',
            'document' => $document,
        ]);
    }

    /**
     * Eliminar documento
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $document = Document::findOrFail($id);
        $document->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Documento eliminado correctamente.',
        ]);
    }

    /**
     * Generar PDF del documento.
     * ?download=1 -> descarga; sin parametro -> abre en el navegador (stream).
     */
    public function pdf(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Markdown (GitHub Flavored, soporta tablas) -> HTML
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input'         => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $html = (string) $converter->convert($document->content);

        $pdf = Pdf::loadView('superadmin.documents_pdf', [
            'document' => $document,
            'html'     => $html,
        ])->setPaper('letter');

        $filename = Str::slug($document->title ?: 'documento') . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename, ['Attachment' => false]);
    }
}
