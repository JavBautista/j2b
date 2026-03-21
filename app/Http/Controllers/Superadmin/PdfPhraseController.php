<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PdfPhrase;

class PdfPhraseController extends Controller
{
    public function index()
    {
        return view('superadmin.pdf-phrases');
    }

    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $phrases = PdfPhrase::orderBy('created_at', 'desc')->get();

        return response()->json([
            'ok' => true,
            'phrases' => $phrases
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'phrase' => 'required|string|max:500',
            'link_url' => 'nullable|string|max:500',
        ]);

        $phrase = PdfPhrase::create([
            'phrase' => $request->phrase,
            'link_url' => $request->link_url ?: 'https://j2biznes.com',
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Frase creada correctamente.',
            'phrase' => $phrase
        ]);
    }

    public function bulkImport(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'phrases' => 'required|string',
            'link_url' => 'nullable|string|max:500',
        ]);

        $lines = array_filter(
            array_map('trim', explode("\n", $request->phrases)),
            fn($line) => $line !== ''
        );

        if (empty($lines)) {
            return response()->json(['ok' => false, 'message' => 'No se encontraron frases.'], 400);
        }

        $linkUrl = $request->link_url ?: 'https://j2biznes.com';
        $count = 0;

        foreach ($lines as $line) {
            PdfPhrase::create([
                'phrase' => mb_substr($line, 0, 500),
                'link_url' => $linkUrl,
                'is_active' => true,
            ]);
            $count++;
        }

        return response()->json([
            'ok' => true,
            'message' => "Se importaron {$count} frases correctamente.",
        ]);
    }

    public function update(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'id' => 'required|exists:pdf_phrases,id',
            'phrase' => 'required|string|max:500',
            'link_url' => 'nullable|string|max:500',
        ]);

        $phrase = PdfPhrase::findOrFail($request->id);
        $phrase->phrase = $request->phrase;
        $phrase->link_url = $request->link_url ?: 'https://j2biznes.com';
        $phrase->save();

        return response()->json([
            'ok' => true,
            'message' => 'Frase actualizada correctamente.',
            'phrase' => $phrase
        ]);
    }

    public function toggleActive(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'id' => 'required|exists:pdf_phrases,id',
        ]);

        $phrase = PdfPhrase::findOrFail($request->id);
        $phrase->is_active = !$phrase->is_active;
        $phrase->save();

        return response()->json([
            'ok' => true,
            'message' => $phrase->is_active ? 'Frase activada.' : 'Frase desactivada.',
            'phrase' => $phrase
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $phrase = PdfPhrase::findOrFail($id);
        $phrase->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Frase eliminada correctamente.'
        ]);
    }
}
