<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;

class LegalPageController extends Controller
{
    public function terms()
    {
        $document = LegalDocument::getActiveTerms();

        return view('web.legal', [
            'document' => $document,
            'type' => 'terms',
        ]);
    }

    public function privacy()
    {
        $document = LegalDocument::getActivePrivacy();

        return view('web.legal', [
            'document' => $document,
            'type' => 'privacy',
        ]);
    }
}
