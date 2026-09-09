<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation Driver
    |--------------------------------------------------------------------------
    */
    'driver' => env('AUTO_TRANSLATE_DRIVER', 'deepl'),

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    */
    'languages' => explode(',', env('SUPPORTED_LANGUAGES', 'en,ar,fr,es')),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    */
    'default_language' => env('DEFAULT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    */
    'scan_paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Patterns
    |--------------------------------------------------------------------------
    */
    'exclude_files' => explode(',', env('AUTO_TRANSLATE_EXCLUDE_FILES', 'vendor/**,node_modules/**,tests/**,lang/**,storage/**')),

    /*
    |--------------------------------------------------------------------------
    | Translation Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'ollama' => [
            'auto_start' => env('OLLAMA_AUTO_START', false), // true, false, or 'ask'
            'auto_pull' => env('OLLAMA_AUTO_PULL', true),
            'model' => env('OLLAMA_MODEL', 'llama3.2'),
            'api_url' => env('OLLAMA_API_URL', 'http://localhost:11434'),
        ],
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'plan' => env('DEEPL_PLAN', 'free'),
        ],
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],
    ], // ← closes 'providers'

    /*
    |--------------------------------------------------------------------------
    | Translation Options
    |--------------------------------------------------------------------------
    */
    'options' => [
        'context' => env('AUTO_TRANSLATE_CONTEXT', ''),
        'exclude_words' => explode(',', env('AUTO_TRANSLATE_EXCLUDE_WORDS', 'Laravel,PHP,API')),
        'preserve_html' => env('AUTO_TRANSLATE_PRESERVE_HTML', true),
        'preserve_placeholders' => env('AUTO_TRANSLATE_PRESERVE_PLACEHOLDERS', true),
        'chunk_size' => env('AUTO_TRANSLATE_CHUNK_SIZE', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('AUTO_TRANSLATE_BACKUP', true),
        'path' => base_path('lang/.backup'),
        'keep' => env('AUTO_TRANSLATE_BACKUP_KEEP', 5),
        'lang_path' => null, // resolved at runtime
        'cleanup' => env('AUTO_TRANSLATE_BACKUP_CLEANUP', true),
        'auto_restore_on_error' => env('AUTO_TRANSLATE_AUTO_RESTORE_ON_ERROR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'metadata_file' => base_path('lang/.translations-meta.json'),
        'lock_file' => base_path('lang/.locked-translations.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'enabled' => env('AI_TRANSLATOR_DASHBOARD', true),
        'path' => env('AI_TRANSLATOR_PATH', 'ai-translator'),
        'middleware' => ['web'],
    ],
];
