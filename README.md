<div align="center">

# 🌍 Laravel AI Translator

**Automatic AI-powered translation for Laravel applications.**  
Write your app once in English — translate everywhere automatically.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/youssef-mekkkawy/laravel-ai-translator.svg?style=flat-square)](https://packagist.org/packages/youssef-mekkkawy/laravel-ai-translator)
[![Total Downloads](https://img.shields.io/packagist/dt/youssef-mekkkawy/laravel-ai-translator.svg?style=flat-square)](https://packagist.org/packages/youssef-mekkkawy/laravel-ai-translator)
[![Tests](https://img.shields.io/github/actions/workflow/status/Youssef-Mekkkawy/laravel-ai-translator/tests.yml?label=tests&style=flat-square)](https://github.com/Youssef-Mekkkawy/laravel-ai-translator/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/youssef-mekkkawy/laravel-ai-translator.svg?style=flat-square)](https://packagist.org/packages/youssef-mekkkawy/laravel-ai-translator)
[![License](https://img.shields.io/github/license/Youssef-Mekkkawy/laravel-ai-translator?style=flat-square)](LICENSE)
[![Buy Me a Coffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-Support-FFDD00?style=flat-square&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/youssef.mekkawy)

</div>

---

## The Problem

Building multi-language Laravel apps traditionally wastes **8+ hours per project**:

- ❌ Creating duplicate inputs in admin panels for every language
- ❌ Manually copying content across language files
- ❌ Re-translating everything when one string changes
- ❌ Manual edits getting overwritten by automation
- ❌ Paying $100–600/month for SaaS tools that are overkill

## The Solution

```bash
composer require youssef-mekkkawy/laravel-ai-translator
php artisan lang:translate
```

That's it. Your entire app is translated.

✅ Auto-scans all Blade views for `__()`, `@lang()`, and `trans()` keys  
✅ Translates only what **changed** — saves 70%+ on API costs  
✅ Protects manual edits with a **lock system**  
✅ Works **offline and free** with [Ollama](https://ollama.com)  
✅ Embedded dashboard at `/ai-translator` (like Laravel Telescope)  
✅ Zero configuration required

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 10.x or 11.x |
| Ollama *(optional)* | Any recent version |

---

## Installation

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=ai-translator-config
```

---

## Quick Start

### 1. Choose your AI provider

**Option A — Ollama (free, local, no API key needed):**

```bash
# Install Ollama from https://ollama.com
ollama pull llama3
```

```env
AUTO_TRANSLATE_DRIVER=ollama
OLLAMA_MODEL=llama3
OLLAMA_API_URL=http://localhost:11434
SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
```

**Option B — Cloud provider:**

```env
AUTO_TRANSLATE_DRIVER=deepl
DEEPL_API_KEY=your-api-key
SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
```

### 2. Scan your views

```bash
php artisan lang:scan
```

See all translation keys found in your Blade files, with their status.

### 3. Translate

```bash
# Preview what would happen (no changes made)
php artisan lang:translate --dry-run

# Translate everything
php artisan lang:translate

# Translate to a specific language only
php artisan lang:translate --lang=ar

# Force re-translate all keys
php artisan lang:translate --force
```

### 4. Result

```
lang/
├── en/
│   ├── auth.php         ← your original
│   └── welcome.php      ← your original
├── ar/
│   ├── auth.php         ← auto-generated ✅
│   └── welcome.php      ← auto-generated ✅
├── fr/
│   ├── auth.php         ← auto-generated ✅
│   └── welcome.php      ← auto-generated ✅
└── es/
    ├── auth.php         ← auto-generated ✅
    └── welcome.php      ← auto-generated ✅
```

---

## AI Providers

| Provider | Quality | Speed | Cost | Offline |
|---|---|---|---|---|
| **Ollama** | ⭐⭐⭐⭐ | ⭐⭐⭐ | Free | ✅ Yes |
| **DeepL** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | $ | ❌ No |
| **Claude** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | $$ | ❌ No |
| **ChatGPT** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | $$ | ❌ No |
| **Gemini** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | $ | ❌ No |

> **Recommended for most developers:** Start with Ollama (free, private). Switch to DeepL or Claude for production.

---

## Commands

### `lang:scan`
Scan Blade views and show all translation keys.

```bash
php artisan lang:scan
php artisan lang:scan --missing-only        # Show only missing keys
php artisan lang:scan --path=resources/views/admin  # Custom path
```

### `lang:translate`
Translate all keys to all configured languages.

```bash
php artisan lang:translate
php artisan lang:translate --dry-run        # Preview only
php artisan lang:translate --lang=ar        # One language
php artisan lang:translate --force          # Re-translate everything
php artisan lang:translate --no-backup      # Skip backup creation
```

### `lang:validate`
Validate translation quality — checks for missing keys, broken placeholders, missing HTML tags.

```bash
php artisan lang:validate
php artisan lang:validate --lang=ar         # One language
php artisan lang:validate --strict          # Fail on warnings too
```

### `lang:lock` / `lang:unlock`
Protect manual translations from being overwritten.

```bash
php artisan lang:lock ar auth.login                 # Lock one key
php artisan lang:lock ar auth.* --all               # Lock a pattern
php artisan lang:lock ar auth.login --reason="Client preferred shorter translation"

php artisan lang:unlock ar auth.login               # Unlock one key
php artisan lang:locked                             # List all locked keys
php artisan lang:locked --lang=ar                   # Filter by language
```

### `lang:backup:list` / `lang:restore`
Manage translation backups (automatic before every sync).

```bash
php artisan lang:backup:list                        # Show available backups
php artisan lang:restore 2026-04-14_10-30-00        # Restore specific backup
php artisan lang:restore --latest                   # Restore most recent
```

---

## Configuration

All options are in `config/ai-translator.php` after publishing. The most important ones:

```php
return [
    // Active AI driver: 'ollama', 'deepl', 'claude', 'openai', 'gemini'
    'driver' => env('AUTO_TRANSLATE_DRIVER', 'ollama'),

    // Languages to translate into
    'languages' => explode(',', env('SUPPORTED_LANGUAGES', 'en,ar,fr,es')),

    // Source language
    'default_language' => env('DEFAULT_LANGUAGE', 'en'),

    // Providers configuration
    'providers' => [
        'ollama' => [
            'model'   => env('OLLAMA_MODEL', 'llama3'),
            'api_url' => env('OLLAMA_API_URL', 'http://localhost:11434'),
        ],
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'plan'    => env('DEEPL_PLAN', 'free'),
        ],
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
        ],
        // ...
    ],

    // Backup settings
    'backup' => [
        'enabled' => env('AUTO_TRANSLATE_BACKUP', true),
        'keep'    => env('AUTO_TRANSLATE_BACKUP_KEEP', 5),
    ],
];
```

---

## Dashboard

Access the embedded dashboard at `/ai-translator` in your application.

The dashboard provides:

- **Overview** — key counts, translation status, last sync info
- **Languages** — enable/disable languages, per-language progress
- **Settings** — switch providers, configure API keys, manage models
- **Locked Keys** — view and unlock protected translations
- **History** — sync logs, cost tracking, what changed each run

> The dashboard supports both **Arabic (RTL)** and **English (LTR)** — switchable from within the UI.

---

## How Smart Change Tracking Works

Every time you run `lang:translate`, the package:

1. Scans your Blade views for translation keys
2. Loads your English `lang/en/*.php` files
3. Generates an MD5 hash of every value
4. Compares against stored hashes in `lang/.translations-meta.json`
5. **Only translates keys that actually changed**
6. Saves new hashes for next run

Result: if you have 500 keys and change 3, only 3 API calls are made.

---

## Protecting Manual Translations

Sometimes the AI translation isn't quite right. Lock it:

```bash
php artisan lang:lock ar auth.login --reason="Client prefers 'دخول' over 'تسجيل الدخول'"
```

That key will never be overwritten, even when you run `lang:translate --force`.

To see what's locked:

```bash
php artisan lang:locked
```

To restore AI control:

```bash
php artisan lang:unlock ar auth.login
```

---

## Placeholder & HTML Preservation

The package automatically preserves:

- Laravel placeholders: `:name`, `:count`, `:attribute`
- Numbered placeholders: `{0}`, `{1}`, `{2}`
- HTML tags: `<strong>`, `<a href="#">`, `<br>`, etc.

Example:

```php
// English
'greeting' => 'Hello <strong>:name</strong>, you have :count messages.'

// Arabic (auto-generated — placeholders and HTML preserved)
'greeting' => 'مرحبا <strong>:name</strong>، لديك :count رسائل.'
```

---

## Supported Translation Syntaxes

The scanner detects all standard Laravel translation helpers:

```blade
{{ __('welcome.title') }}
{{ __("auth.login") }}
@lang('messages.success')
{{ trans('errors.404') }}
{{ __('user.greeting', ['name' => $user->name]) }}
```

---

## Testing

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

---

## Contributing

Contributions are very welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

---

## Security

If you discover a security vulnerability, please email **your-email@example.com** instead of using the issue tracker.

---

## Support

This package is free and always will be. If it saved you time on a project, a coffee keeps me going ☕

[![Buy Me a Coffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-Support-FFDD00?style=flat-square&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/youssef.mekkawy)

---

## Credits

- [Youssef Mekkkawy](https://github.com/Youssef-Mekkkawy) — Creator & Maintainer

---

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

---

<div align="center">

**Built with ❤️ by developers, for developers.**

⭐ Star this repo if it saves you time!

</div>
