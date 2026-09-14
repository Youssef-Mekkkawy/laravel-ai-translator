# Installation

## Requirements

- PHP **8.2** or higher
- Laravel **10, 11, 12 or 13**
- [Ollama](https://ollama.com) (for free local translation — optional if using cloud providers)

## Install the Package

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

## Run the Install Wizard

```bash
php artisan ai-translator:install
```

The wizard will:

1. **Publish the config file** to `config/ai-translator.php`
2. **Detect your stack** (Blade, Livewire, Inertia + Vue/React)
3. **Ask which AI provider** you want to use
4. **Ask which languages** to translate into
5. **Update your `.env`** with the correct settings
6. **Check Ollama** is running (if selected)
7. **Generate source language files** from your views

## Setup Ollama (Free Provider)

If you chose Ollama, install it from [ollama.com](https://ollama.com) and pull a model:

```bash
ollama pull llama3.2
```

Or for better multilingual quality:

```bash
ollama pull aya-expanse:8b
```

## Verify Installation

Open your browser at:

```
http://your-app.test/ai-translator
```

You should see the dashboard with your app's translation stats.

## Manual Configuration

If you prefer to configure manually, publish the config:

```bash
php artisan vendor:publish --tag=ai-translator-config
```

Then add to your `.env`:

```env
AUTO_TRANSLATE_DRIVER=ollama
SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
OLLAMA_MODEL=llama3.2
OLLAMA_API_URL=http://localhost:11434
```
