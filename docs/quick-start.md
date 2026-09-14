# Quick Start

Get your Laravel app translated in under 5 minutes.

## 1. Install the package

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

## 2. Run the install wizard

```bash
php artisan ai-translator:install
```

The wizard will ask you:
- Which **AI provider** to use (start with Ollama — free and local)
- Which **languages** to translate into (e.g. `ar,fr,es`)

It then updates your `.env`, detects your stack, and scans your views automatically.

## 3. Translate

```bash
php artisan lang:translate
```

Translation runs in the **background** — your site stays live while it works. With Ollama this takes a few minutes. With DeepL it takes seconds.

## 4. Open the dashboard

```
http://your-app.test/ai-translator
```

You'll see your coverage stats, history, and all language files.

---

## That's it

Your app is now multilingual. Next time you add new strings, just run:

```bash
php artisan lang:translate
```

Only the **changed** strings get re-translated — no wasted API calls.

---

## What gets generated

```
lang/
├── en/auth.php          ← your original
├── ar/auth.php          ← auto-generated ✅
├── ar.json              ← auto-generated ✅ (for JSON keys)
├── fr/auth.php          ← auto-generated ✅
└── fr.json              ← auto-generated ✅
```

---

## Next steps

- [Commands reference](/commands) — all available artisan commands
- [Dashboard](/dashboard) — manage translations visually
- [Providers](/providers) — compare AI providers
- [Configuration](/configuration) — full config reference
