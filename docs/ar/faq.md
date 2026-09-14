# الأسئلة الشائعة

## لا يوجد أي ترجمة تحدث — ما السبب؟

**تحقق 1 — Ollama يعمل:**
```bash
curl http://localhost:11434/api/tags
```
إذا لم يكن هناك استجابة، شغّل Ollama: `ollama serve`

**تحقق 2 — لديك لغات هدف مُعدة:**
```bash
php artisan lang:scan
```
إذا ظهرت رسالة "0 keys found"، فهذا يعني أن الـ views الخاصة بك لا تستخدم `__()` أو `@lang()`.

**تحقق 3 — تتبع الـ Hash:**
إذا كنت قد ترجمت من قبل، يتم تجاوز المفاتيح غير المتغيرة. لإجبار إعادة الترجمة:
```bash
php artisan lang:translate --force
```

---

## لماذا تظهر بعض المفاتيح كـ "مفقودة" حتى بعد الترجمة؟

تحسب لوحة التحكم المفاتيح من الـ views الخاصة بك ومن `lang/en.json` (إذا كان موجودًا). إذا أضفت views جديدة بعد آخر عملية ترجمة، ستظهر تلك المفاتيح كمفقودة حتى تترجم مرة أخرى.

---

## ما الفرق بين Toggle وRemove في صفحة اللغات؟

- **Toggle (مفتاح التبديل)** — يعطّل/يفعّل اللغة. ملفات الترجمة تبقى محفوظة. اللغات المُعطّلة يتم تجاوزها أثناء الترجمة.
- **أيقونة سلة المهملات** — تحذف اللغة نهائيًا من الإعدادات وتحذف كل ملفات ترجمتها. لا يمكن التراجع عن هذا.

---

## ملفات PHP مقابل ملفات JSON — أيهما يستخدمه تطبيقي؟

يدعم لارافيل صيغتين لملفات الترجمة:

- **ملفات PHP** (`lang/ar/auth.php`) — لمفاتيح النقطة مثل `__('auth.login')`
- **ملفات JSON** (`lang/ar.json`) — للمفاتيح النصية الكاملة مثل `__('Login')`

تكتشف الحزمة الصيغة المناسبة تلقائيًا بناءً على أسلوب مفاتيحك. يمكنك فرض صيغة معينة باستخدام `AUTO_TRANSLATE_OUTPUT=php` أو `AUTO_TRANSLATE_OUTPUT=json`.

---

## كيف أضيف مبدّل لغة إلى تطبيقي؟

أضف مسارًا (route) إلى `routes/web.php`:

```php
Route::get('/lang/{locale}', function (string $locale) {
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');
```

أضف middleware في `app/Http/Middleware/SetLocale.php`:

```php
public function handle(Request $request, Closure $next)
{
    if (session()->has('locale')) {
        app()->setLocale(session('locale'));
    }
    return $next($request);
}
```

سجّله في `bootstrap/app.php`:

```php
$middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
```

---

## هل يمكنني استخدام هذا مع Filament أو Livewire أو Inertia؟

نعم. تكتشف الحزمة تقنيتك تلقائيًا:

- **Blade** — يفحص ملفات `.blade.php`
- **Livewire** — يفحص أيضًا ملفات PHP لمكونات Livewire
- **Inertia + Vue** — يفحص أيضًا ملفات `.vue`
- **Inertia + React** — يفحص أيضًا ملفات `.jsx` و `.tsx`

ملاحظة: يستخدم Filament نظام ترجمة خاصًا به ولا يستخدم استدعاءات `__()` القياسية، لذلك لا يمكن لهذه الحزمة فحص نصوص واجهة Filament.

---

## جودة الترجمة ضعيفة — ماذا يمكنني أن أفعل؟

1. **استخدم نموذجًا أفضل** — `aya-expanse:8b` يعطي نتائج أفضل بكثير في اللغات المتعددة مقارنة بـ `llama3.2`
2. **أضف سياقًا** — اضبط سياق الترجمة في الإعدادات: `"هذا تطبيق طبي. استخدم لغة سريرية رسمية."`
3. **اقفل الترجمات الجيدة** — عندما تصحح ترجمة يدويًا، اقفلها لمنع استبدالها

---

## كيف أعيد ضبط كل الترجمات وأبدأ من جديد؟

```bash
# احذف كل مجلدات اللغة المُترجمة
php artisan lang:clean --force

# احذف البيانات الوصفية (metadata)
rm lang/.translations-meta.json
rm lang/.ai-translator-runtime.json

# ابدأ من جديد
php artisan lang:translate --force
```
