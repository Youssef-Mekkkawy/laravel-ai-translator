# مزودو الخدمة

## Ollama (مجاني، محلي)

شغّل ترجمة الذكاء الاصطناعي مجاناً تماماً على جهازك. لا يلزم مفتاح API.

```env
AUTO_TRANSLATE_DRIVER=ollama
OLLAMA_MODEL=llama3.2
OLLAMA_API_URL=http://localhost:11434
```

### النماذج الموصى بها

| النموذج | الحجم | الجودة | السرعة |
|---|---|---|---|
| `llama3.2` | 2GB | جيد | سريع |
| `aya-expanse:8b` | 4.8GB | ممتاز | متوسط |

---

## DeepL — قريباً

```env
AUTO_TRANSLATE_DRIVER=deepl
DEEPL_API_KEY=your-api-key-here
```

---

## Claude (Anthropic) — قريباً

```env
AUTO_TRANSLATE_DRIVER=claude
ANTHROPIC_API_KEY=your-api-key-here
```

---

## ChatGPT (OpenAI) — قريباً

```env
AUTO_TRANSLATE_DRIVER=openai
OPENAI_API_KEY=your-api-key-here
```

---

## Gemini (Google) — قريباً

```env
AUTO_TRANSLATE_DRIVER=gemini
GEMINI_API_KEY=your-api-key-here
```
