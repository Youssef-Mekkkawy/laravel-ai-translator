# التثبيت

## المتطلبات

- PHP **8.2** أو أعلى
- Laravel **10 أو 11 أو 12 أو 13**
- [Ollama](https://ollama.com) (للترجمة المجانية المحلية — اختياري مع مزودي السحابة)

## تثبيت الحزمة

```bash
composer require youssef-mekkkawy/laravel-ai-translator
```

## تشغيل معالج التثبيت

```bash
php artisan ai-translator:install
```

سيقوم المعالج بـ:

1. **نشر ملف الإعداد** إلى `config/ai-translator.php`
2. **اكتشاف Stack الخاص بك** (Blade أو Livewire أو Inertia + Vue/React)
3. **اختيار مزود الذكاء الاصطناعي** الذي تريد استخدامه
4. **اختيار اللغات** للترجمة إليها
5. **تحديث `.env`** بالإعدادات الصحيحة
6. **التحقق من Ollama** (إذا تم اختياره)
7. **إنشاء ملفات اللغة المصدر** من views الخاصة بك

## إعداد Ollama (مزود مجاني)

إذا اخترت Ollama، قم بتثبيته من [ollama.com](https://ollama.com) وسحب نموذج:

```bash
ollama pull llama3.2
```

أو لجودة متعددة اللغات أفضل:

```bash
ollama pull aya-expanse:8b
```

## التحقق من التثبيت

افتح المتصفح على:

```
http://your-app.test/ai-translator
```

يجب أن ترى لوحة التحكم مع إحصائيات الترجمة.
