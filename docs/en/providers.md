# AI Providers

Laravel AI Translator supports multiple AI providers. Switch between them any time from the dashboard or `.env`.

## Comparison

| Provider | Quality | Speed | Cost | Offline | API Key |
|---|---|---|---|---|---|
| **Ollama** | ⭐⭐⭐⭐ | ⭐⭐⭐ | Free | ✅ Yes | ❌ No |
| **DeepL** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Free tier (500k chars/month) | ❌ No | ✅ Yes |
| **Claude** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Paid | ❌ No | ✅ Yes |
| **ChatGPT** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Paid | ❌ No | ✅ Yes |
| **Gemini** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Free tier available | ❌ No | ✅ Yes |

> **Recommended:** Start with **Ollama** for development (free, private, no API key). Switch to **DeepL** for production (fast, high quality, free tier).

---

## Ollama (Local, Free)

Runs entirely on your machine. No API key, no cost, no data sent anywhere.

### Setup

1. Install from [ollama.com](https://ollama.com)
2. Pull a model:

```bash
ollama pull llama3.2
```

3. Configure:

```env
AUTO_TRANSLATE_DRIVER=ollama
OLLAMA_MODEL=llama3.2
OLLAMA_API_URL=http://localhost:11434
```

### Models

| Model | Size | Speed | Quality |
|---|---|---|---|
| `llama3.2` | 2GB | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| `llama3.2:1b` | 800MB | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| `aya-expanse:8b` | 5GB | ⭐⭐ | ⭐⭐⭐⭐⭐ |

> `aya-expanse` is specifically trained for multilingual tasks — best quality for non-English languages.

### Speed note

Ollama speed depends on your hardware. With a GPU (NVIDIA/AMD), translation is 5–10× faster than CPU-only.

---

## DeepL

High-quality neural machine translation. Free tier includes 500,000 characters per month.

### Setup

1. Get a free API key from [deepl.com/api](https://www.deepl.com/api)
2. Configure:

```env
AUTO_TRANSLATE_DRIVER=deepl
DEEPL_API_KEY=your-key-here
DEEPL_PLAN=free
```

### Notes

- Best translation quality for European languages
- Extremely fast — 149 keys in ~10 seconds
- Free tier: 500k characters/month (~50 average-sized projects)

---

## Claude (Anthropic)

High-quality AI translation with good context understanding.

```env
AUTO_TRANSLATE_DRIVER=claude
ANTHROPIC_API_KEY=your-key-here
ANTHROPIC_MODEL=claude-sonnet-4-5
```

---

## ChatGPT (OpenAI)

```env
AUTO_TRANSLATE_DRIVER=openai
OPENAI_API_KEY=your-key-here
OPENAI_MODEL=gpt-4o-mini
```

> Use `gpt-4o-mini` for cost efficiency. It offers good translation quality at a fraction of the cost of `gpt-4o`.

---

## Gemini (Google)

```env
AUTO_TRANSLATE_DRIVER=gemini
GEMINI_API_KEY=your-key-here
GEMINI_MODEL=gemini-1.5-flash
```

---

## Switching providers

You can switch providers any time from the **Settings** page in the dashboard — no restart needed. The package uses `RuntimeConfig` to store the active provider, so changes take effect immediately.

Or update your `.env` and run:

```bash
php artisan config:clear
```
