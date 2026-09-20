# Dashboard

Access the dashboard at `/ai-translator` in your Laravel app.

## Overview

The main page shows your translation state at a glance.

![Dashboard Overview](/dashboard-overview.png)

**Stats cards:**
- **Total Keys** — all translation keys found in your views
- **Translated** — how many keys have translations
- **Missing** — keys that still need translation
- **Locked** — keys protected from being overwritten

**Quick actions:**
- **Scan** — find new keys in your views
- **Translate** — start AI translation
- **Dry run** — preview what would be translated without saving

**Active provider** shows which AI provider is connected and when the last sync ran.

**Coverage by language** shows a progress bar per language.

---

## Languages

Manage your configured languages.

![Languages Page](/dashboard-languages.png)

- **Toggle** (switch icon) — enable or disable a language. Disabled languages are skipped during translation.
- **Trash icon** — permanently remove a language and all its translation files.
- **+ Add language** — add a new language from 180+ available options.

Each language card shows:
- Coverage percentage and progress bar
- Number of missing keys
- ✓ Complete when 100% translated

---

## Locked Keys

Protect specific translations from being overwritten.

![Locked Keys Page](/dashboard-locked.png)

When you manually translate a key and want to keep it, lock it. Future `lang:translate` runs will skip locked keys.

**Lock a key:** Click **Lock a Key** → enter language, key name, and optional reason.

**Unlock:** Click the **Unlock** button next to any locked key.

---

## History

Every translation run is recorded with full details.

![History Page](/dashboard-history.png)

Each run shows:
- Date and time
- Number of keys translated
- Languages processed
- Duration
- Provider and model used
- Cost ($0.00 with Ollama)

---

## Backups

Automatic snapshots of your language files.

![Backups Page](/dashboard-backups.png)

A backup is created automatically before every translation run.

- **Download** — download a backup as a zip file
- **Restore** — restore all language files to a previous state
- **Keep last N** — configure how many backups to keep (default: 5)
- **Auto-backup** — toggle automatic backups on/off

---

## Settings

Configure your AI provider and translation behaviour.

![Settings Page](/dashboard-settings.png)

**Active provider:** Choose between Ollama (free, local) or cloud providers.

**Translation behaviour:**
- **Source language** — the language your app is written in
- **Chunk size** — how many keys to send per API request (lower = safer, higher = faster)
- **Context prompt** — give the AI context about your app for better translations

e.g. `"This is an e-commerce app. Use formal language."`
