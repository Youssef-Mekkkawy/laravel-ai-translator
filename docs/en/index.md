---
layout: home

hero:
  name: "Laravel AI Translator"
  text: "Automatic translation for Laravel apps"
  tagline: Translate your entire Laravel application using AI — free with Ollama, or via DeepL, Claude, ChatGPT and Gemini.
  image:
    src: /logo-dark.png
    alt: Dashboard Overview
  actions:
    - theme: brand
      text: Get Started
      link: /installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/Youssef-Mekkkawy/laravel-ai-translator

features:
  - icon: 🤖
    title: AI-Powered Translation
    details: Translate using Ollama (free, local), DeepL, Claude, ChatGPT or Gemini. No API key needed with Ollama.

  - icon: 📊
    title: Beautiful Dashboard
    details: Manage all your translations visually at /ai-translator. See coverage stats, history and more.

  - icon: 🔒
    title: Lock System
    details: Protect specific translations from being overwritten during future translation runs.

  - icon: 💾
    title: Backup & Restore
    details: Automatic backups before every translation run. Restore any previous state in one click.

  - icon: ⚡
    title: Smart Change Tracking
    details: Hash-based tracking means only changed strings get re-translated — saving API costs.

  - icon: 🌍
    title: Laravel 10-13 Support
    details: Works with Laravel 10, 11, 12 and 13. Supports Blade, Livewire, Inertia + Vue/React.
---

## Quick Install

```bash
composer require youssef-mekkkawy/laravel-ai-translator
php artisan ai-translator:install
```

Then open your browser at `/ai-translator` to access the dashboard.

## Supported Providers

| Provider | Cost | Requires API Key |
|---|---|---|
| **Ollama** | Free | No (runs locally) |
| DeepL | Free tier (500k chars/month) | Yes — coming soon |
| Claude | Paid | Yes — coming soon |
| ChatGPT | Paid | Yes — coming soon |
| Gemini | Free tier | Yes — coming soon |

## Requirements

- PHP **8.2** or higher
- Laravel **10, 11, 12 or 13**
- [Ollama](https://ollama.com) (for free local translation)

