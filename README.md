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
php artisan ai-translator:install
php artisan lang:translate
```

That's it. Your entire app is translated.

✅ Auto-detects your stack (Blade, Breeze, Inertia+Vue, Inertia+React, Livewire)  
✅ Scans Blade, Vue, JSX, and TSX files for all translation keys  
✅ Supports PHP files (`lang/en/*.php`) and JSON files (`lang/en.json`)  
✅ Auto-creates missing source files when keys are found  
✅ Translates only what **changed** — saves 70%+ on API costs  
✅ Protects manual edits with a **lock system**  
✅ Works **offline and free** with [Ollama](https://ollama.com)  
✅ **Non-blocking** — translation runs in the background, your site stays live  
✅ Embedded dashboard at `/ai-translator` (like Laravel Telescope)  
✅ **180+ languages** supported out of the box  
✅ Zero extra dependencies — works with any Laravel project

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 10.x, 11.x, 12.x, 13.x |
| Ollama *(optional)* | Any recent version |

---

## Installation

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

Then run the interactive setup wizard:

```bash
php artisan ai-translator:install
```

The wizard will:
- Detect your Laravel stack automatically (Blade, Vue, React, Livewire, Breeze)
- Ask which AI provider you want to use
- Ask which languages to translate into
- Update your `.env` file
- Scan your views and create source language files
- Verify your provider is connected

---

## Quick Start

### 1. Run the install wizard

```bash
php artisan ai-translator:install
```

### 2. Translate

```bash
php artisan lang:translate
```

### 3. Open the dashboard

```
http://yourapp.test/ai-translator
```

That's it. Your app is now multilingual.

---

## AI Providers

| Provider | Quality | Speed | Cost | Offline |
|---|---|---|---|---|
| **Ollama** | ⭐⭐⭐⭐ | ⭐⭐⭐ | Free | ✅ Yes |
| **DeepL** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Free tier available | ❌ No |
| **Claude** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Paid | ❌ No |
| **ChatGPT** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Paid | ❌ No |
| **Gemini** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Free tier available | ❌ No |

> **Recommended for most developers:** Start with Ollama (free, private). Switch to DeepL or Claude for production.

### Manual provider configuration

**.env (Ollama):**

```env
AUTO_TRANSLATE_DRIVER=ollama
OLLAMA_MODEL=llama3.2
OLLAMA_API_URL=http://localhost:11434
SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
```

**.env (DeepL):**

```env
AUTO_TRANSLATE_DRIVER=deepl
DEEPL_API_KEY=your-api-key
DEEPL_PLAN=free
SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
```

---

## Stack Detection

The package automatically detects your Laravel stack and configures itself accordingly — no manual setup needed.

| Stack | Scan paths | Output format |
|---|---|---|
| Blade | `resources/views/**/*.blade.php` | PHP files |
| Blade + Livewire | `resources/views/**/*.blade.php` | PHP files |
| Inertia + Vue | `resources/views/`, `resources/js/**/*.vue` | JSON files |
| Inertia + React | `resources/views/`, `resources/js/**/*.jsx`, `.tsx` | JSON files |
| Breeze | Auto-detected starter kit + JSON source | JSON files |

Detection runs once during `ai-translator:install` and is stored in `lang/.ai-translator-runtime.json`.

---

## Commands

### `ai-translator:install`
Interactive setup wizard. Run once after installation.

```bash
php artisan ai-translator:install
```

Detects your stack, configures your provider, sets your languages, and scans views.

---

### `lang:translate`
Translate all keys to all configured languages. **Runs in the background** — your site stays live during translation.

```bash
php artisan lang:translate
php artisan lang:translate --dry-run        # Preview only, no changes
php artisan lang:translate --lang=ar        # One language only
php artisan lang:translate --force          # Re-translate everything
php artisan lang:translate --no-backup      # Skip backup creation
```

---

### `lang:scan`
Scan views and show all translation keys with their status.

```bash
php artisan lang:scan
php artisan lang:scan --missing-only        # Show only missing keys
php artisan lang:scan --path=resources/views/admin
```

---

### `lang:clean`
Find and remove translation keys that no longer exist in any view.

```bash
php artisan lang:clean                      # Interactive — shows what would be removed
php artisan lang:clean --force              # Remove without confirmation
php artisan lang:clean --lang=ar            # Clean one language only
```

---

### `lang:validate`
Validate translation quality — checks for missing keys, broken placeholders, missing HTML tags.

```bash
php artisan lang:validate
php artisan lang:validate --lang=ar         # One language
php artisan lang:validate --strict          # Fail on warnings too
```

---

### `lang:lock` / `lang:unlock`
Protect manual translations from being overwritten.

```bash
php artisan lang:lock ar auth.login
php artisan lang:lock ar auth.login --reason="Client preferred term"

php artisan lang:unlock ar auth.login
php artisan lang:locked                     # List all locked keys
php artisan lang:locked --lang=ar           # Filter by language
```

---

### `lang:backup:list` / `lang:restore`
Manage translation backups. A backup is created automatically before every translation run.

```bash
php artisan lang:backup:list
php artisan lang:restore 2026-04-14_10-30-00
php artisan lang:restore --latest
```

---

## Background Translation

Translation runs in a **completely separate background process** — the web server is never blocked.

```
User clicks Translate
  → Job queued in lang/.translation-queue.json
  → Background worker spawned (uses PHP_BINARY — cross-platform)
  → Web server returns immediately ✅
  → Your site stays live for all visitors ✅
  → Dashboard stays navigable ✅

Translation finishes (2 seconds to 30 minutes, depending on provider)
  → Toast notification appears
  → Stats refresh automatically
```

This works on **all setups** with zero extra dependencies:
- `php artisan serve` (single-threaded dev server)
- Laravel Valet / Herd
- Nginx / Apache
- Docker / Sail
- Shared hosting

---

## Dashboard

Access the embedded dashboard at `/ai-translator`.

### Overview
Real-time stats — total keys, translated, missing, locked. Progress bar during translation. Quick actions: Scan, Translate, Dry Run.

### Languages
Full list of configured languages with per-language coverage bars. Add any of **180+ languages** from the built-in list — the new language is automatically translated in the background without blocking navigation. Enable, disable, or remove languages at any time.

### Locked Keys
View, add, and remove translation locks. Filter by language.

### History
Every translation run with timestamp, provider, duration, and which keys changed.

### Backups
Create and restore snapshots of your language files. Configure how many backups to keep.

### Settings
Switch providers, configure API keys, set Ollama model and URL, adjust chunk size, set context prompt.

> The dashboard supports **10 languages** (EN, AR, FR, ES, DE, ZH, JA, TR, RU, PT) with on-demand AI generation for 170+ more.

---

## Configuration

All options are in `config/ai-translator.php` after publishing with `vendor:publish`.

```php
return [
    // Active AI driver: 'ollama', 'deepl', 'claude', 'openai', 'gemini'
    'driver' => env('AUTO_TRANSLATE_DRIVER', 'ollama'),

    // Languages to translate into (managed via dashboard or .env)
    'languages' => explode(',', env('SUPPORTED_LANGUAGES', 'en,ar,fr,es')),

    // Source language of your application
    'default_language' => env('DEFAULT_LANGUAGE', 'en'),

    // Paths to scan for translation keys
    'scan_paths' => [
        resource_path('views'),
        // resource_path('js'),  ← added automatically for Vue/React stacks
    ],

    // Providers
    'providers' => [
        'ollama' => [
            'model'   => env('OLLAMA_MODEL', 'llama3.2'),
            'api_url' => env('OLLAMA_API_URL', 'http://localhost:11434'),
        ],
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'plan'    => env('DEEPL_PLAN', 'free'),
        ],
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model'   => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],
    ],

    // Backup settings
    'backup' => [
        'enabled' => env('AUTO_TRANSLATE_BACKUP', true),
        'keep'    => env('AUTO_TRANSLATE_BACKUP_KEEP', 5),
    ],

    // Translation options
    'options' => [
        'chunk_size' => env('AUTO_TRANSLATE_CHUNK_SIZE', 50),
        'context'    => env('AUTO_TRANSLATE_CONTEXT', ''),
    ],
];
```

---

## How Smart Change Tracking Works

Every time you run `lang:translate`, the package:

1. Scans your views for translation keys
2. Loads your source language files (`lang/en/*.php` and `lang/en.json`)
3. Generates an MD5 hash of every value
4. Compares against stored hashes in `lang/.translations-meta.json`
5. **Only translates keys that actually changed**
6. Saves new hashes for the next run

Result: if you have 500 keys and change 3, only 3 API calls are made.

---

## Supported Translation Syntaxes

### Blade / PHP
```blade
{{ __('welcome.title') }}
{{ __('auth.login') }}
@lang('messages.success')
{{ trans('errors.404') }}
{{ __('user.greeting', ['name' => $user->name]) }}
```

### Vue (Inertia)
```vue
{{ $t('welcome.title') }}
{{ t('auth.login') }}
```

### React / JSX (Inertia)
```jsx
{__('welcome.title')}
{i18n.t('auth.login')}
```

---

## Placeholder & HTML Preservation

The package automatically preserves:

- Laravel placeholders: `:name`, `:count`, `:attribute`
- Numbered placeholders: `{0}`, `{1}`, `{2}`
- HTML tags: `<strong>`, `<a href="#">`, `<br>`, etc.

```php
// English
'greeting' => 'Hello <strong>:name</strong>, you have :count messages.'

// Arabic (auto-generated — placeholders and HTML preserved)
'greeting' => 'مرحبا <strong>:name</strong>، لديك :count رسائل.'
```

---

## Runtime Configuration

Settings changed via the dashboard (provider, model, languages) are stored in `lang/.ai-translator-runtime.json` — not in `.env`. This means:

- No server restarts when changing settings
- Safe for production deployments
- `.env` acts as the initial default only

```json
{
    "stack": "blade",
    "output_format": "auto",
    "supported_languages": ["en", "ar", "fr"],
    "driver": "ollama",
    "ollama_model": "llama3.2"
}
```

---

## File Structure

After running `lang:translate`:

```
lang/
├── .ai-translator-runtime.json   ← runtime settings (dashboard changes)
├── .translations-meta.json       ← hash tracking (change detection)
├── .translation-queue.json       ← background job queue
├── .translation-status.json      ← current worker status
├── .backup/                      ← automatic backups
├── en/
│   ├── auth.php                  ← your original PHP files
│   └── welcome.php
├── en.json                       ← your original JSON keys (Breeze/Vue/React)
├── ar/
│   ├── auth.php                  ← auto-generated ✅
│   └── welcome.php
├── ar.json                       ← auto-generated ✅
├── fr/
│   └── ...
└── fr.json
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

If you discover a security vulnerability, please email **youssef.mekkawy@example.com** instead of using the issue tracker. See [SECURITY.md](SECURITY.md) for details.

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
