---
layout: home

hero:
  name: "Laravel AI Translator"
  text: "ترجمة تلقائية لتطبيقات لارافيل"
  tagline: ترجم تطبيق لارافيل بالكامل باستخدام الذكاء الاصطناعي — مجانًا مع Ollama، أو عبر DeepL و Claude و ChatGPT و Gemini.
  image:
    src: /dashboard-overview.png
    alt: نظرة عامة على لوحة التحكم
  actions:
    - theme: brand
      text: ابدأ الآن
      link: /ar/installation
    - theme: alt
      text: عرض على GitHub
      link: https://github.com/Youssef-Mekkkawy/laravel-ai-translator

features:
  - icon: 🤖
    title: ترجمة مدعومة بالذكاء الاصطناعي
    details: ترجم باستخدام Ollama (مجاني ومحلي) أو DeepL أو Claude أو ChatGPT أو Gemini. لا حاجة لمفتاح API مع Ollama.

  - icon: 📊
    title: لوحة تحكم أنيقة
    details: أدر كل ترجماتك بصريًا من خلال /ai-translator. شاهد إحصاءات التغطية والسجل والمزيد.

  - icon: 🔒
    title: نظام القفل
    details: احمِ ترجمات معينة من الاستبدال أثناء عمليات الترجمة المستقبلية.

  - icon: 💾
    title: نسخ احتياطي واستعادة
    details: نسخ احتياطي تلقائي قبل كل عملية ترجمة. استعد أي حالة سابقة بنقرة واحدة.

  - icon: ⚡
    title: تتبع ذكي للتغييرات
    details: التتبع القائم على الـ hash يعني أن النصوص المتغيرة فقط هي التي تُترجم من جديد — مما يوفر تكاليف الـ API.

  - icon: 🌍
    title: دعم لارافيل 10-13
    details: يعمل مع لارافيل 10 و11 و12 و13. يدعم Blade وLivewire وInertia مع Vue/React.
---

## التثبيت السريع

```bash
composer require youssef-mekkkawy/laravel-ai-translator
php artisan ai-translator:install
```

ثم افتح المتصفح على `/ai-translator` للوصول إلى لوحة التحكم.

## مزودو الخدمة المدعومون

| المزود | التكلفة | يتطلب مفتاح API |
|---|---|---|
| **Ollama** | مجاني | لا (يعمل محليًا) |
| DeepL | باقة مجانية (500 ألف حرف/شهر) | نعم — قريبًا |
| Claude | مدفوع | نعم — قريبًا |
| ChatGPT | مدفوع | نعم — قريبًا |
| Gemini | باقة مجانية | نعم — قريبًا |

## المتطلبات

- PHP **8.2** أو أحدث
- لارافيل **10 أو 11 أو 12 أو 13**
- [Ollama](https://ollama.com) (للترجمة المحلية المجانية)
