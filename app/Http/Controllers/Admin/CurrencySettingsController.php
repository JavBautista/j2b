<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CurrencySettingsController extends Controller
{
    public function index()
    {
        $shop = auth()->user()->shop;
        return view('admin.configurations.currency.index', compact('shop'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'currency' => 'required|in:MXN,USD',
            'tax_name' => 'nullable|string|max:20',
            'tax_rate' => 'required|numeric|min:0|max:99.99',
        ]);

        $shop = auth()->user()->shop;
        $shop->currency = $request->currency;
        $shop->tax_name = $request->tax_name ?: null;
        $shop->tax_rate = $request->tax_rate;
        $shop->save();

        return redirect()->route('admin.configurations.currency')
            ->with('success', 'Moneda e impuesto actualizados correctamente.');
    }
}
