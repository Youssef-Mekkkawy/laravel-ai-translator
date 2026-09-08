# Changelog

All notable changes to `laravel-ai-translator` are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- `lang:scan` — scan Blade views and display all translation keys with status
- `lang:translate` — translate all keys to configured languages with AI
- `lang:validate` — validate translation quality (missing keys, broken placeholders, HTML)
- `lang:lock` / `lang:unlock` — protect manual translations from being overwritten
- `lang:locked` — list all locked translations
- `lang:backup:list` — list available translation backups
- `lang:restore` — restore translations from a backup
- **OllamaTranslator** — local, free, offline translation via Ollama
- **DeepLTranslator** — high-quality cloud translation via DeepL API
- **Hash-based change tracking** — only translate what actually changed
- **Automatic backups** — snapshot before every translation run
- **Placeholder preservation** — `:name`, `:count`, `{0}` always kept intact
- **HTML tag preservation** — markup never broken by translation
- **Embedded dashboard** at `/ai-translator`
- Support for PHP 8.2+, Laravel 10.x , 11.x, 12.x and 13.x 
- 120+ tests with full coverage of core functionality

---

## [1.0.0] — TBD

First stable release.
