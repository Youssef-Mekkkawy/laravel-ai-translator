# الإعدادات

بعد تشغيل `php artisan vendor:publish --tag=ai-translator-config`، ستجد ملف الإعدادات في `config/ai-translator.php`.

## مرجع ملف .env

| المتغير | الافتراضي | الوصف |
|---|---|---|
| `AUTO_TRANSLATE_DRIVER` | `ollama` | المزود النشط: `ollama`، `deepl`، `claude`، `openai`، `gemini` |
| `SUPPORTED_LANGUAGES` | `en,ar,fr,es` | قائمة رموز اللغات مفصولة بفواصل |
| `DEFAULT_LANGUAGE` | `en` | اللغة المصدر (اللغة التي كُتب بها تطبيقك) |
| `AUTO_TRANSLATE_OUTPUT` | `auto` | صيغة الإخراج: `auto`، `php`، `json` |
| `AUTO_TRANSLATE_CHUNK_SIZE` | `50` | عدد المفاتيح في كل طلب API |
| `AUTO_TRANSLATE_BACKUP` | `true` | نسخ احتياطي تلقائي قبل كل عملية ترجمة |
| `AUTO_TRANSLATE_BACKUP_KEEP` | `5` | عدد النسخ الاحتياطية المُحتفظ بها |
| `AUTO_TRANSLATE_EXCLUDE_FILES` | `vendor/**,...` | أنماط glob لاستبعادها من الفحص |
| `OLLAMA_MODEL` | `llama3.2` | نموذج Ollama المستخدم |
| `OLLAMA_API_URL` | `http://localhost:11434` | رابط API الخاص بـ Ollama |
| `OLLAMA_AUTO_START` | `false` | تشغيل Ollama تلقائيًا عند تحميل لوحة التحكم |

## صيغة الإخراج

تحكم في طريقة كتابة ملفات الترجمة:

```env
AUTO_TRANSLATE_OUTPUT=auto   # يقرر حسب كل مفتاح (الافتراضي)
AUTO_TRANSLATE_OUTPUT=php    # فرض ملفات PHP (lang/ar/auth.php)
AUTO_TRANSLATE_OUTPUT=json   # فرض ملفات JSON (lang/ar.json)
```

**auto** — مفاتيح النقطة (`auth.login`) تذهب إلى ملفات PHP، والمفاتيح النصية الكاملة (`Login`) تذهب إلى ملفات JSON.

## إعدادات وقت التشغيل

تحفظ الحزمة إعدادات وقت التشغيل في `lang/.ai-translator-runtime.json`. يتم تحديث هذا الملف من لوحة التحكم بدون الحاجة لإعادة تشغيل خادم التطوير.

أضفه إلى ملف `.gitignore`:

```
lang/.ai-translator-runtime.json
lang/.translations-meta.json
lang/.backup/
```

## مسار لوحة التحكم

غيّر رابط لوحة التحكم (الافتراضي: `/ai-translator`):

```php
// config/ai-translator.php
'dashboard' => [
    'enabled' => true,
    'path'    => 'ai-translator', // غيّر هذا
],
```

## سياق الترجمة (Context Prompt)

أعطِ الذكاء الاصطناعي سياقًا عن تطبيقك لترجمة أفضل:

```env
AUTO_TRANSLATE_CONTEXT="This is an e-commerce app selling clothes. Use formal language."
```

أو اضبطه من صفحة الإعدادات في لوحة التحكم.
