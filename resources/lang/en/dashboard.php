<?php

return [
    // Navigation
    'overview' => 'Overview',
    'languages' => 'Languages',
    'locked' => 'Locked Keys',
    'history' => 'History',
    'backups' => 'Backups',
    'settings' => 'Settings',
    'collapse' => 'Collapse',

    // Page subtitles
    'overview_sub' => 'Translation state of your application',
    'languages_sub' => 'Locales configured in config/ai-translator.php',
    'locked_sub' => 'Keys protected from being overwritten',
    'history_sub' => 'Every sync run, with cost and diff',
    'backups_sub' => 'Snapshots of your language files',
    'settings_sub' => 'Provider, models and behaviour',

    // Quick actions
    'quick_actions' => 'Quick actions',
    'scan' => 'Scan',
    'translate' => 'Translate',
    'dry_run' => 'Dry run',
    'active_provider' => 'Active provider',
    'last_sync' => 'Last sync',
    'coverage_by_lang' => 'Coverage by language',

    // Stats
    'total_keys' => 'Total Keys',
    'translated' => 'Translated',
    'missing' => 'Missing',
    'locked_keys' => 'Locked',

    // Lock modal
    'lock_key_title' => 'Lock a Key',
    'language' => 'Language',
    'key' => 'Key',
    'value' => 'Value',
    'reason_optional' => 'Reason (optional)',
    'reason_placeholder' => 'e.g. Client preferred term',
    'lock_key_confirm' => 'Lock Key',
    'locked_by' => 'Locked by',
    'reason' => 'Reason',
    'unlock' => 'Unlock',

    // Restore modal
    'restore_title' => 'Restore Backup',
    'restore_warn' => 'This will replace all current language files with the selected backup.',
    'restore_confirm' => 'Yes, restore',

    // Add language modal
    'add_language' => 'Add language',
    'add_language_hint' => 'Select a language to add to your project',
    'search_language' => 'Search languages...',

    // Backups
    'create_backup' => 'Create backup',
    'download' => 'Download',
    'restore' => 'Restore',
    'empty_backups_t' => 'No backups yet',
    'empty_backups_b' => 'A backup is created automatically before every translation run.',
    'backup_settings' => 'Backup settings',
    'keep_last' => 'Keep last',
    'keep_last_hint' => 'Older backups are deleted automatically',
    'auto_backup' => 'Auto-backup',
    'auto_backup_hint' => 'Before every translation run',

    // Settings
    'save' => 'Save changes',
    'reset' => 'Reset to defaults',
    'saving' => 'Saving...',

    // General
    'cancel' => 'Cancel',
    'files' => 'files',
    'keys' => 'keys',
    'star_repo' => 'Star on GitHub',
    'connected' => 'Connected',
    'buy_coffee' => 'Buy me a coffee',

    'enabled_of' => 'enabled of',
    'no_languages' => 'No languages configured',
    'add_config_hint' => 'Add languages in config/ai-translator.php',
    'complete' => 'Complete',
    'no_locked_keys' => 'No locked keys',
    'lock_hint' => 'Lock a translation to protect it from being overwritten by AI.',
    'lock_first' => 'Lock your first key',
    'all_languages' => 'All languages',
    'auto_label' => 'auto',
    'no_history' => 'No history yet',
    'run_translate' => 'Run',
    'to_start' => 'to create your first history entry.',
    'duration' => 'Duration',
    'skipped' => 'skipped',
    'updated' => 'updated',
    'all_up_to_date' => 'All keys were already up to date.',
    'not_running' => 'Not running',
    'test_connection' => 'Test connection',
    'model' => 'Model',
    'api_key' => 'API key',
    'api_key_hint' => 'Stored in your .env — never in the database.',
    'source_language' => 'Source language',
    'context' => 'Context prompt',
    'context_hint' => 'e.g. This is an e-commerce app. Keep translations formal.',
    'coming_soon_provider' => 'This provider is coming soon. Save your API key now and it will be used when support is added.',
];
