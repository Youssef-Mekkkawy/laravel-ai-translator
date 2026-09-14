---
layout: home

hero:
  name: "Laravel AI Translator"
  text: "ترجمة تلقائية لتطبيقات Laravel"
  tagline: ترجم تطبيق Laravel الخاص بك باستخدام الذكاء الاصطناعي — مجاناً مع Ollama، أو عبر DeepL وClaude وChatGPT وGemini.
  actions:
    - theme: brand
      text: ابدأ الآن
      link: /ar/installation
    - theme: alt
      text: GitHub
      link: https://github.com/Youssef-Mekkkawy/laravel-ai-translator

features:
  - icon: 🤖
    title: ترجمة بالذكاء الاصطناعي
    details: ترجم باستخدام Ollama (مجاني ومحلي) أو DeepL أو Claude أو ChatGPT أو Gemini. لا حاجة لمفتاح API مع Ollama.

  - icon: 📊
    title: لوحة تحكم جميلة
    details: أدر جميع ترجماتك بصرياً من /ai-translator. شاهد إحصائيات التغطية والتاريخ وغير ذلك.

  - icon: 🔒
    title: نظام القفل
    details: احمِ ترجمات محددة من الكتابة فوقها في عمليات الترجمة المستقبلية.

  - icon: 💾
    title: النسخ الاحتياطي والاستعادة
    details: نسخ احتياطية تلقائية قبل كل عملية ترجمة. استعد أي حالة سابقة بنقرة واحدة.

  - icon: ⚡
    title: تتبع التغييرات الذكي
    details: التتبع المبني على Hash يعني ترجمة النصوص المتغيرة فقط — مما يوفر تكاليف API.

  - icon: 🌍
    title: دعم Laravel 10-13
    details: يعمل مع Laravel 10 و11 و12 و13. يدعم Blade وLivewire وInertia مع Vue/React.
---

## التثبيت السريع

```bash
composer require youssef-mekkkawy/laravel-ai-translator
php artisan ai-translator:install
```

ثم افتح المتصفح على `/ai-translator` للوصول إلى لوحة التحكم.
