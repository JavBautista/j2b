<?php

namespace App\Services\Pagos;

use App\Models\Receipt;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Resuelve y valida la fecha real de un pago/abono para que el complemento de pago
 * CFDI (PPD) emita el FechaPago correcto (la fecha en que se recibió el dinero, no la
 * fecha de captura en el sistema).
 *
 * Reglas (confirmadas con el usuario 2026-05-30):
 *  - Si no se captura fecha → ahora (zona horaria México).
 *  - Hora: si la fecha es hoy → hora actual; si es fecha pasada (solo Y-m-d) → 12:00:00
 *    (evita corrimientos por zona horaria al formatear Y-m-d\TH:i:s en el XML).
 *  - No puede ser futura.
 *  - No puede ser anterior a la emisión de la factura CFDI vigente (regla SAT: el pago
 *    no puede registrarse antes de timbrar el comprobante).
 */
class PaymentDateResolver
{
    const TZ = 'America/Mexico_City';

    /**
     * @throws ValidationException
     */
    public static function resolver(?string $inputDate, Receipt $receipt): Carbon
    {
        $now = Carbon::now(self::TZ);

        if (empty($inputDate)) {
            return $now;
        }

        $fecha = Carbon::parse($inputDate, self::TZ);

        // ¿El input traía hora explícita? Si parse dio medianoche asumimos "solo fecha".
        $soloFecha = $fecha->format('H:i:s') === '00:00:00';

        if ($fecha->isSameDay($now)) {
            // Pago de hoy → hora real del registro.
            $fecha = $now->copy();
        } elseif ($soloFecha) {
            // Fecha pasada sin hora → mediodía para estabilidad de zona horaria.
            $fecha->setTime(12, 0, 0);
        }

        // No futura.
        if ($fecha->gt($now)) {
            throw ValidationException::withMessages([
                'payment_date' => 'La fecha de pago no puede ser futura.',
            ]);
        }

        // No anterior a la emisión de la factura CFDI vigente.
        $cfdi = $receipt->cfdiInvoice;
        if ($cfdi) {
            $emision = $cfdi->fecha_emision ?? $cfdi->fecha_timbrado;
            if ($emision) {
                $emision = Carbon::parse($emision, self::TZ);
                if ($fecha->lt($emision->copy()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'payment_date' => 'La fecha de pago no puede ser anterior a la emisión de la factura ('
                            . $emision->format('d/m/Y') . ').',
                    ]);
                }
            }
        }

        return $fecha;
    }
}
