# Laravel AI Auto-Translator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/youssef-mekkkawy/laravel-ai-translator.svg?style=flat-square)](https://packagist.org/packages/youssef-mekkkawy/laravel-ai-translator)
[![Total Downloads](https://img.shields.io/packagist/dt/youssef-mekkkawy/laravel-ai-translator.svg?style=flat-square)](https://packagist.org/packages/youssef-mekkkawy/laravel-ai-translator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/Youssef-Mekkkawy/laravel-ai-translator/run-tests?label=tests)](https://github.com/Youssef-Mekkkawy/laravel-ai-translator/actions?query=workflow%3Arun-tests+branch%3Amain)

AI-powered automatic translation for Laravel applications. Write your app in one language, translate to many with a single command.

## The Problem

Building multi-language Laravel apps traditionally requires:

- ❌ Creating duplicate inputs in admin panels for each language
- ❌ Manually copying content across language files
- ❌ Running translation commands repeatedly
- ❌ Lost manual edits when re-translating
- ❌ Wasted time on busywork instead of features

**8+ hours per project spent on translation admin.**

## The Solution

Laravel AI Auto-Translator eliminates manual translation workflow:

✅ Auto-scans Blade views for translation keys  
✅ Detects new or changed translations  
✅ Uses AI (DeepL, OpenAI, Claude) for high-quality translations  
✅ Generates all language files automatically  
✅ Protects manual edits with smart lock system  
✅ Saves 70% on API costs with intelligent change tracking

**Write once. Translate everywhere. Automatically.**

## Features

- 🔍 **Smart Scanning**: Automatically detects `__()`, `@lang()`, and `trans()` in your views
- 🤖 **Multiple AI Providers**: DeepL, OpenAI, Claude (more coming soon)
- 💰 **Cost Optimization**: Hash-based change tracking - only translate what changed
- 🔒 **Manual Override Protection**: Lock translations to prevent overwrites
- 💾 **Automatic Backups**: Every translation run creates a backup
- ⚡ **Zero Configuration**: Works out of the box with sensible defaults
- 🌍 **30+ Languages**: Support for all major languages

## Installation

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=ai-translator-config
```

## Quick Start

### 1. Configure Your AI Provider

Add to your `.env`:

```bash
# Choose your provider (deepl, openai, or claude)
AUTO_TRANSLATE_DRIVER=deepl

# DeepL (recommended - free tier available)
DEEPL_API_KEY=your-deepl-api-key
DEEPL_PLAN=free  # or 'pro'

# OR OpenAI
OPENAI_API_KEY=sk-your-openai-key

# OR Claude
ANTHROPIC_API_KEY=sk-ant-your-claude-key

# Your supported languages
SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
```

### 2. Run Translation

```bash
php artisan lang:sync
```

That's it! All your translation files are generated automatically.

## Usage

### Basic Workflow

**1. Write your views in one language:**

```blade
<!-- resources/views/welcome.blade.php -->
<h1>{{ __('welcome.title') }}</h1>
<p>{{ __('welcome.description') }}</p>
```

**2. Create your English translations:**

```php
// lang/en/welcome.php
return [
    'title' => 'Welcome to Our Platform',
    'description' => 'Start building amazing things today',
];
```

**3. Run the sync command:**

```bash
php artisan lang:sync
```

**4. All language files generated automatically:**

```
lang/
├── en/welcome.php  (original)
├── ar/welcome.php  (auto-generated: مرحبا في منصتنا)
├── fr/welcome.php  (auto-generated: Bienvenue sur notre plateforme)
└── es/welcome.php  (auto-generated: Bienvenido a nuestra plataforma)
```

### Protecting Manual Edits

If you manually improve a translation, lock it to prevent overwrites:

```bash
# Lock a specific translation
php artisan lang:lock ar welcome.title

# Lock all translations in a file
php artisan lang:lock ar welcome.*

# List all locked translations
php artisan lang:locked

# Unlock when needed
php artisan lang:unlock ar welcome.title
```

### Backup & Restore

Automatic backups are created before each translation:

```bash
# List available backups
php artisan lang:backup:list

# Restore from a backup
php artisan lang:restore 2026-04-14_10-30-00

# Restore latest backup
php artisan lang:restore --latest
```

## Configuration

All configuration is optional. The package works with sensible defaults.

```php
// config/ai-translator.php

return [
    // AI provider: 'deepl', 'openai', 'claude'
    'driver' => env('AUTO_TRANSLATE_DRIVER', 'deepl'),

    // Supported languages (ISO 639-1 codes)
    'languages' => explode(',', env('SUPPORTED_LANGUAGES', 'en,ar,fr,es')),

    // Source language
    'default_language' => env('DEFAULT_LANGUAGE', 'en'),

    // Backup settings
    'backup' => [
        'enabled' => env('AUTO_TRANSLATE_BACKUP', true),
        'keep' => env('AUTO_TRANSLATE_BACKUP_KEEP', 5),
    ],

    // ... more options
];
```

## Commands

| Command | Description | Example |
|---------|-------------|---------|
| `lang:scan` | Scan views for translation keys | `php artisan lang:scan` |
| `lang:translate` | Translate to all configured languages | `php artisan lang:translate` |
| `lang:lock` | Lock a translation to protect from overwrites | `php artisan lang:lock ar auth.login` |
| `lang:unlock` | Remove a lock from a translation | `php artisan lang:unlock ar auth.login` |
| `lang:locked` | List all currently locked translations | `php artisan lang:locked` |
| `lang:validate` | Validate translation quality and detect issues | `php artisan lang:validate` |
| `lang:restore` | Restore translations from a backup | `php artisan lang:restore` |
| `lang:backup:list` | List all available backups | `php artisan lang:backup:list` |

### Command Options

**`lang:translate`**
```bash
php artisan lang:translate                # Translate all languages
php artisan lang:translate --lang=ar      # Translate Arabic only
php artisan lang:translate --force        # Re-translate all keys (ignore change tracking)
php artisan lang:translate --dry-run      # Preview without writing any files
php artisan lang:translate --no-backup    # Skip backup creation
```

**`lang:validate`**
```bash
php artisan lang:validate                 # Validate all languages
php artisan lang:validate --lang=ar       # Validate Arabic only
php artisan lang:validate --strict        # Exit with error code if warnings found
```

**`lang:restore`**
```bash
php artisan lang:restore                                # Interactive selection
php artisan lang:restore 2026-04-16_14-30-00           # Restore specific backup
php artisan lang:restore --latest                       # Restore most recent backup
php artisan lang:restore --list                         # List backups without restoring
```

**`lang:backup:list`**
```bash
php artisan lang:backup:list              # Show backup summary table
php artisan lang:backup:list --details    # Show file list per backup
```

## Examples

### First-Time Setup

```bash
# 1. Install the package
composer require youssef-mekkkawy/laravel-ai-translator

# 2. Add your API key to .env
DEEPL_API_KEY=your-key-here
SUPPORTED_LANGUAGES=en,ar,fr,es

# 3. Run translation
php artisan lang:translate
```

### Daily Workflow

```bash
# 1. Add new translation keys to your Blade views
{{ __('dashboard.new_feature') }}

# 2. Add the English source value
# lang/en/dashboard.php → 'new_feature' => 'New Feature'

# 3. Translate — only new/changed keys are sent to the API
php artisan lang:translate

# 4. Review quality (optional)
php artisan lang:validate

# 5. Lock any translations you've manually improved
php artisan lang:lock ar dashboard.new_feature
```

### Safe Deploy Workflow

```bash
# Before deploy: create a manual backup
php artisan lang:backup:list

# If something goes wrong: restore
php artisan lang:restore --latest

# Or pick a specific backup
php artisan lang:restore 2026-04-16_14-30-00
```

---

## Configuration

All configuration lives in `config/laravel-ai-translator.php`:

```php
return [
    // AI provider: 'deepl', 'openai', 'claude'
    'driver' => env('AUTO_TRANSLATE_DRIVER', 'deepl'),

    // Languages to support (ISO 639-1 codes)
    'languages' => explode(',', env('SUPPORTED_LANGUAGES', 'en,ar,fr,es')),

    // Source language (never translated)
    'default_language' => env('DEFAULT_LANGUAGE', 'en'),

    // Blade view directories to scan
    'scan_paths' => [
        resource_path('views'),
    ],

    // Patterns to ignore during scanning
    'exclude_files' => explode(',', env('AUTO_TRANSLATE_EXCLUDE_FILES', 'vendor/**,node_modules/**,tests/**')),

    // Backup settings
    'backup' => [
        'enabled' => env('AUTO_TRANSLATE_BACKUP', true),
        'path'    => base_path('lang/.backup'),
        'keep'    => env('AUTO_TRANSLATE_BACKUP_KEEP', 5),   // Keep last N backups
    ],

    // Provider credentials
    'providers' => [
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'plan'    => env('DEEPL_PLAN', 'free'),          // 'free' or 'pro'
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
        ],
    ],
];
```

---

## Troubleshooting

### "DeepL API key not configured"
**Solution:** Add `DEEPL_API_KEY=your-key` to your `.env` file and run `php artisan config:clear`.

### "No translation keys found"
**Solution:** Make sure you're using the standard `{{ __('key') }}` syntax in your Blade views. The scanner supports `__()`, `@lang()`, and `trans()`.

### "Translation files not created"
**Solution:** Check that your `lang/` directory exists and is writable. Also ensure `scan_paths` in the config points to the correct views directory.

### "Locked translation was overwritten"
**Solution:** The lock wasn't applied before the last translation run. Re-apply it: `php artisan lang:lock <lang> <key>`, then translations will be protected on subsequent runs.

### "Validate shows placeholder issues"
**Solution:** Your AI translation removed a placeholder like `:name` or `{0}`. Manually correct the affected translation, then lock it: `php artisan lang:lock <lang> <key> --reason="placeholder fix"`.

### Want to start fresh?
```bash
# See what backups exist
php artisan lang:backup:list

# Restore a clean state
php artisan lang:restore --latest
```

---

## AI Provider Comparison

| Provider   | Quality    | Speed      | Cost | Free Tier        |
| ---------- | ---------- | ---------- | ---- | ---------------- |
| **DeepL**  | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | $    | 500k chars/month |
| **OpenAI** | ⭐⭐⭐⭐   | ⭐⭐⭐⭐   | $$   | No               |
| **Claude** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐   | $$   | No               |

**Recommendation**: Start with DeepL for the free tier and excellent quality.

## How It Works

1. **Scanner** detects all translation keys in your Blade views
2. **Tracker** compares current translations with stored hashes
3. **AI Provider** translates only new/changed keys
4. **Writer** generates language files with proper Laravel structure
5. **Backup** saves previous version before changes

**Smart optimization**: Only translates what changed, saving 70%+ on API costs.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email your-email@example.com instead of using the issue tracker.

## Credits

- [Youssef Mekkkawy](https://github.com/Youssef-Mekkkawy) - Creator & Maintainer
- Built by [aissp](https://aissp.com)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- **Documentation**: [Full documentation](https://github.com/Youssef-Mekkkawy/laravel-ai-translator/wiki)
- **Issues**: [GitHub Issues](https://github.com/Youssef-Mekkkawy/laravel-ai-translator/issues)
- **Discussions**: [GitHub Discussions](https://github.com/Youssef-Mekkkawy/laravel-ai-translator/discussions)

---

Built with ❤️ by developers, for developers.

**[Star this repo](https://github.com/Youssef-Mekkkawy/laravel-ai-translator)** if you find it useful!
