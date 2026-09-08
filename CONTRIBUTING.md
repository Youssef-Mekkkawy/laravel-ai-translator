# Contributing to Laravel AI Translator

First off — thank you for taking the time to contribute! 🎉

This project is open source and built for the developer community. Every contribution, however small, is genuinely appreciated.

---

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Adding a New AI Provider](#adding-a-new-ai-provider)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Commit Messages](#commit-messages)

---

## Code of Conduct

Be respectful. Be constructive. Help each other. That's it.

---

## How Can I Contribute?

### 🐛 Report a Bug

Open an issue and include:

- **Laravel version** (e.g., 11.x)
- **PHP version** (e.g., 8.2)
- **Package version** (e.g., 1.0.3)
- **AI provider** being used (Ollama, DeepL, etc.)
- **Steps to reproduce**
- **Expected behavior vs actual behavior**
- **Error output / stack trace** if available

### 💡 Suggest a Feature

Open an issue with the `[Feature Request]` prefix. Include:

- What problem does this solve?
- Who would benefit from it?
- Any ideas on how it could work?

### 🌍 Add a New AI Provider

This is the most impactful contribution you can make. See [Adding a New AI Provider](#adding-a-new-ai-provider) below.

### 🌐 Improve Translations / Language Support

If you notice the AI produces poor translations for a specific language, open an issue with examples. We can improve prompts or add language-specific handling.

### 📝 Improve Documentation

Docs PRs are always welcome — fixes, examples, clarifications, translations of the README itself.

---

## Development Setup

```bash
# 1. Fork and clone
git clone https://github.com/YOUR-USERNAME/laravel-ai-translator.git
cd laravel-ai-translator

# 2. Install dependencies
composer install

# 3. Run tests
composer test

# 4. Format code
composer format

# 5. Static analysis
composer analyse
```

### Available Scripts

| Command | Description |
|---|---|
| `composer test` | Run the full test suite |
| `composer test-coverage` | Run tests with code coverage |
| `composer format` | Format code with Laravel Pint |
| `composer format-test` | Check formatting without fixing |
| `composer analyse` | Run PHPStan static analysis |

### Running Against a Real Laravel App

The project uses two folders:

```
laravel-ai-translator/          ← package source (this repo)
laravel-translator-test/        ← test Laravel app
```

To test your changes in a real Laravel app:

```bash
# In laravel-translator-test/composer.json, add:
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-ai-translator",
            "options": {"symlink": true}
        }
    ],
    "require": {
        "youssef-mekkkawy/laravel-ai-translator": "@dev"
    }
}

composer install
php artisan lang:scan
php artisan lang:translate --dry-run
```

---

## Adding a New AI Provider

This is where you can make the biggest impact. The architecture makes it straightforward.

### Step 1: Create the translator class

Create `src/Services/Translators/YourProviderTranslator.php`:

```php
<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

class YourProviderTranslator extends AbstractTranslator
{
    public function getName(): string
    {
        return 'yourprovider';
    }

    public function isAvailable(): bool
    {
        return !empty($this->getConfig('api_key'));
    }

    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        // Preserve placeholders and HTML before sending to AI
        [$prepared, $placeholders, $tags] = $this->prepareText($text);

        $translated = $this->callApi($prepared, $targetLang, $sourceLang);

        // Restore placeholders and HTML after translation
        return $this->restoreText($translated, $placeholders, $tags);
    }

    public function estimateCost(array $texts, array $targetLangs): array
    {
        $chars = array_sum(array_map('mb_strlen', $texts)) * count($targetLangs);

        return [
            'characters'     => $chars,
            'cost'           => $chars * 0.000020, // adjust to your provider's pricing
            'currency'       => 'USD',
        ];
    }

    private function callApi(string $text, string $targetLang, string $sourceLang): string
    {
        // Your HTTP call here
        // Use $this->getConfig('api_key') for the API key
        // Use $this->getConfig('model', 'default-model') for the model
    }
}
```

**Key rules:**
- Always call `$this->prepareText()` before sending to the AI
- Always call `$this->restoreText()` after receiving the translation
- Never swallow exceptions silently — let them propagate
- `isAvailable()` should check connectivity, not just config

### Step 2: Register in TranslatorManager

In `src/Services/Translators/TranslatorManager.php`, add your class:

```php
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\YourProviderTranslator;

protected array $translators = [
    'ollama'       => OllamaTranslator::class,
    'deepl'        => DeepLTranslator::class,
    'yourprovider' => YourProviderTranslator::class, // ← add here
];
```

### Step 3: Add config

In `src/config/ai-translator.php`:

```php
'providers' => [
    // ... existing providers ...
    'yourprovider' => [
        'api_key' => env('YOURPROVIDER_API_KEY'),
        'model'   => env('YOURPROVIDER_MODEL', 'default-model'),
    ],
],
```

### Step 4: Write tests

Create `tests/Unit/YourProviderTranslatorTest.php` following the pattern in `OllamaTranslatorTest.php`. Tests must cover:

- [ ] Can be instantiated
- [ ] `isAvailable()` returns true/false correctly
- [ ] `translate()` calls the API and returns translated text
- [ ] Placeholders are preserved (`:name`, `{0}`)
- [ ] HTML tags are preserved
- [ ] `estimateCost()` returns correct structure
- [ ] API errors are handled gracefully

### Step 5: Add to README

Add your provider to the providers table in `README.md`.

---

## Pull Request Process

1. **Fork** the repository and create a branch from `main`:
   ```bash
   git checkout -b feature/add-google-translate
   ```

2. **Write tests** for any new functionality. PRs without tests will not be merged.

3. **Ensure all tests pass:**
   ```bash
   composer test
   ```

4. **Ensure code is formatted:**
   ```bash
   composer format
   ```

5. **Ensure static analysis passes:**
   ```bash
   composer analyse
   ```

6. **Write a clear PR description:**
   - What does this PR do?
   - Why is it needed?
   - Any breaking changes?
   - Screenshots (for UI changes)

7. **Keep PRs focused.** One feature or fix per PR. Large refactors should be discussed in an issue first.

---

## Coding Standards

- **PSR-12** code style (enforced by Laravel Pint)
- **PHPDoc blocks** for all public methods
- **Type hints** on all method parameters and return types (PHP 8.2+)
- **No magic numbers** — use named constants or config values
- **No hardcoded strings** that users might want to customize

---

## Commit Messages

Use clear, descriptive commit messages:

```
# ✅ Good
feat: add Google Translate provider
fix: preserve HTML attributes during translation
docs: add Gemini setup instructions
test: add OllamaTranslator batch fallback test

# ❌ Bad
fix stuff
update
changes
```

---

## Questions?

Open a [GitHub Discussion](https://github.com/Youssef-Mekkkawy/laravel-ai-translator/discussions) — not an issue. Issues are for bugs and feature requests.

---

**Thank you for making Laravel AI Translator better for everyone. 🚀**
