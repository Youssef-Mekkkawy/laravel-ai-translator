# FAQ

## Nothing is being translated — why?

**Check 1 — Ollama is running:**
```bash
curl http://localhost:11434/api/tags
```
If no response, start Ollama: `ollama serve`

**Check 2 — You have target languages configured:**
```bash
php artisan lang:scan
```
If it says "0 keys found", your views don't use `__()` or `@lang()`.

**Check 3 — Hash tracking:**
If you've translated before, unchanged keys are skipped. Force a re-translate:
```bash
php artisan lang:translate --force
```

---

## Why are some keys showing as "missing" even after translating?

The dashboard counts keys from your views AND from `lang/en.json` (if it exists). If you added new views after the last translation run, those keys will show as missing until you translate again.

---

## What's the difference between toggle and remove on the Languages page?

- **Toggle (switch)** — disables/enables the language. The translation files are kept. Disabled languages are skipped during translation.
- **Trash icon** — permanently removes the language from config AND deletes all its translation files. This cannot be undone.

---

## PHP files vs JSON files — which does my app use?

Laravel supports two translation file formats:

- **PHP files** (`lang/ar/auth.php`) — for dot-notation keys like `__('auth.login')`
- **JSON files** (`lang/ar.json`) — for full-string keys like `__('Login')`

The package detects which format to use automatically based on your key style. You can force one format with `AUTO_TRANSLATE_OUTPUT=php` or `AUTO_TRANSLATE_OUTPUT=json`.

---

## How do I add a language switcher to my app?

Add a route to `routes/web.php`:

```php
Route::get('/lang/{locale}', function (string $locale) {
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');
```

Add middleware `app/Http/Middleware/SetLocale.php`:

```php
public function handle(Request $request, Closure $next)
{
    if (session()->has('locale')) {
        app()->setLocale(session('locale'));
    }
    return $next($request);
}
```

Register it in `bootstrap/app.php`:

```php
$middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
```

---

## Can I use this with Filament, Livewire, or Inertia?

Yes. The package auto-detects your stack:

- **Blade** — scans `.blade.php` files
- **Livewire** — also scans Livewire component PHP files
- **Inertia + Vue** — also scans `.vue` files
- **Inertia + React** — also scans `.jsx` and `.tsx` files

Note: Filament uses its own translation system and doesn't use standard `__()` calls, so Filament UI strings cannot be scanned by this package.

---

## Translation quality is poor — what can I do?

1. **Use a better model** — `aya-expanse:8b` gives much better multilingual results than `llama3.2`
2. **Add context** — set a context prompt in Settings: `"This is a medical app. Use formal clinical language."`
3. **Lock good translations** — when you manually fix a translation, lock it to prevent it being overwritten

---

## How do I reset all translations and start fresh?

```bash
# Delete all translated language folders
php artisan lang:clean --force

# Delete metadata
rm lang/.translations-meta.json
rm lang/.ai-translator-runtime.json

# Start fresh
php artisan lang:translate --force
```
