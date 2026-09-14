# مرجع الأوامر

## ai-translator:install

معالج إعداد تفاعلي. شغّل هذا الأمر مرة واحدة بعد تثبيت الحزمة.

```bash
php artisan ai-translator:install
```

يكتشف الحزمة التقنية المستخدمة، ويضبط ملف `.env`، ويتحقق من Ollama، ويولّد ملفات اللغة المصدر تلقائيًا.

---

## lang:scan

افحص الـ views الخاصة بك بحثًا عن مفاتيح الترجمة.

```bash
php artisan lang:scan
```

يفحص:
- `resources/views/**/*.blade.php` — ملفات Blade
- `app/Livewire/**/*.php` — مكونات Livewire (إذا تم اكتشافها)
- `resources/js/**/*.vue` — ملفات Vue (إذا تم اكتشافها)
- `resources/js/**/*.jsx` / `.tsx` — ملفات React (إذا تم اكتشافها)

---

## lang:translate

ترجم كل المفاتيح المفقودة إلى اللغات المُعدة لديك.

```bash
# ترجمة كل اللغات
php artisan lang:translate

# إعادة ترجمة كل شيء بالإجبار (تجاهل تتبع الـ hash)
php artisan lang:translate --force

# معاينة بدون كتابة الملفات
php artisan lang:translate --dry-run

# ترجمة لغة محددة فقط
php artisan lang:translate --lang=ar
```

---

## lang:clean

ابحث عن مفاتيح الترجمة غير المستخدمة واحذفها.

```bash
# معاينة المفاتيح غير المستخدمة (آمن — بدون تغييرات)
php artisan lang:clean --dry-run

# عرض المفاتيح غير المستخدمة وحذفها تفاعليًا
php artisan lang:clean

# حذف بدون تأكيد
php artisan lang:clean --force
```

---

## lang:lock

احمِ مفتاح ترجمة من الاستبدال.

```bash
php artisan lang:lock
```

سيُطلب منك إدخال اللغة والمفتاح وسبب اختياري.

---

## lang:unlock

أزل القفل عن مفتاح ترجمة.

```bash
php artisan lang:unlock
```

---

## lang:locked

اعرض كل المفاتيح المقفلة حاليًا.

```bash
php artisan lang:locked
```

---

## lang:validate

تحقق من جودة الترجمة — يفحص المتغيرات (placeholders) المفقودة والقيم الفارغة وغير ذلك.

```bash
php artisan lang:validate
```

---

## lang:backup:list

اعرض كل النسخ الاحتياطية المتاحة.

```bash
php artisan lang:backup:list
```

---

## lang:restore

استعد نسخة احتياطية سابقة.

```bash
php artisan lang:restore
```

ستظهر لك قائمة بالنسخ الاحتياطية المتاحة للاختيار من بينها.
