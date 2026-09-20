# Configuration

After running `php artisan vendor:publish --tag=ai-translator-config`, you'll find the config at `config/ai-translator.php`.

## .env Reference

| Variable | Default | Description |
|---|---|---|
| `AUTO_TRANSLATE_DRIVER` | `ollama` | Active provider: `ollama`, `deepl`, `claude`, `openai`, `gemini` |
| `SUPPORTED_LANGUAGES` | `en,ar,fr,es` | Comma-separated list of language codes |
| `DEFAULT_LANGUAGE` | `en` | Source language (the language your app is written in) |
| `AUTO_TRANSLATE_OUTPUT` | `auto` | Output format: `auto`, `php`, `json` |
| `AUTO_TRANSLATE_CHUNK_SIZE` | `50` | Keys per API request |
| `AUTO_TRANSLATE_BACKUP` | `true` | Auto-backup before each translation run |
| `AUTO_TRANSLATE_BACKUP_KEEP` | `5` | Number of backups to keep |
| `AUTO_TRANSLATE_EXCLUDE_FILES` | `vendor/**,...` | Glob patterns to exclude from scanning |
| `OLLAMA_MODEL` | `llama3.2` | Ollama model to use |
| `OLLAMA_API_URL` | `http://localhost:11434` | Ollama API endpoint |
| `OLLAMA_AUTO_START` | `false` | Auto-start Ollama when dashboard loads |

## Output Format

Control how translation files are written:

```env
AUTO_TRANSLATE_OUTPUT=auto   # Decide per key (default)
AUTO_TRANSLATE_OUTPUT=php    # Force PHP files (lang/ar/auth.php)
AUTO_TRANSLATE_OUTPUT=json   # Force JSON files (lang/ar.json)
```

**auto** — dot-notation keys (`auth.login`) go to PHP files, full-string keys (`Login`) go to JSON files.

## Runtime Config

The package stores runtime settings in `lang/.ai-translator-runtime.json`. This file is updated by the dashboard without restarting your dev server.

Add it to your `.gitignore`:

```
lang/.ai-translator-runtime.json
lang/.translations-meta.json
lang/.backup/
```

## Dashboard Path

Change the dashboard URL (default: `/ai-translator`):

```php
// config/ai-translator.php
'dashboard' => [
    'enabled' => true,
    'path'    => 'ai-translator', // change this
],
```

## Context Prompt

Give the AI context about your application for better translations:

```env
AUTO_TRANSLATE_CONTEXT="This is an e-commerce app selling clothes. Use formal language."
```

Or set it from the Settings page in the dashboard.
