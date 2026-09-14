# Commands Reference

## ai-translator:install

Interactive setup wizard. Run this once after installing the package.

```bash
php artisan ai-translator:install
```

Detects your stack, configures your `.env`, checks Ollama, and generates source language files automatically.

---

## lang:scan

Scan your views for translation keys.

```bash
php artisan lang:scan
```

Scans:
- `resources/views/**/*.blade.php` — Blade views
- `app/Livewire/**/*.php` — Livewire components (if detected)
- `resources/js/**/*.vue` — Vue files (if detected)
- `resources/js/**/*.jsx` / `.tsx` — React files (if detected)

---

## lang:translate

Translate all missing keys to your configured languages.

```bash
# Translate all languages
php artisan lang:translate

# Force re-translate everything (ignore hash tracking)
php artisan lang:translate --force

# Preview without writing files
php artisan lang:translate --dry-run

# Translate a specific language only
php artisan lang:translate --lang=ar
```

---

## lang:clean

Find and remove unused translation keys.

```bash
# Preview unused keys (safe — no changes)
php artisan lang:clean --dry-run

# Show and interactively delete unused keys
php artisan lang:clean

# Delete without confirmation
php artisan lang:clean --force
```

---

## lang:lock

Protect a translation key from being overwritten.

```bash
php artisan lang:lock
```

You'll be prompted for the language, key, and optional reason.

---

## lang:unlock

Remove a lock from a translation key.

```bash
php artisan lang:unlock
```

---

## lang:locked

List all currently locked keys.

```bash
php artisan lang:locked
```

---

## lang:validate

Validate translation quality — checks for missing placeholders, empty values, etc.

```bash
php artisan lang:validate
```

---

## lang:backup:list

List all available backups.

```bash
php artisan lang:backup:list
```

---

## lang:restore

Restore a previous backup.

```bash
php artisan lang:restore
```

You'll be shown a list of available backups to choose from.
