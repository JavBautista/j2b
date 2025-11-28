<?php

return [
    'api_key' => env('GROQ_API_KEY'),
    'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    'timeout' => env('GROQ_TIMEOUT', 30),
    'max_tokens' => env('GROQ_MAX_TOKENS', 2000),
    'temperature' => env('GROQ_TEMPERATURE', 0.7),

    // Configuración de retry
    'retry' => [
        'times' => 3,
        'sleep' => 1000, // milliseconds
    ],

    // Modelos disponibles (FREE)
    'available_models' => [
        // Más rápido para producción (14.4K RPD, 500K TPD)
        'llama-3.1-8b-instant' => [
            'name' => 'Meta Llama 3.1 8B Instant',
            'description' => 'MÁS RÁPIDO - Ideal para uso intensivo',
            'limits' => ['rpm' => 30, 'rpd' => 14400, 'tpm' => 6000, 'tpd' => 500000],
        ],
        // Balanceado (1K RPD, 100K TPD)
        'llama-3.3-70b-versatile' => [
            'name' => 'Meta Llama 3.3 70B Versatile',
            'description' => 'Balanceado - Muy bueno para español',
            'limits' => ['rpm' => 30, 'rpd' => 1000, 'tpm' => 12000, 'tpd' => 100000],
        ],
        // Con herramientas integradas
        'openai/gpt-oss-20b' => [
            'name' => 'OpenAI GPT-OSS 20B',
            'description' => 'Incluye browser search y code execution',
            'limits' => ['rpm' => 30, 'rpd' => 1000, 'tpm' => 8000, 'tpd' => 200000],
        ],
        'openai/gpt-oss-120b' => [
            'name' => 'OpenAI GPT-OSS 120B',
            'description' => 'Más grande, con reasoning capabilities',
            'limits' => ['rpm' => 30, 'rpd' => 1000, 'tpm' => 8000, 'tpd' => 200000],
        ],
        // Con visión
        'meta-llama/llama-4-scout-17b-16e-instruct' => [
            'name' => 'Meta Llama 4 Scout 17B',
            'description' => 'Soporte para VISIÓN DE IMÁGENES',
            'limits' => ['rpm' => 30, 'rpd' => 1000, 'tpm' => 30000, 'tpd' => 500000],
        ],
        // Multilingüe
        'moonshotai/kimi-k2-instruct' => [
            'name' => 'Moonshot Kimi K2 Instruct',
            'description' => 'Bueno para multilingüe',
            'limits' => ['rpm' => 60, 'rpd' => 1000, 'tpm' => 10000, 'tpd' => 300000],
        ],
    ],
];
