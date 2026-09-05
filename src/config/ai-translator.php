<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation Driver
    |--------------------------------------------------------------------------
    | Supported: "deepl", "openai", "claude"
    */
    'driver' => env('AUTO_TRANSLATE_DRIVER', 'deepl'),

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    | List all languages you want to support (ISO 639-1 codes)
    */
    'languages' => explode(',', env('SUPPORTED_LANGUAGES', 'en,ar,fr,es')),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    | The source language for translations (usually English)
    */
    'default_language' => env('DEFAULT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    | Directories to scan for translation keys
    */
    'scan_paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Patterns
    |--------------------------------------------------------------------------
    | Files/directories to ignore during scanning
    */
    'exclude_files' => explode(',', env('AUTO_TRANSLATE_EXCLUDE_FILES', 'vendor/**,node_modules/**,tests/**')),

    /*
    |--------------------------------------------------------------------------
    | Translation Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'plan' => env('DEEPL_PLAN', 'free'), // 'free' or 'pro'
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'temperature' => 0.3,
            'max_tokens' => 2000,
        ],

        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
            'max_tokens' => 2000,
        ],
    ],

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
        'chunk_size' => env('AUTO_TRANSLATE_CHUNK_SIZE', 100),
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
        'cleanup' => env('AUTO_TRANSLATE_BACKUP_CLEANUP', true),
        'restore_confirm' => env('AUTO_TRANSLATE_RESTORE_CONFIRM', true),
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

    'dashboard' => [
        'path' => 'ai-translator',
        'enabled' => true,
    ],
];
