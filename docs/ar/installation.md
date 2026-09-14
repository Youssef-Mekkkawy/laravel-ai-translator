# التثبيت

## المتطلبات

- PHP **8.2** أو أحدث
- لارافيل **10 أو 11 أو 12 أو 13**
- [Ollama](https://ollama.com) (للترجمة المحلية المجانية — اختياري إذا كنت تستخدم مزودي الخدمة السحابيين)

## تثبيت الحزمة

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

## تشغيل معالج التثبيت

```bash
php artisan ai-translator:install
```

سيقوم المعالج بما يلي:

1. **نشر ملف الإعدادات** إلى `config/ai-translator.php`
2. **اكتشاف الحزمة التقنية** المستخدمة (Blade أو Livewire أو Inertia مع Vue/React)
3. **سؤالك عن مزود الذكاء الاصطناعي** الذي تريد استخدامه
4. **سؤالك عن اللغات** التي تريد الترجمة إليها
5. **تحديث ملف `.env`** بالإعدادات الصحيحة
6. **التحقق من تشغيل Ollama** (إذا تم اختياره)
7. **توليد ملفات اللغة المصدر** من الـ views الخاصة بك

## إعداد Ollama (المزود المجاني)

إذا اخترت Ollama، قم بتثبيته من [ollama.com](https://ollama.com) ثم اسحب نموذجًا:

```bash
ollama pull llama3.2
```

أو للحصول على جودة أفضل في دعم اللغات المتعددة:

```bash
ollama pull aya-expanse:8b
```

## التحقق من التثبيت

افتح المتصفح على:

```
http://your-app.test/ai-translator
```

يجب أن تشاهد لوحة التحكم مع إحصاءات ترجمة تطبيقك.

## الإعداد اليدوي

إذا كنت تفضل الإعداد يدويًا، انشر ملف الإعدادات:

```bash
php artisan vendor:publish --tag=ai-translator-config
```

ثم أضف إلى ملف `.env`:

```env
AUTO_TRANSLATE_DRIVER=ollama
SUPPORTED_LANGUAGES=en,ar,fr,es
DEFAULT_LANGUAGE=en
OLLAMA_MODEL=llama3.2
OLLAMA_API_URL=http://localhost:11434
```
