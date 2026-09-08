# Usage Guide — Laravel AI Auto-Translator

## Table of Contents

1. [Step-by-Step Tutorial](#step-by-step-tutorial)
2. [Lock Management Guide](#lock-management-guide)
3. [Backup & Restore Guide](#backup--restore-guide)
4. [Cost Optimization Tips](#cost-optimization-tips)
5. [Multi-Developer Workflow](#multi-developer-workflow)

---

## Step-by-Step Tutorial

This section walks you through translating a Laravel application from scratch.

### Prerequisites

- Laravel 10.x , 11.x, 12.x and 13.x 
- PHP 8.2+
- A DeepL API key (free tier: 500,000 characters/month)

### Step 1 — Install the Package

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

### Step 2 — Configure Your Environment

Add the following to your `.env` file:

```dotenv
DEEPL_API_KEY=your-deepl-api-key-here
DEEPL_PLAN=free

SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
```

### Step 3 — Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=laravel-ai-translator-config
```

This copies the config file to `config/laravel-ai-translator.php` so you can customize scan paths, backup settings, and provider options.

### Step 4 — Write Your Views Using Translation Keys

```blade
{{-- resources/views/welcome.blade.php --}}
<h1>{{ __('welcome.title') }}</h1>
<p>{{ __('welcome.subtitle') }}</p>
<a href="{{ route('register') }}">{{ __('welcome.get_started') }}</a>
```

### Step 5 — Create the English Source File

```php
// lang/en/welcome.php
return [
    'title'       => 'Welcome to Our Platform',
    'subtitle'    => 'Build amazing things, faster.',
    'get_started' => 'Get Started for Free',
];
```

### Step 6 — Preview Before Translating

Run a dry-run to see the cost estimate without making any changes:

```bash
php artisan lang:translate --dry-run
```

Expected output:
```
╔══════════════════════════════════════════════════════════╗
║       🌍  Laravel AI Auto-Translator  🤖                ║
╚══════════════════════════════════════════════════════════╝

📊 Analyzing translation requirements...

┌──────────────────────┬────────────┐
│ Metric               │ Value      │
├──────────────────────┼────────────┤
│ Total Keys           │ 3          │
│ Total Characters     │ 65         │
│ Target Languages     │ ar, fr, es │
│ Estimated Cost       │ $0.0004    │
│ Estimated Time       │ < 1 min    │
└──────────────────────┴────────────┘

🔍 DRY RUN MODE - No changes will be made
```

### Step 7 — Run the Translation

```bash
php artisan lang:translate
```

Confirm when prompted, and your language files are generated:

```
lang/
├── en/welcome.php    ← your original
├── ar/welcome.php    ← مرحبا في منصتنا
├── fr/welcome.php    ← Bienvenue sur notre plateforme
└── es/welcome.php    ← Bienvenido a nuestra plataforma
```

### Step 8 — Validate the Results

```bash
php artisan lang:validate
```

This checks for missing keys, broken placeholders, missing HTML tags, and excessively long translations.

---

## Lock Management Guide

Locks prevent the auto-translator from overwriting translations that have been manually refined.

### When to Lock

- You have manually improved an AI-generated translation
- A translator has reviewed and corrected specific strings
- A translation contains a brand name or proper noun that must not change
- Legal or compliance text that must remain exact

### Lock a Single Translation

```bash
php artisan lang:lock ar auth.login
php artisan lang:lock ar auth.login --reason="Reviewed by native speaker"
```

### Lock Multiple Keys With a Pattern

```bash
php artisan lang:lock ar auth.*
php artisan lang:lock ar legal.*  --reason="Legal team approved"
```

### List All Locked Translations

```bash
php artisan lang:locked
php artisan lang:locked --lang=ar     # Filter by language
php artisan lang:locked --verbose     # Show full lock details
```

### Unlock When You Want Auto-Translation Again

```bash
php artisan lang:unlock ar auth.login
```

### How Locking Interacts With Translation

When you run `php artisan lang:translate`:

1. The command scans all translation keys
2. For each key, it checks the lock file
3. **Locked keys are skipped entirely** — no API call is made, and the file is not touched
4. The summary shows how many keys were locked: `Locked Keys: 3`

This means locks are safe to use freely — they don't cost extra API calls.

---

## Backup & Restore Guide

### How Backups Work

A backup is automatically created every time translation files are written. Backups are stored in `lang/.backup/` with a timestamp directory name (e.g. `2026-04-16_14-30-00`).

The number of backups to keep is controlled by the config:

```php
'backup' => [
    'enabled' => true,
    'keep'    => 5,    // Keep only the 5 most recent backups
],
```

### List Available Backups

```bash
php artisan lang:backup:list
```

```
📦 Available backups (3 total):

┌───────────────────┬───────┬─────────┬──────────┐
│ Timestamp         │ Files │ Size    │ Age      │
├───────────────────┼───────┼─────────┼──────────┤
│ 2026-04-16 14:30  │ 15    │ 45 KB   │ 5 min    │
│ 2026-04-16 14:25  │ 15    │ 44 KB   │ 10 min   │
│ 2026-04-16 14:20  │ 12    │ 38 KB   │ 15 min   │
└───────────────────┴───────┴─────────┴──────────┘
```

For a detailed view including file list:

```bash
php artisan lang:backup:list --details
```

### Restore From a Backup

**Interactive (recommended):**
```bash
php artisan lang:restore
```

The command shows the backup list, prompts you to choose, shows details, asks for confirmation, creates a safety backup of the current state, then restores.

**Restore the latest backup directly:**
```bash
php artisan lang:restore --latest
```

**Restore a specific backup by timestamp:**
```bash
php artisan lang:restore 2026-04-16_14-30-00
```

### Safety Backup

Before any restore, the command automatically creates a new backup named `pre-restore-{timestamp}`. This means you can always undo a restore by restoring again from the pre-restore backup.

### Best Practices

- Run `php artisan lang:backup:list` before any major translation run
- Use `--dry-run` to preview changes before committing
- Keep the `backup.keep` value at 5 or higher in production
- Add `lang/.backup/` to `.gitignore` to avoid committing large backup files

---

## Cost Optimization Tips

### 1. Use Change Tracking (Default Behavior)

The package tracks a hash of every translated key. On subsequent runs, only keys that have changed since the last translation are sent to the API. This alone saves 70–90% of API costs in active projects.

```bash
# First run: translates everything
php artisan lang:translate

# Second run: 0 API calls (nothing changed)
php artisan lang:translate
```

### 2. Preview Costs Before Translating

```bash
php artisan lang:translate --dry-run
```

The output shows total characters and estimated cost before you commit.

### 3. Translate One Language at a Time

If you're adding a new language, translate it alone to avoid spending on languages that are already complete:

```bash
php artisan lang:translate --lang=de
```

### 4. Avoid --force Unless Necessary

The `--force` flag re-translates every key regardless of whether it has changed. Use it only when you want to regenerate all translations (e.g., after changing the AI provider).

```bash
# Only use --force when you actually need it
php artisan lang:translate --force
```

### 5. Lock Reviewed Translations

Once a translation has been reviewed and is correct, lock it. This prevents accidental re-translation if `--force` is used:

```bash
php artisan lang:lock ar auth.login --reason="Native speaker approved"
```

### 6. Use DeepL Free Tier for Development

DeepL offers 500,000 characters/month for free — more than enough for a typical development workflow. Switch to a paid plan only for production volumes.

---

## Multi-Developer Workflow

### Recommended Setup

**1. Commit the source language only:**

```
lang/en/             ← commit this
lang/ar/             ← gitignore this (auto-generated)
lang/fr/             ← gitignore this (auto-generated)
lang/.backup/        ← gitignore this
lang/.translations-meta.json  ← commit this (change tracking)
lang/.locked-translations.json  ← commit this (lock definitions)
```

`.gitignore`:
```
lang/ar/
lang/fr/
lang/es/
lang/.backup/
```

**2. Run translation as a CI/CD step:**

```yaml
# .github/workflows/translate.yml
- name: Translate
  run: php artisan lang:translate --no-backup
  env:
    DEEPL_API_KEY: ${{ secrets.DEEPL_API_KEY }}
```

**3. Or run locally and commit the generated files:**

Some teams prefer to commit all language files. In this case:
- Run `php artisan lang:translate` locally
- Commit all `lang/` files
- The change tracker ensures only actual changes appear in the diff

### Handling Lock File Conflicts

The lock file (`lang/.locked-translations.json`) can cause merge conflicts if two developers lock different keys simultaneously. Resolve conflicts by keeping all locks from both branches:

```json
{
    "ar": {
        "auth.login": { ... },  // kept from branch A
        "auth.logout": { ... }  // kept from branch B
    }
}
```

### Validate in CI

Add a validation step to catch translation drift early:

```yaml
- name: Validate translations
  run: php artisan lang:validate --strict
```

This fails the pipeline if any translation is missing, has broken placeholders, or has HTML tag mismatches.

### Code Review Checklist

When reviewing PRs that add new translation keys:

- [ ] English source file updated in `lang/en/`
- [ ] Key uses proper dot notation (`file.key`)
- [ ] No hardcoded text in Blade (uses `__()`)
- [ ] Translation run locally or added to CI
- [ ] `lang:validate` passes
