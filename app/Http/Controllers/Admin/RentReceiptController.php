<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Rent;
use App\Models\RentDetail;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\PartialPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RentReceiptController extends Controller
{
    /**
     * Obtener rentas activas del cliente con detalle de equipos.
     */
    public function getClientRentas(Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $rentas = Rent::where('client_id', $client->id)
            ->where('active', 1)
            ->withCount('rentDetail')
            ->get();

        return response()->json([
            'ok' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'phone' => $client->phone,
                'email' => $client->email,
            ],
            'rentas' => $rentas,
        ]);
    }

    /**
     * Obtener detalle completo de una renta (equipos con contadores).
     */
    public function getRentDetails(Rent $rent)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $rent->load('rentDetail', 'client');

        if ($rent->client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada'], 404);
        }

        return response()->json([
            'ok' => true,
            'rent' => $rent,
        ]);
    }

    /**
     * Crear recibo de renta (replica la logica de ReceiptController@store para type=renta).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;
        $date_today = Carbon::now();

        $rcp = $request->receipt;

        // Validaciones basicas
        if (empty($rcp['rent_id']) || empty($rcp['client_id'])) {
            return response()->json(['ok' => false, 'message' => 'Datos incompletos'], 422);
        }

        // Verificar que el cliente pertenece a la tienda
        $client = Client::findOrFail($rcp['client_id']);
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no autorizado'], 403);
        }

        // Calcular si esta finalizada
        $finished = ($rcp['received'] >= $rcp['total']) ? 1 : 0;

        // Calcular periodo de renta
        $rnt = Rent::findOrFail($rcp['rent_id']);
        $dia_corte = $rnt->cutoff;

        $_m = ($rcp['rent_mm'] > 9) ? $rcp['rent_mm'] : '0' . $rcp['rent_mm'];
        $_d = ($dia_corte > 9) ? $dia_corte : '0' . $dia_corte;

        $f_ini = $rcp['rent_yy'] . '-' . $_m . '-' . $_d;
        $fecha_corte_ini = Carbon::createFromFormat('Y-m-d', $f_ini);
        $fecha_corte_fin = Carbon::createFromFormat('Y-m-d', $f_ini)->addMonth()->subDay();

        Carbon::setLocale('es');
        $fecha_corte_ini->locale('es');
        $fecha_corte_fin->locale('es');

        $desc1 = $fecha_corte_ini->isoFormat('DD [de] MMMM [del] YYYY');
        $desc2 = $fecha_corte_fin->isoFormat('DD [de] MMMM [del] YYYY');
        $rent_periodo = strtoupper('Periodo del  ' . $desc1 . ' al ' . $desc2);

        // Generar folio
        $ultimo_folio = Receipt::where('shop_id', $shop->id)->max('folio');
        $nuevo_folio = $ultimo_folio ? $ultimo_folio + 1 : 1;

        // Crear receipt
        $receipt = new Receipt();
        $receipt->folio = $nuevo_folio;
        $receipt->shop_id = $shop->id;
        $receipt->client_id = $rcp['client_id'];
        $receipt->created_by = $user->name;
        $receipt->rent_id = $rcp['rent_id'];
        $receipt->type = 'renta';
        $receipt->rent_yy = $rcp['rent_yy'];
        $receipt->rent_mm = $rcp['rent_mm'];
        $receipt->rent_periodo = $rent_periodo;
        $receipt->description = $rcp['description'] ?? '';
        $receipt->observation = $rcp['observation'] ?? '';
        $receipt->discount = $rcp['discount'] ?? 0;
        $receipt->discount_concept = $rcp['discount_concept'] ?? '$';
        $receipt->subtotal = $rcp['subtotal'];
        $receipt->total = $rcp['total'];
        $receipt->iva = $rcp['iva'] ?? 0;
        $receipt->finished = $finished;
        $receipt->status = $rcp['status'];
        $receipt->payment = $rcp['payment'];
        $receipt->received = $rcp['received'] ?? 0;
        $receipt->origin = 'ADMIN';
        $receipt->save();

        // Pago parcial
        if ($receipt->received > 0) {
            $monto_a_registrar = min($receipt->received, $receipt->total);
            $payment_type = ($monto_a_registrar >= $receipt->total) ? 'unico' : 'inicial';

            $partial = new PartialPayments();
            $partial->receipt_id = $receipt->id;
            $partial->amount = $monto_a_registrar;
            $partial->payment_type = $payment_type;
            $partial->payment_date = $date_today;
            $partial->save();
        }

        // Guardar detalle (items del recibo)
        $details = $request->detail;
        foreach ($details as $data) {
            $detail = new ReceiptDetail();
            $detail->receipt_id = $receipt->id;
            $detail->product_id = $data['id'];
            $detail->type = $data['type'];
            $detail->descripcion = $data['name'];
            $detail->qty = $data['qty'];
            $detail->price = $data['cost'];
            $detail->cost = 0;
            $detail->discount = $data['discount'] ?? 0;
            $detail->discount_concept = $data['discount_concept'] ?? '';
            $detail->subtotal = $data['subtotal'];
            $detail->save();
        }

        // Actualizar contadores de equipos
        $eq_new_counts = $request->eq_new_counts;
        if (!empty($eq_new_counts)) {
            foreach ($eq_new_counts as $enc) {
                $rent_equipo = RentDetail::findOrFail($enc['equipo_id']);
                if ($rent_equipo->monochrome) {
                    $rent_equipo->counter_mono = $enc['equipo_new_count_monochrome'];
                }
                if ($rent_equipo->color) {
                    $rent_equipo->counter_color = $enc['equipo_new_count_color'];
                }
                $rent_equipo->save();
            }
        }

        // Retornar recibo completo
        $rr = Receipt::with('partialPayments', 'client', 'shop')
            ->findOrFail($receipt->id);

        return response()->json([
            'ok' => true,
            'receipt' => $rr,
        ]);
    }
}
