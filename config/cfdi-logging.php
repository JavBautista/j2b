<?php

/*
|------------------------------------------------------------------------------
| Configuración de logs estructurados de facturación CFDI
|------------------------------------------------------------------------------
| Plan: xdev/facturacion/PLAN_LOGS_FACTURACION.md
| Helper: app/Services/Facturacion/Logging/LogFacturacion.php
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Sensitive keys (sanitización)
    |--------------------------------------------------------------------------
    | Cualquier clave en cualquier nivel del payload cuyo nombre contenga
    | alguno de estos substrings (case-insensitive) se reemplaza por
    | "[REDACTED]" antes de escribir a archivo o BD.
    */
    'sensitive_keys' => [
        'password', 'pwd', 'pass',
        'csd_password', 'csd_pass', 'key_password',
        'api_token', 'token', 'bearer',
        'authorization', 'x-api-key', 'apikey',
        'sello', 'certificado', 'private_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persisted events
    |--------------------------------------------------------------------------
    | Solo estos eventos se insertan en cfdi_timbrado_logs (además de archivo).
    | El resto va solo a archivo. Esto evita inflar la tabla con eventos
    | informativos de bajo valor.
    */
    'persisted_events' => [
        // Timbrado
        'cfdi.timbrado.attempt',
        'cfdi.timbrado.success',
        'cfdi.timbrado.error',
        // Cancelación
        'cfdi.cancelacion.attempt',
        'cfdi.cancelacion.success',
        'cfdi.cancelacion.error',
        // Complementos PPD
        'cfdi.complemento_pago.attempt',
        'cfdi.complemento_pago.success',
        'cfdi.complemento_pago.error',
        // Errores HTTP del hub TBT (cualquier endpoint)
        'cfdi.hub.api_error',
        // Pipeline XML implocal (cuando se implemente)
        'cfdi.implocal.attempt',
        'cfdi.implocal.success',
        'cfdi.implocal.error',
    ],

    /*
    |--------------------------------------------------------------------------
    | Compresión de payloads grandes
    |--------------------------------------------------------------------------
    | Payloads request/response que excedan este tamaño en bytes se guardan
    | con gzip + base64 (prefijo "gzip:") en la columna correspondiente.
    | El helper transparenta la descompresión al leer.
    */
    'compression_threshold_bytes' => 32 * 1024, // 32 KB

    /*
    |--------------------------------------------------------------------------
    | Retención
    |--------------------------------------------------------------------------
    | Días que se conservan los rows de cfdi_timbrado_logs antes de ser
    | purgados por el comando artisan cfdi:purge-logs (Sesión 6).
    */
    'retention_days' => (int) env('CFDI_LOGS_RETENTION_DAYS', 90),

];
