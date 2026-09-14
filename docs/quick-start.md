# Quick Start

Get your Laravel app translated in 3 steps.

## Step 1 — Scan Your Views

Find all translation keys used in your app:

```bash
php artisan lang:scan
```

This scans your Blade views, Livewire components, and Vue/React files (depending on your stack) and reports how many keys were found.

## Step 2 — Translate

Translate all missing keys to your configured languages:

```bash
php artisan lang:translate
```

The command will:
- Show estimated cost (free with Ollama)
- Ask for confirmation
- Translate all keys
- Show a summary of what was translated

## Step 3 — Open the Dashboard

```
http://your-app.test/ai-translator
```

You'll see your translation coverage, recent history, and quick actions.

## Using the Dashboard

Instead of the CLI, you can do everything from the dashboard:

1. Click **Scan** to find new keys
2. Click **Translate** to start translation
3. Watch the real-time progress bar
4. See coverage by language in the chart below

## Add More Languages

From the Languages page, click **+ Add language** and pick from 180+ languages. The package will start translating into the new language on the next run.

## Next Steps

- [All available commands →](/commands)
- [Dashboard overview →](/dashboard)
- [Configure providers →](/providers)
