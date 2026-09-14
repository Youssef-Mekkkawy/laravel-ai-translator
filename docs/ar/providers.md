# مزودو الخدمة

يدعم Laravel AI Translator عدة مزودين للذكاء الاصطناعي. اختر المناسب لاحتياجاتك.

## Ollama (مجاني، محلي)

شغّل الترجمة بالذكاء الاصطناعي مجانًا بالكامل على جهازك. لا حاجة لمفتاح API.

**الأفضل لـ:** بيئة التطوير، التطبيقات الحساسة للخصوصية، الترجمة بدون أي تكلفة.

```env
AUTO_TRANSLATE_DRIVER=ollama
OLLAMA_MODEL=llama3.2
OLLAMA_API_URL=http://localhost:11434
```

### الإعداد

1. ثبّت Ollama من [ollama.com](https://ollama.com)
2. اسحب نموذجًا:

```bash
# سريع، جودة جيدة
ollama pull llama3.2

# جودة أفضل في دعم اللغات المتعددة (4.8 جيجابايت)
ollama pull aya-expanse:8b
```

3. يبدأ Ollama تلقائيًا عند استخدام لوحة التحكم (إذا كانت `OLLAMA_AUTO_START=true`)

### النماذج الموصى بها

| النموذج | الحجم | الجودة | السرعة |
|---|---|---|---|
| `llama3.2` | 2 جيجابايت | جيدة | سريعة |
| `llama3.2:1b` | 1.3 جيجابايت | أساسية | سريعة جدًا |
| `aya-expanse:8b` | 4.8 جيجابايت | ممتازة | متوسطة |
| `aya:8b` | 4.8 جيجابايت | ممتازة | متوسطة |

### التشغيل التلقائي

أضف إلى ملف `.env` لتشغيل Ollama تلقائيًا عند تحميل لوحة التحكم:

```env
OLLAMA_AUTO_START=true
```

---

## DeepL — قريبًا

ترجمة آلية كلاسيكية بجودة ممتازة. الباقة المجانية: 500,000 حرف/شهر.

```env
AUTO_TRANSLATE_DRIVER=deepl
DEEPL_API_KEY=your-api-key-here
DEEPL_PLAN=free
```

احصل على مفتاح API من [deepl.com/pro](https://www.deepl.com/pro#developer)

---

## Claude (من Anthropic) — قريبًا

ترجمة عالية الجودة بالذكاء الاصطناعي مع فهم ممتاز للسياق.

```env
AUTO_TRANSLATE_DRIVER=claude
ANTHROPIC_API_KEY=your-api-key-here
ANTHROPIC_MODEL=claude-sonnet-4-5
```

احصل على مفتاح API من [console.anthropic.com](https://console.anthropic.com)

---

## ChatGPT (من OpenAI) — قريبًا

ترجمة مدعومة بـ GPT-4.

```env
AUTO_TRANSLATE_DRIVER=openai
OPENAI_API_KEY=your-api-key-here
OPENAI_MODEL=gpt-4o-mini
```

احصل على مفتاح API من [platform.openai.com](https://platform.openai.com)

---

## Gemini (من Google) — قريبًا

ترجمة Google بالذكاء الاصطناعي مع باقة مجانية سخية.

```env
AUTO_TRANSLATE_DRIVER=gemini
GEMINI_API_KEY=your-api-key-here
GEMINI_MODEL=gemini-1.5-flash
```

احصل على مفتاح API من [aistudio.google.com](https://aistudio.google.com)
