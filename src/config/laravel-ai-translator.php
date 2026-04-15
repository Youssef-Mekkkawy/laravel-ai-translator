<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Source Language
    |--------------------------------------------------------------------------
    |
    | The source language for your translations. This is typically 'en' for English.
    | All translations will be generated from this language.
    |
    */
    'source_language' => env('AI_TRANSLATOR_SOURCE_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Target Languages
    |--------------------------------------------------------------------------
    |
    | The languages you want to translate to. Add or remove language codes as needed.
    | Common codes: ar (Arabic), fr (French), es (Spanish), de (German), etc.
    |
    */
    'target_languages' => [
        'ar', // Arabic
        'fr', // French
        'es', // Spanish
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Provider
    |--------------------------------------------------------------------------
    |
    | The AI translation service to use. Currently supported: 'deepl'
    | Future: 'openai', 'claude', 'google'
    |
    */
    'provider' => env('AI_TRANSLATOR_PROVIDER', 'deepl'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    |
    | API keys and settings for each translation provider
    |
    */
    'providers' => [
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'plan' => env('DEEPL_PLAN', 'free'), // 'free' or 'pro'
        ],
        // Future providers
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
        ],
        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-3-sonnet'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | View Scanner Configuration
    |--------------------------------------------------------------------------
    |
    | Paths to scan for translation keys in your Blade views
    |
    */
    'scan_paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Patterns
    |--------------------------------------------------------------------------
    |
    | Files or directories to exclude from scanning
    |
    */
    'exclude_files' => [
        '*/vendor/*',
        '*/node_modules/*',
        '*.min.js',
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for backing up translation files before overwriting
    |
    */
    'backup' => [
        'enabled' => true,
        'path' => storage_path('app/translation-backups'),
        'keep_days' => 30, // How many days to keep backups
    ],

    /*
    |--------------------------------------------------------------------------
    | Change Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Enable smart change detection to save API costs by only translating
    | what actually changed. Tracks translation hashes in metadata file.
    |
    | 💡 TIP: This can save you 70% on translation costs!
    |
    */
    'change_tracking' => [
        'enabled' => true, // Enable/disable change tracking
        'metadata_path' => lang_path('.translations-meta.json'), // Where to store metadata
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Options
    |--------------------------------------------------------------------------
    |
    | General translation behavior settings
    |
    */
    'translation' => [
        'preserve_parameters' => true, // Keep :name, {count}, etc. in translations
        'formality' => 'default', // 'default', 'formal', or 'informal' (provider-dependent)
        'batch_size' => 50, // Number of translations per API call
    ],

    /*
    |--------------------------------------------------------------------------
    | File Writer Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for writing translation files
    |
    */
    'file_writer' => [
        'format' => 'php', // Output format: 'php' or 'json'
        'organize_by_namespace' => true, // Group by namespace (auth.php, validation.php, etc.)
        'sort_keys' => true, // Sort translation keys alphabetically
    ],
];