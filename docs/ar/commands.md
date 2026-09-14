# مرجع الأوامر

## ai-translator:install

معالج الإعداد التفاعلي. شغّله مرة واحدة بعد تثبيت الحزمة.

```bash
php artisan ai-translator:install
```

---

## lang:scan

فحص views للبحث عن مفاتيح الترجمة.

```bash
php artisan lang:scan
```

---

## lang:translate

ترجمة جميع المفاتيح المفقودة إلى اللغات المُعدَّة.

```bash
# ترجمة جميع اللغات
php artisan lang:translate

# إعادة الترجمة بالقوة (تجاهل التتبع)
php artisan lang:translate --force

# معاينة بدون حفظ الملفات
php artisan lang:translate --dry-run

# ترجمة لغة محددة فقط
php artisan lang:translate --lang=ar
```

---

## lang:clean

البحث عن مفاتيح الترجمة غير المستخدمة وإزالتها.

```bash
# معاينة المفاتيح غير المستخدمة (آمن)
php artisan lang:clean --dry-run

# عرض وحذف المفاتيح تفاعلياً
php artisan lang:clean

# حذف بدون تأكيد
php artisan lang:clean --force
```

---

## lang:lock

حماية مفتاح ترجمة من الكتابة فوقه.

```bash
php artisan lang:lock
```

---

## lang:unlock

إزالة القفل من مفتاح ترجمة.

```bash
php artisan lang:unlock
```

---

## lang:locked

عرض قائمة بجميع المفاتيح المقفلة حالياً.

```bash
php artisan lang:locked
```

---

## lang:validate

التحقق من جودة الترجمة.

```bash
php artisan lang:validate
```

---

## lang:backup:list

عرض قائمة بجميع النسخ الاحتياطية المتاحة.

```bash
php artisan lang:backup:list
```

---

## lang:restore

استعادة نسخة احتياطية سابقة.

```bash
php artisan lang:restore
```
