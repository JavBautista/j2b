<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Recibir y almacenar un mensaje de contacto
     */
    public function store(Request $request)
    {
        try {
            // Rate limiting: máximo 5 mensajes por IP cada 10 minutos
            $key = 'contact-form:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'success' => false,
                    'message' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos."
                ], 429);
            }

            // Honeypot check
            if ($request->filled('website_url')) {
                // Bot detectado, simular éxito
                Log::warning('Bot detected via honeypot', ['ip' => $request->ip()]);
                return response()->json([
                    'success' => true,
                    'message' => 'Mensaje enviado correctamente.'
                ]);
            }

            // Validación
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:100',
                'email' => 'required|email|max:100',
                'phone' => 'required|string|size:10|regex:/^[0-9]+$/',
                'is_whatsapp' => 'nullable|boolean',
                'company' => 'nullable|string|max:100',
                'message' => 'required|string|min:10|max:1000',
            ], [
                'name.required' => 'El nombre es requerido.',
                'name.min' => 'El nombre debe tener al menos 3 caracteres.',
                'name.max' => 'El nombre no puede exceder 100 caracteres.',
                'email.required' => 'El email es requerido.',
                'email.email' => 'El email no es válido.',
                'phone.required' => 'El teléfono es requerido.',
                'phone.size' => 'El teléfono debe tener 10 dígitos.',
                'phone.regex' => 'El teléfono solo debe contener números.',
                'message.required' => 'El mensaje es requerido.',
                'message.min' => 'El mensaje debe tener al menos 10 caracteres.',
                'message.max' => 'El mensaje no puede exceder 1000 caracteres.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor corrige los errores.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Sanitizar datos
            $data = $this->sanitizeData($request);

            // Verificar contenido malicioso
            $maliciousCheck = $this->checkMaliciousContent($data);
            if ($maliciousCheck['is_malicious']) {
                Log::warning('Malicious content detected', [
                    'ip' => $request->ip(),
                    'reason' => $maliciousCheck['reason'],
                    'data' => $data
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Se detectó contenido no permitido.'
                ], 400);
            }

            // Crear mensaje
            $contactMessage = ContactMessage::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'is_whatsapp' => $request->boolean('is_whatsapp'),
                'company' => $data['company'],
                'message' => $data['message'],
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            ]);

            // Incrementar rate limiter
            RateLimiter::hit($key, 600); // 10 minutos

            Log::info('New contact message received', [
                'id' => $contactMessage->id,
                'email' => $contactMessage->email
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Mensaje enviado! Te contactaremos pronto.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving contact message', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error. Intenta de nuevo más tarde.'
            ], 500);
        }
    }

    /**
     * Sanitizar datos del formulario
     */
    private function sanitizeData(Request $request): array
    {
        return [
            'name' => $this->sanitizeString($request->input('name')),
            'email' => strtolower(trim($request->input('email'))),
            'phone' => preg_replace('/[^0-9]/', '', $request->input('phone')),
            'company' => $this->sanitizeString($request->input('company', '')),
            'message' => $this->sanitizeString($request->input('message')),
        ];
    }

    /**
     * Sanitizar un string
     */
    private function sanitizeString(?string $value): string
    {
        if (!$value) return '';

        // Eliminar tags HTML
        $value = strip_tags($value);

        // Eliminar javascript:
        $value = preg_replace('/javascript\s*:/i', '', $value);

        // Eliminar event handlers
        $value = preg_replace('/on\w+\s*=/i', '', $value);

        // Eliminar caracteres de control (excepto saltos de línea)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // Convertir entidades HTML
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return trim($value);
    }

    /**
     * Verificar contenido malicioso
     */
    private function checkMaliciousContent(array $data): array
    {
        $allText = implode(' ', array_values($data));
        $allTextLower = strtolower($allText);

        // Patrones de SQL Injection
        $sqlPatterns = [
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bunion\b.*\bselect\b)/i',
            '/(--|;|\/\*|\*\/)/',
        ];

        // Patrones de XSS
        $xssPatterns = [
            '/<script/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/expression\s*\(/i',
            '/javascript\s*:/i',
        ];

        // Patrones de inyección de comandos
        $cmdPatterns = [
            '/[;&|`]/',
            '/\.\.\//',
            '/(\/etc\/passwd|\/bin\/sh|cmd\.exe)/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $allText)) {
                return ['is_malicious' => true, 'reason' => 'SQL Injection pattern detected'];
            }
        }

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $allText)) {
                return ['is_malicious' => true, 'reason' => 'XSS pattern detected'];
            }
        }

        foreach ($cmdPatterns as $pattern) {
            if (preg_match($pattern, $allText)) {
                return ['is_malicious' => true, 'reason' => 'Command Injection pattern detected'];
            }
        }

        // Verificar muchos URLs
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/i', $allText);
        if ($urlCount > 5) {
            return ['is_malicious' => true, 'reason' => 'Too many URLs'];
        }

        return ['is_malicious' => false, 'reason' => null];
    }
}
