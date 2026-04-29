<?php

namespace App\Http\Controllers;

use App\Models\Rent;
use App\Models\RentConsignment;
use App\Services\Rentas\RentConsignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RentConsignmentController extends Controller
{
    public function index(Request $request, $rentId)
    {
        $shop = Auth::user()->shop;
        $rent = Rent::where('id', $rentId)->where('shop_id', $shop->id)->first();
        if (!$rent) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada.'], 404);
        }

        $consignments = RentConsignment::where('rent_id', $rent->id)
            ->where('shop_id', $shop->id)
            ->with(['items.product:id,name,retail', 'createdBy:id,name'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'consignments' => $consignments,
        ]);
    }

    public function store(Request $request, $rentId)
    {
        $request->validate([
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'received_by_name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.description' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        if ($user->limited) {
            return response()->json(['ok' => false, 'message' => 'Acceso restringido. Esta acción requiere admin completo.'], 403);
        }

        $shop = $user->shop;
        $rent = Rent::where('id', $rentId)->where('shop_id', $shop->id)->first();
        if (!$rent) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada.'], 404);
        }

        $service = new RentConsignmentService();
        $result = $service->crear($rent, $request->input('items'), [
            'delivery_date' => $request->input('delivery_date'),
            'notes' => $request->input('notes'),
            'received_by_name' => $request->input('received_by_name'),
            'created_by_user_id' => $user->id,
        ]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function show($id)
    {
        $shop = Auth::user()->shop;
        $cons = RentConsignment::where('id', $id)->where('shop_id', $shop->id)
            ->with(['items.product:id,name,retail', 'rent.client:id,name', 'createdBy:id,name'])
            ->first();
        if (!$cons) {
            return response()->json(['ok' => false, 'message' => 'Consigna no encontrada.'], 404);
        }

        return response()->json(['ok' => true, 'consignment' => $cons]);
    }

    public function cancelar(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:1000',
            'returns' => 'nullable|array',
            'returns.*' => 'integer|min:0',
        ]);

        $user = Auth::user();
        if ($user->limited) {
            return response()->json(['ok' => false, 'message' => 'Acceso restringido. Esta acción requiere admin completo.'], 403);
        }

        $shop = $user->shop;
        $cons = RentConsignment::where('id', $id)->where('shop_id', $shop->id)->first();
        if (!$cons) {
            return response()->json(['ok' => false, 'message' => 'Consigna no encontrada.'], 404);
        }

        $service = new RentConsignmentService();
        $result = $service->cancelar(
            $cons,
            $request->input('returns', []),
            $request->input('cancellation_reason')
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function descargarPdf($id)
    {
        $shop = Auth::user()->shop;
        $cons = RentConsignment::where('id', $id)->where('shop_id', $shop->id)->first();
        if (!$cons) {
            return response()->json(['ok' => false, 'message' => 'Consigna no encontrada.'], 404);
        }

        $service = new RentConsignmentService();

        if ($cons->pdf_path && Storage::disk('consignments')->exists($cons->pdf_path)) {
            $content = Storage::disk('consignments')->get($cons->pdf_path);
        } else {
            try {
                $path = $service->generarYGuardarPdf($cons);
                $cons->pdf_path = $path;
                $cons->save();
                $content = Storage::disk('consignments')->get($path);
            } catch (\Throwable $e) {
                return response()->json(['ok' => false, 'message' => 'Error al generar PDF: ' . $e->getMessage()], 500);
            }
        }

        $filename = "consigna_{$cons->folioCompleto()}.pdf";
        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function subirFirma(Request $request, $id)
    {
        $request->validate([
            'foto' => 'required|file|mimes:jpg,jpeg,png|max:10240',
        ]);

        $user = Auth::user();
        if ($user->limited) {
            return response()->json(['ok' => false, 'message' => 'Acceso restringido. Esta acción requiere admin completo.'], 403);
        }

        $shop = $user->shop;
        $cons = RentConsignment::where('id', $id)->where('shop_id', $shop->id)->first();
        if (!$cons) {
            return response()->json(['ok' => false, 'message' => 'Consigna no encontrada.'], 404);
        }

        $service = new RentConsignmentService();
        $result = $service->subirFirma($cons, $request->file('foto'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function verFirma($id)
    {
        $shop = Auth::user()->shop;
        $cons = RentConsignment::where('id', $id)->where('shop_id', $shop->id)->first();
        if (!$cons || !$cons->signature_path) {
            abort(404);
        }
        if (!Storage::disk('consignments')->exists($cons->signature_path)) {
            abort(404);
        }

        $content = Storage::disk('consignments')->get($cons->signature_path);
        $ext = pathinfo($cons->signature_path, PATHINFO_EXTENSION);
        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

        return response($content)->header('Content-Type', $mime);
    }
}
