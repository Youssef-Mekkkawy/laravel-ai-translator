# Providers

Laravel AI Translator supports multiple AI providers. Choose the one that fits your needs.

## Ollama (Free, Local)

Run AI translation completely free on your own machine. No API key required.

**Best for:** Development, privacy-sensitive apps, zero-cost translation.

```env
AUTO_TRANSLATE_DRIVER=ollama
OLLAMA_MODEL=llama3.2
OLLAMA_API_URL=http://localhost:11434
```

### Setup

1. Install Ollama from [ollama.com](https://ollama.com)
2. Pull a model:

```bash
# Fast, good quality
ollama pull llama3.2

# Better multilingual quality (4.8GB)
ollama pull aya-expanse:8b
```

3. Ollama starts automatically when you use the dashboard (if `OLLAMA_AUTO_START=true`)

### Recommended Models

| Model | Size | Quality | Speed |
|---|---|---|---|
| `llama3.2` | 2GB | Good | Fast |
| `llama3.2:1b` | 1.3GB | Basic | Very fast |
| `aya-expanse:8b` | 4.8GB | Excellent | Medium |
| `aya:8b` | 4.8GB | Excellent | Medium |

### Auto-Start

Add to your `.env` to start Ollama automatically when the dashboard loads:

```env
OLLAMA_AUTO_START=true
```

---

## DeepL — Coming Soon

Classic machine translation with excellent quality. Free tier: 500,000 characters/month.

```env
AUTO_TRANSLATE_DRIVER=deepl
DEEPL_API_KEY=your-api-key-here
DEEPL_PLAN=free
```

Get your API key at [deepl.com/pro](https://www.deepl.com/pro#developer)

---

## Claude (Anthropic) — Coming Soon

High quality AI translation with excellent context understanding.

```env
AUTO_TRANSLATE_DRIVER=claude
ANTHROPIC_API_KEY=your-api-key-here
ANTHROPIC_MODEL=claude-sonnet-4-5
```

Get your API key at [console.anthropic.com](https://console.anthropic.com)

---

## ChatGPT (OpenAI) — Coming Soon

GPT-4 powered translation.

```env
AUTO_TRANSLATE_DRIVER=openai
OPENAI_API_KEY=your-api-key-here
OPENAI_MODEL=gpt-4o-mini
```

Get your API key at [platform.openai.com](https://platform.openai.com)

---

## Gemini (Google) — Coming Soon

Google's AI translation with a generous free tier.

```env
AUTO_TRANSLATE_DRIVER=gemini
GEMINI_API_KEY=your-api-key-here
GEMINI_MODEL=gemini-1.5-flash
```

Get your API key at [aistudio.google.com](https://aistudio.google.com)
