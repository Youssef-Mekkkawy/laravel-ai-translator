import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Laravel AI Translator',
  description: 'Automatic AI-powered translation for Laravel applications',
  base: '/laravel-ai-translator/',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/laravel-ai-translator/logo.svg' }],
    ['meta', { name: 'theme-color', content: '#6EE7B7' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Laravel AI Translator' }],
    ['meta', { property: 'og:description', content: 'Automatic AI-powered translation for Laravel applications' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'AI Translator',

    nav: [
      { text: 'Guide', link: '/installation' },
      { text: 'Commands', link: '/commands' },
      { text: 'Dashboard', link: '/dashboard' },
      { text: 'Config', link: '/configuration' },
      {
        text: 'v1.0.13',
        items: [
          { text: 'Changelog', link: 'https://github.com/Youssef-Mekkkawy/laravel-ai-translator/releases' },
          { text: 'GitHub', link: 'https://github.com/Youssef-Mekkkawy/laravel-ai-translator' },
          { text: 'Packagist', link: 'https://packagist.org/packages/youssef-mekkkawy/laravel-ai-translator' },
        ],
      },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/' },
          { text: 'Installation', link: '/installation' },
          { text: 'Quick Start', link: '/quick-start' },
        ],
      },
      {
        text: 'Usage',
        items: [
          { text: 'Commands', link: '/commands' },
          { text: 'Dashboard', link: '/dashboard' },
          { text: 'Providers', link: '/providers' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'Configuration', link: '/configuration' },
          { text: 'FAQ', link: '/faq' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/Youssef-Mekkkawy/laravel-ai-translator' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2026 Youssef Mekkkawy',
    },

    editLink: {
      pattern: 'https://github.com/Youssef-Mekkkawy/laravel-ai-translator/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },
  },

  locales: {
    root: {
      label: 'English',
      lang: 'en',
    },
    ar: {
      label: 'العربية',
      lang: 'ar',
      dir: 'rtl',
      link: '/ar/',
      themeConfig: {
        nav: [
          { text: 'الدليل', link: '/ar/installation' },
          { text: 'الأوامر', link: '/ar/commands' },
          { text: 'لوحة التحكم', link: '/ar/dashboard' },
        ],
        sidebar: [
          {
            text: 'البداية',
            items: [
              { text: 'مقدمة', link: '/ar/' },
              { text: 'التثبيت', link: '/ar/installation' },
              { text: 'البدء السريع', link: '/ar/quick-start' },
            ],
          },
          {
            text: 'الاستخدام',
            items: [
              { text: 'الأوامر', link: '/ar/commands' },
              { text: 'لوحة التحكم', link: '/ar/dashboard' },
              { text: 'مزودو الخدمة', link: '/ar/providers' },
            ],
          },
          {
            text: 'المرجع',
            items: [
              { text: 'الإعدادات', link: '/ar/configuration' },
              { text: 'الأسئلة الشائعة', link: '/ar/faq' },
            ],
          },
        ],
      },
    },
  },
})
