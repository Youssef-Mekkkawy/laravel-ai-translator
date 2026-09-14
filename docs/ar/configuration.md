# الإعدادات

## مرجع متغيرات .env

| المتغير | الافتراضي | الوصف |
|---|---|---|
| `AUTO_TRANSLATE_DRIVER` | `ollama` | المزود النشط |
| `SUPPORTED_LANGUAGES` | `en,ar,fr,es` | اللغات المدعومة |
| `DEFAULT_LANGUAGE` | `en` | لغة المصدر |
| `AUTO_TRANSLATE_OUTPUT` | `auto` | تنسيق الإخراج: `auto` أو `php` أو `json` |
| `AUTO_TRANSLATE_CHUNK_SIZE` | `50` | المفاتيح لكل طلب API |
| `OLLAMA_MODEL` | `llama3.2` | نموذج Ollama |
| `OLLAMA_API_URL` | `http://localhost:11434` | نقطة نهاية Ollama |
| `OLLAMA_AUTO_START` | `false` | تشغيل Ollama تلقائياً |
